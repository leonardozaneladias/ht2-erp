<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Atualiza os defaults de fábrica para a identidade HT2 ERP.
 *
 * Idempotente e não-destrutivo: cada cor só é trocada se ainda estiver no
 * valor antigo (paleta Inspinia). Instalações que já customizaram a marca
 * pela aba de Aparência mantêm os valores escolhidos. Em instalações novas
 * (migrate:fresh), o seed inicial já grava a paleta HT2, então estes updates
 * viram no-op.
 */
return new class extends SettingsMigration
{
    /** Mapa cor antiga (Inspinia) → cor nova (HT2 ERP). */
    private const CORES = [
        'branding.cor_primaria' => ['#1ab394', '#1577ce'],
        'branding.cor_secundaria' => ['#1c84c6', '#2b3a67'],
        'branding.cor_sucesso' => ['#0acf97', '#12b886'],
        'branding.cor_warning' => ['#f8ac59', '#f5a623'],
        'branding.cor_perigo' => ['#ed5565', '#e5384b'],
        'branding.cor_info' => ['#23c6c8', '#36a8ff'],
    ];

    public function up(): void
    {
        foreach (self::CORES as $chave => [$antiga, $nova]) {
            $this->migrator->update(
                $chave,
                fn (string $atual): string => strtolower($atual) === $antiga ? $nova : $atual,
            );
        }

        // Nome do sistema: só renomeia se ainda for o default genérico antigo.
        $this->migrator->update(
            'branding.nome_sistema',
            fn (string $atual): string => in_array($atual, ['Laravel', 'Laravel Admin', 'ERP'], true) ? 'HT2 ERP' : $atual,
        );
    }
};
