<?php

declare(strict_types=1);

namespace App\Support\Generator;

use Illuminate\Support\Str;

/**
 * Identidade de uma extensão, derivada do nome e de config/extensoes.php.
 *
 * Concentra toda a resolução de nomes/paths/namespaces de um pacote de módulo,
 * para que o gerador não espalhe convenções de nomenclatura (ver ADR-0015).
 */
final class Extensao
{
    public function __construct(
        public readonly string $studly,        // Rh
        public readonly string $slug,          // rh
        public readonly string $namespaceBase, // HT2ML\Rh
        public readonly string $pacote,        // ht2ml/extensao-rh
        public readonly string $dir,           // packages/extensao-rh
        public readonly string $viewNamespace, // rh
        public readonly string $providerClass, // RhServiceProvider
    ) {}

    /**
     * @param  array{vendor: string, namespace: string, path: string, prefixo_pacote: string}  $config
     */
    public static function criar(string $nome, array $config): self
    {
        $studly = Str::studly($nome);
        $slug = Str::kebab($studly);
        $pastaPacote = $config['prefixo_pacote'] . $slug;

        return new self(
            studly: $studly,
            slug: $slug,
            namespaceBase: $config['namespace'] . '\\' . $studly,
            pacote: $config['vendor'] . '/' . $pastaPacote,
            dir: $config['path'] . '/' . $pastaPacote,
            viewNamespace: $slug,
            providerClass: $studly . 'ServiceProvider',
        );
    }

    /**
     * Cria a partir do config/extensoes.php da aplicação.
     */
    public static function paraNome(string $nome): self
    {
        /** @var array{vendor: string, namespace: string, path: string, prefixo_pacote: string} $config */
        $config = config('extensoes');

        return self::criar($nome, $config);
    }

    /**
     * FQN da classe ServiceProvider (para extra.laravel.providers do composer.json).
     */
    public function providerFqn(): string
    {
        return $this->namespaceBase . '\\' . $this->providerClass;
    }
}
