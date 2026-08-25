<?php

declare(strict_types=1);

namespace HT2ML\Core\Support\Access;

use HT2ML\Core\Enums\ModuloAcesso;
use Illuminate\Support\Str;

/**
 * Uma gaveta do catálogo de permissões — a divisão que a matriz de acesso, o
 * painel de perfil e o de pessoa usam para agrupar ~200 permissões em algo
 * navegável.
 *
 * Antes disto a gaveta era o enum {@see ModuloAcesso}: onze casos fechados com
 * quatro `match` exaustivos. Um produto com módulos de negócio próprios tinha
 * duas saídas — empilhar tudo em 'negocio', onde a tela deixa de ser navegável,
 * ou editar o core. Abrir o CONJUNTO resolve; apagar o TIPO espalharia risco,
 * porque onze chaves de config, duas telas, três blades e dois testes dependem
 * do enum. Então o enum continua sendo a SEMENTE de `config('access.areas')` e
 * este VO é o tipo que representa qualquer área, venha ela do enum, do config
 * do produto ou de uma extensão via `ModuleRegistry::areaDeAcesso()`.
 *
 * Nunca lança para chave desconhecida: `de()` devolve uma área não-declarada
 * com rótulo derivado. É o mesmo `?? Str::headline($chave)` que os três blades
 * já faziam à mão — a diferença é que agora existe UM lugar decidindo isso, e
 * `$declarada` deixa o diagnóstico (ht2ml:doutor) distinguir os dois casos.
 */
final readonly class AreaDeAcesso
{
    public function __construct(
        public string $chave,
        public string $label,
        public string $descricao = '',
        public string $icone = 'tabler--box',
        public string $variant = 'neutral',
        /** false quando a chave não está em config('access.areas') — rótulo derivado. */
        public bool $declarada = true,
    ) {}

    /**
     * A área de uma chave, sempre. Chave desconhecida devolve área derivada.
     */
    public static function de(string $chave): self
    {
        $catalogo = self::catalogo();

        if (! isset($catalogo[$chave])) {
            return new self(
                chave: $chave,
                label: Str::headline($chave),
                declarada: false,
            );
        }

        return self::doArray($chave, $catalogo[$chave]);
    }

    /**
     * Todas as áreas declaradas, na ordem do catálogo.
     *
     * @return array<string, self>
     */
    public static function todas(): array
    {
        $areas = [];

        foreach (self::catalogo() as $chave => $dados) {
            $areas[$chave] = self::doArray($chave, $dados);
        }

        return $areas;
    }

    public static function existe(string $chave): bool
    {
        return isset(self::catalogo()[$chave]);
    }

    /**
     * Semente do catálogo: os onze casos do enum, sem redigitá-los na config.
     * Chamado por `config/access.php`.
     *
     * @return array<string, array{label: string, descricao: string, icone: string, variant: string}>
     */
    public static function sementeDoEnum(): array
    {
        $areas = [];

        foreach (ModuloAcesso::cases() as $caso) {
            $areas[$caso->value] = [
                'label' => $caso->label(),
                'descricao' => $caso->descricao(),
                'icone' => $caso->icone(),
                'variant' => $caso->variant(),
            ];
        }

        return $areas;
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    private static function doArray(string $chave, array $dados): self
    {
        return new self(
            chave: $chave,
            label: is_string($dados['label'] ?? null) ? $dados['label'] : Str::headline($chave),
            descricao: is_string($dados['descricao'] ?? null) ? $dados['descricao'] : '',
            icone: is_string($dados['icone'] ?? null) ? $dados['icone'] : 'tabler--box',
            variant: is_string($dados['variant'] ?? null) ? $dados['variant'] : 'neutral',
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function catalogo(): array
    {
        /** @var array<string, array<string, mixed>> $areas */
        $areas = (array) config('access.areas', []);

        return $areas;
    }
}
