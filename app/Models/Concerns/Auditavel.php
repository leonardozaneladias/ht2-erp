<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Auditoria automática de mudanças de dados (padrão do projeto).
 *
 * Captura created/updated/deleted/restored com o diff completo de atributos
 * (properties.attributes / properties.old) no activity_log. O contexto de
 * tenant, causer, IP e rótulo do registro é carimbado de forma central em
 * App\Support\Audit\CarimbarContextoNaAtividade.
 *
 * Regra do projeto: o trait é a verdade crua de atributos; Actions só logam
 * eventos de DOMÍNIO que o diff não expressa (sync de pivôs/roles, settings,
 * auth, resumos de operação em massa). Ver docs/devops/conventions.md §7.2.
 *
 * Overrides por model (todos opcionais):
 * - atributosNaoAuditados(): atributos excluídos do diff (ruído/sensíveis do
 *   model — os sensíveis globais ficam em activitylog.default_except_attributes);
 * - nomeLogAuditoria(): log_name (default: nome da tabela);
 * - descricaoEventoAuditoria(): descrição PT-BR por evento;
 * - rotuloAuditoria(): rótulo humano do registro gravado em
 *   properties.subject_label (default: nome/name/titulo/email, ver carimbo).
 */
trait Auditavel
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        // Timestamps são metadado, não dado: fora do diff (o próprio log já
        // tem created_at). Com eles excluídos, um touch() não gera log
        // (diff vazio + dontLogEmptyChanges).
        return LogOptions::defaults()
            ->logAll()
            ->logExcept([...$this->atributosNaoAuditados(), 'created_at', 'updated_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName($this->nomeLogAuditoria())
            ->setDescriptionForEvent(fn (string $evento): string => $this->descricaoEventoAuditoria($evento));
    }

    /**
     * Atributos do model excluídos do diff (ruído de sessão/navegação, hashes).
     *
     * @return list<string>
     */
    protected function atributosNaoAuditados(): array
    {
        return [];
    }

    /**
     * log_name no activity_log — default é o nome da tabela (ex.: admin_users).
     */
    protected function nomeLogAuditoria(): string
    {
        return $this->getTable();
    }

    protected function descricaoEventoAuditoria(string $evento): string
    {
        return match ($evento) {
            'created' => 'Registro criado',
            'updated' => 'Registro atualizado',
            'deleted' => 'Registro excluído',
            'restored' => 'Registro restaurado',
            'forceDeleted' => 'Registro excluído permanentemente',
            default => $evento,
        };
    }
}
