<?php

declare(strict_types=1);

namespace HT2ML\Documentos;

use Illuminate\Support\ServiceProvider;

/**
 * Numeração sequencial de documentos por empresa e ano.
 *
 * O mecanismo é genérico; o vocabulário (TipoDocumento: matrícula, contrato,
 * protocolo, recibo) é de domínio — foi por isso que saiu do núcleo. Zero uso em
 * produção quando foi extraído, o que tornou a discussão barata: quem precisar,
 * instala.
 *
 * Sem provider de rotas, menu ou permissões: este pacote não tem tela. É
 * biblioteca, consumida por quem numera documentos.
 */
final class DocumentosServiceProvider extends ServiceProvider
{
    /**
     * O binding vive AQUI, não no AppServiceProvider do app.
     *
     * Estava lá porque a classe morava em app/. Quando ela virou pacote, o
     * singleton ficou para trás — e um produto que não instala esta extensão
     * tinha um AppServiceProvider referenciando classe inexistente. O PHPStan
     * do skeleton pegou: "Class HT2ML\Documentos\Support\GeradorNumeroDocumento
     * not found".
     */
    public function register(): void
    {
        $this->app->singleton(Support\GeradorNumeroDocumento::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
