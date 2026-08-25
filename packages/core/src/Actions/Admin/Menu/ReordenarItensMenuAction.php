<?php

declare(strict_types=1);

namespace HT2ML\Core\Actions\Admin\Menu;

use HT2ML\Core\Enums\TipoPersonalizacaoMenu;
use HT2ML\Core\Models\MenuPersonalizacao;
use HT2ML\Core\Services\Admin\Menu\MenuService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Persiste a nova ordem após um drag & drop de itens/grupos, incluindo a
 * movimentação entre containers. Containers são prefixados: `secao:<key>`
 * (nível raiz da seção — aceita itens e grupos) ou `grupo:<key>` (filhos do
 * grupo — só itens). O payload vem do cliente e é hostil: toda key é
 * validada contra o registro do config e os grupos do banco antes de gravar.
 */
class ReordenarItensMenuAction
{
    public function __construct(private readonly MenuService $menu) {}

    /**
     * @param  array<string, list<string>>  $ordens  ordem completa por container afetado
     */
    public function execute(string $movidoKey, string $containerDestino, array $ordens): void
    {
        $chavesItens = $this->menu->chavesDeItens();
        $chavesGrupos = $this->menu->chavesDeGrupos();
        $destinosSecao = $this->menu->destinosDeSecao();

        $this->parseContainer($containerDestino, $destinosSecao, $chavesGrupos);

        if (! in_array($movidoKey, $chavesItens, true) && ! in_array($movidoKey, $chavesGrupos, true)) {
            throw new InvalidArgumentException('Registro de menu desconhecido.');
        }

        foreach ($ordens as $container => $keys) {
            [$tipoContainer] = $this->parseContainer((string) $container, $destinosSecao, $chavesGrupos);

            foreach ($keys as $key) {
                $ehItem = is_string($key) && in_array($key, $chavesItens, true);
                $ehGrupo = is_string($key) && in_array($key, $chavesGrupos, true);

                if (! $ehItem && ! $ehGrupo) {
                    throw new InvalidArgumentException('Registro desconhecido no payload de ordenação.');
                }

                if ($tipoContainer === 'grupo' && $ehGrupo) {
                    throw new InvalidArgumentException('Grupos não podem ficar dentro de grupos.');
                }
            }
        }

        DB::transaction(function () use ($movidoKey, $containerDestino, $ordens, $destinosSecao, $chavesGrupos): void {
            foreach ($ordens as $container => $keys) {
                [$tipoContainer, $containerKey] = $this->parseContainer((string) $container, $destinosSecao, $chavesGrupos);

                foreach (array_values($keys) as $posicao => $key) {
                    if (in_array($key, $chavesGrupos, true)) {
                        // Grupo reordenado/movido no nível raiz da seção.
                        //
                        // firstOrNew, não firstOrFail: um grupo DECLARADO no
                        // config existe sem linha no banco, e a linha nasce na
                        // primeira vez que alguém o arrasta. Com firstOrFail,
                        // reordenar qualquer coisa numa seção que tivesse um
                        // grupo declarado estourava ModelNotFoundException.
                        //
                        // e_custom fica no default (false): o grupo veio do
                        // config, então não é excluível pela tela, e restaurar
                        // apaga a personalização e o devolve ao declarado.
                        $grupo = MenuPersonalizacao::query()->firstOrNew([
                            'tipo' => TipoPersonalizacaoMenu::Grupo,
                            'key' => $key,
                        ]);

                        $grupo->ordem = $posicao + 1;
                        $grupo->secao_key = $containerKey;
                        $grupo->save();

                        continue;
                    }

                    $personalizacao = MenuPersonalizacao::query()->firstOrNew([
                        'tipo' => TipoPersonalizacaoMenu::Item,
                        'key' => $key,
                    ]);

                    $personalizacao->ordem = $posicao + 1;

                    if ($tipoContainer === 'grupo') {
                        $personalizacao->grupo_key = $containerKey;
                        $personalizacao->secao_key = null;
                    } else {
                        // Seção destino só é gravada quando difere da natural —
                        // mantém o badge "Personalizado" significativo. A exceção
                        // é o item que o config declara DENTRO de um grupo: aí
                        // pousar na raiz da própria seção é a decisão de tirá-lo
                        // do grupo, e precisa ficar gravada. Sem isto o item
                        // voltaria ao grupo declarado no próximo render, porque
                        // grupo_key nulo é indistinguível de "ninguém decidiu".
                        $personalizacao->grupo_key = null;
                        $personalizacao->secao_key = $containerKey === $this->menu->secaoNaturalDoItem($key)
                            && $this->menu->grupoNaturalDoItem($key) === null
                            ? null
                            : $containerKey;
                    }

                    $personalizacao->save();
                }
            }

            // Resumo de domínio: a operação em massa que os diffs por linha
            // (trait Auditavel) não expressam de uma vez.
            activity('menus')
                ->withProperties(['item' => $movidoKey, 'container_destino' => $containerDestino, 'ordens' => $ordens])
                ->event('menu_reordenado')
                ->log('Itens do menu reordenados');
        });

        $this->menu->invalidarCache();
    }

    /**
     * Valida e decompõe um container prefixado.
     *
     * @param  list<string>  $destinosSecao
     * @param  list<string>  $chavesGrupos
     * @return array{0: string, 1: string} [tipo, key]
     */
    private function parseContainer(string $container, array $destinosSecao, array $chavesGrupos): array
    {
        [$tipo, $key] = array_pad(explode(':', $container, 2), 2, '');

        if ($tipo === 'secao' && in_array($key, $destinosSecao, true)) {
            return ['secao', $key];
        }

        if ($tipo === 'grupo' && in_array($key, $chavesGrupos, true)) {
            return ['grupo', $key];
        }

        throw new InvalidArgumentException("Container de menu desconhecido: {$container}");
    }
}
