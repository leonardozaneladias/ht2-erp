<?php

declare(strict_types=1);

namespace HT2ML\Core\Support\Audit;

use HT2ML\Core\Models\Activity;
use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Models\Empresa;
use HT2ML\Core\Models\Filial;
use HT2ML\Core\Support\Impersonation\ImpersonationContext;
use HT2ML\Core\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Carimba o contexto ambiente em cada atividade no momento do `creating`.
 * Ponto único de "contexto → activity_log":
 *
 * - empresa/filial: derivadas do SUBJECT quando ele as expõe (registro de outra
 *   empresa não ganha o carimbo do tenant ativo por engano), senão do tenant;
 * - causer: fallback para o usuário autenticado (guard admin/web) — cobre os
 *   logs disparados pelo trait Auditavel, cujo causer o pacote não resolve
 *   porque o guard default da app não é o admin;
 * - properties.subject_label: rótulo humano do registro resolvido na escrita —
 *   a UI não depende do morph (que o global scope de empresa filtraria na
 *   visão cross-empresa e que um hard delete deixaria órfão);
 * - properties.contexto: ip/user_agent quando há request real (omitido em
 *   console/fila);
 * - properties.impersonado_por: quem está por trás durante personificação.
 */
final class CarimbarContextoNaAtividade
{
    public function __construct(
        private readonly TenantContext $tenant,
        private readonly ImpersonationContext $impersonation,
    ) {}

    public function __invoke(Activity $activity): void
    {
        $subject = $activity->relationLoaded('subject') ? $activity->getRelation('subject') : null;

        // ??= respeita um empresa_id/filial_id já setado explicitamente pela ação.
        $activity->empresa_id ??= $this->empresaDoSubject($subject) ?? $this->tenant->empresaAtivaId();
        $activity->filial_id ??= $this->filialDoSubject($subject) ?? $this->tenant->filialAtivaId();

        $this->resolverCauser($activity);

        $properties = collect($activity->properties ?? []);

        if ($subject instanceof Model && ! $properties->has('subject_label')) {
            $properties->put('subject_label', $this->rotuloDoSubject($subject));
        }

        $ip = request()->ip();

        if ($ip !== null && ! $properties->has('contexto')) {
            $properties->put('contexto', [
                'ip' => $ip,
                'user_agent' => Str::limit((string) request()->userAgent(), 500),
            ]);
        }

        $properties = $this->carimbarImpersonacao($properties);

        $activity->properties = $properties;
    }

    private function empresaDoSubject(?object $subject): ?int
    {
        if ($subject instanceof Empresa) {
            return $subject->id;
        }

        if ($subject instanceof Model && $subject->getAttribute('empresa_id') !== null) {
            return (int) $subject->getAttribute('empresa_id');
        }

        return null;
    }

    private function filialDoSubject(?object $subject): ?int
    {
        if ($subject instanceof Filial) {
            return $subject->id;
        }

        if ($subject instanceof Empresa) {
            return null;
        }

        if ($subject instanceof Model && $subject->getAttribute('filial_id') !== null) {
            return (int) $subject->getAttribute('filial_id');
        }

        return null;
    }

    private function resolverCauser(Activity $activity): void
    {
        if ($activity->causer_id !== null) {
            return;
        }

        $causer = Auth::guard('admin')->user() ?? Auth::guard('web')->user();

        if ($causer !== null) {
            $activity->causer()->associate($causer);
        }
    }

    private function rotuloDoSubject(Model $subject): string
    {
        if (method_exists($subject, 'rotuloAuditoria')) {
            return $subject->rotuloAuditoria();
        }

        foreach (['nome', 'name', 'titulo', 'email'] as $atributo) {
            $valor = $subject->getAttribute($atributo);

            if (is_string($valor) && $valor !== '') {
                return $valor;
            }
        }

        return class_basename($subject) . ' #' . $subject->getKey();
    }

    /**
     * @param  \Illuminate\Support\Collection<array-key, mixed>  $properties
     * @return \Illuminate\Support\Collection<array-key, mixed>
     */
    private function carimbarImpersonacao(\Illuminate\Support\Collection $properties): \Illuminate\Support\Collection
    {
        if (! $this->impersonation->ativo()) {
            return $properties;
        }

        $originalId = $this->impersonation->originalId();

        if ($originalId === null) {
            return $properties;
        }

        $original = AdminUser::find($originalId);

        return $properties->put('impersonado_por', ['id' => $originalId, 'nome' => $original?->nome]);
    }
}
