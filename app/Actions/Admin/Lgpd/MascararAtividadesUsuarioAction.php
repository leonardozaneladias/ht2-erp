<?php

declare(strict_types=1);

namespace App\Actions\Admin\Lgpd;

use HT2ML\Core\Models\Activity;
use HT2ML\Core\Models\AdminUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Complemento LGPD da anonimização: mascara a PII que ficou nos logs ANTIGOS
 * do usuário (diffs do trait Auditavel, subject_label, IP de navegação e
 * e-mail em eventos de auth). O activity_log é append-only por convenção —
 * o mascaramento na anonimização é exceção sancionada, da mesma natureza da
 * anonimização do próprio registro (docs/devops/security-operations.md §9.5).
 */
final class MascararAtividadesUsuarioAction
{
    private const CHAVES_PII = ['nome', 'email', 'telefone', 'cargo', 'bio', 'avatar_url'];

    private const MASCARA = '[removido-lgpd]';

    /**
     * @return int Quantidade de atividades mascaradas.
     */
    public function execute(AdminUser $alvo, string $emailOriginal): int
    {
        $total = 0;

        $this->consulta($alvo, $emailOriginal)->chunkById(200, function (Collection $atividades) use ($alvo, $emailOriginal, &$total): void {
            foreach ($atividades as $atividade) {
                if ($this->mascarar($atividade, $alvo, $emailOriginal)) {
                    $total++;
                }
            }
        });

        return $total;
    }

    /**
     * Atividades onde o usuário é subject, é causer ou aparece por e-mail
     * (eventos de auth pré-login não têm subject/causer — ex.: login-falhou).
     *
     * @return Builder<Activity>
     */
    private function consulta(AdminUser $alvo, string $emailOriginal): Builder
    {
        return Activity::query()->where(function (Builder $query) use ($alvo, $emailOriginal): void {
            $query
                ->where(fn (Builder $q): Builder => $q
                    ->where('subject_type', AdminUser::class)
                    ->where('subject_id', $alvo->id))
                ->orWhere(fn (Builder $q): Builder => $q
                    ->where('causer_type', AdminUser::class)
                    ->where('causer_id', $alvo->id))
                ->orWhere('properties->email', $emailOriginal);
        });
    }

    private function mascarar(Activity $atividade, AdminUser $alvo, string $emailOriginal): bool
    {
        $alterou = false;
        $ehSubject = $atividade->subject_type === AdminUser::class && $atividade->subject_id === $alvo->id;
        $ehCauser = $atividade->causer_type === AdminUser::class && $atividade->causer_id === $alvo->id;

        $properties = collect($atividade->properties ?? [])->all();

        // PII no diff e nas properties só quando o REGISTRO é o usuário —
        // num diff de Empresa editada por ele, "nome" é o nome da empresa.
        if ($ehSubject) {
            $changes = collect($atividade->attribute_changes ?? [])->all();

            foreach (['attributes', 'old'] as $lado) {
                foreach (self::CHAVES_PII as $chave) {
                    if (isset($changes[$lado][$chave]) && $changes[$lado][$chave] !== self::MASCARA) {
                        $changes[$lado][$chave] = self::MASCARA;
                        $alterou = true;
                    }
                }
            }

            if ($alterou) {
                $atividade->attribute_changes = collect($changes);
            }

            foreach ([...self::CHAVES_PII, 'subject_label'] as $chave) {
                if (isset($properties[$chave]) && is_string($properties[$chave]) && $properties[$chave] !== self::MASCARA) {
                    $properties[$chave] = self::MASCARA;
                    $alterou = true;
                }
            }
        }

        // E-mail solto em eventos de auth (login-falhou, reset etc.) — nesses
        // eventos sem causer, o contexto (ip/ua) também é do titular.
        $ehTitularPorEmail = isset($properties['email']) && $properties['email'] === $emailOriginal;

        if ($ehTitularPorEmail) {
            $properties['email'] = self::MASCARA;
            $alterou = true;
        }

        // IP/user_agent identificam a pessoa por trás das ações dela.
        if (($ehCauser || $ehTitularPorEmail) && isset($properties['contexto']) && is_array($properties['contexto'])) {
            foreach (['ip', 'user_agent'] as $chave) {
                if (isset($properties['contexto'][$chave]) && $properties['contexto'][$chave] !== self::MASCARA) {
                    $properties['contexto'][$chave] = self::MASCARA;
                    $alterou = true;
                }
            }
        }

        if (! $alterou) {
            return false;
        }

        $atividade->properties = collect($properties);
        $atividade->save();

        return true;
    }
}
