<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Atualiza a paleta de fábrica da plataforma.
 *
 * Idempotente e não-destrutivo: cada cor só é trocada se ainda estiver no
 * valor antigo (paleta Inspinia). Instalações que já customizaram a marca
 * pela aba de Aparência mantêm os valores escolhidos.
 *
 * Não grava nome de sistema: por decisão do ADR-0019 a plataforma é abstrata,
 * e o nome vem de APP_NAME em cada produto. Uma migration que carimbasse nome
 * de produto no banco reintroduziria exatamente o problema que o ADR-0017
 * resolveu ao aposentar o clone da base.
 */
return new class extends SettingsMigration
{
    /** Mapa cor antiga (Inspinia) → cor nova (paleta da plataforma). */
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
    }
};
