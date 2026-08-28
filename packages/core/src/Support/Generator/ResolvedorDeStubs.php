<?php

declare(strict_types=1);

namespace HT2ML\Core\Support\Generator;

use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Localiza um stub do gerador, com o produto vencendo o núcleo.
 *
 * Antes de 2026-08-24 os stubs viviam em `stubs/` na raiz do monorepo e o
 * comando os lia com `base_path('stubs/modulo')`. Isso quebrava o gerador em
 * qualquer produto: os stubs não viajam no pacote (`find vendor/ht2ml/core -name
 * '*.stub'` devolvia vazio), então `make:modulo` no EduConecta morria antes de
 * escrever um byte. O caminho documentado em docs/criar-recurso.md estava morto
 * justamente no repositório onde as telas iam nascer.
 *
 * A precedência é POR ARQUIVO, não por diretório: um produto que queira mudar só
 * o stub da tabela põe `stubs/modulo/livewire-table.stub` no próprio repositório
 * e herda os outros 18 do núcleo. Sobrescrever por diretório obrigaria a copiar
 * os 19 e a mantê-los sincronizados a cada upgrade — que é exatamente a dívida
 * que as views do PowerGrid criaram.
 */
final readonly class ResolvedorDeStubs
{
    /** @param string $grupo 'modulo' ou 'extensao' */
    public function __construct(private string $grupo) {}

    /**
     * Diretórios consultados, na ordem de precedência.
     *
     * @return list<string>
     */
    public function diretorios(): array
    {
        // Normalizados: sem isto o caminho do núcleo sai como
        // '.../packages/core/src/../../../stubs/modulo', que polui a mensagem de
        // erro e quebra qualquer comparação de caminho.
        return array_map(
            static fn (string $dir): string => (string) (realpath($dir) ?: $dir),
            [
                base_path("stubs/{$this->grupo}"),           // produto
                __DIR__ . "/../../../stubs/{$this->grupo}",  // núcleo
            ],
        );
    }

    public function existe(string $stub): bool
    {
        return $this->localizar($stub) !== null;
    }

    /**
     * @throws RuntimeException quando o stub não existe em lugar nenhum
     */
    public function caminho(string $stub): string
    {
        $caminho = $this->localizar($stub);

        if ($caminho === null) {
            throw new RuntimeException(sprintf(
                "Stub '%s' não encontrado. Procurado em: %s",
                $stub,
                implode(', ', $this->diretorios()),
            ));
        }

        return $caminho;
    }

    public function conteudo(string $stub): string
    {
        return (string) File::get($this->caminho($stub));
    }

    private function localizar(string $stub): ?string
    {
        foreach ($this->diretorios() as $dir) {
            if (File::isFile($caminho = "{$dir}/{$stub}")) {
                return $caminho;
            }
        }

        return null;
    }
}
