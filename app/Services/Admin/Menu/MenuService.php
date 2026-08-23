<?php

declare(strict_types=1);

namespace App\Services\Admin\Menu;

use App\Enums\TipoPersonalizacaoMenu;
use App\Models\MenuPersonalizacao;
use HT2ML\Core\Models\AdminUser;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use LogicException;

/**
 * Composição do menu lateral do admin.
 *
 * O registro dos itens vive em config/admin-menu.php (fonte de verdade dos
 * módulos — sempre FLAT; toda seção e item exigem uma `key` estável). TODO o
 * agrupamento é apresentação no banco (menu_personalizacoes): overrides de
 * itens/seções do config, seções custom e grupos (submenus) criados pela
 * tela — estes com `e_custom = true`, nunca tratados como órfãos. Grupo é
 * apresentação pura: sem rota/permissão, só aparece na sidebar quando tem
 * filho visível ao usuário.
 *
 * A visibilidade por usuário não mora aqui além do filtro `can()`: ela é o
 * próprio ACL (permissão de cada item × perfis), via AccessResolver.
 */
final class MenuService
{
    private const CACHE_KEY = 'admin-menu.estrutura';

    private const CACHE_TTL = 600;

    /**
     * Estrutura pronta para a sidebar: inativos removidos (decisão global,
     * vale até no preview), órfãs ignoradas, grupos sem filho visível
     * removidos e filtro de permissão aplicado.
     *
     * @return list<array<string, mixed>>
     */
    public function estruturaParaSidebar(?AdminUser $user, bool $mostrarTudo = false): array
    {
        $podeVer = fn (?string $permissao): bool => $permissao === null
            || $mostrarTudo
            || ($user?->can($permissao) ?? false);

        $secoes = [];

        foreach ($this->estruturaMesclada() as $secao) {
            $itens = [];

            foreach ($secao['items'] as $entry) {
                if (! $entry['ativo']) {
                    continue;
                }

                if ($entry['tipo'] === 'grupo') {
                    $children = array_values(array_filter(
                        $entry['children'],
                        fn (array $filho): bool => $filho['ativo'] && $podeVer($filho['permission'] ?? null),
                    ));

                    // Grupo é apresentação pura: sem filho visível, some.
                    if ($children === []) {
                        continue;
                    }

                    $itens[] = array_merge($entry, ['children' => $children]);

                    continue;
                }

                if (! $podeVer($entry['permission'] ?? null)) {
                    continue;
                }

                $itens[] = array_merge($entry, ['children' => []]);
            }

            if ($itens !== []) {
                $secoes[] = array_merge($secao, ['items' => $itens]);
            }
        }

        return $secoes;
    }

    /**
     * Estrutura completa para a tela de gestão: inclui itens/grupos inativos
     * e as personalizações órfãs (key fora do config — customs nunca contam).
     *
     * @return array{secoes: list<array<string, mixed>>, orfas: Collection<int, MenuPersonalizacao>}
     */
    public function estruturaParaGestao(): array
    {
        $chavesItens = $this->chavesDeItens();
        $chavesSecoes = $this->chavesDeSecoes();

        $orfas = $this->personalizacoes()
            ->filter(function (MenuPersonalizacao $personalizacao) use ($chavesItens, $chavesSecoes): bool {
                if ($personalizacao->e_custom || $personalizacao->tipo === TipoPersonalizacaoMenu::Grupo) {
                    return false;
                }

                return ! in_array(
                    $personalizacao->key,
                    $personalizacao->tipo === TipoPersonalizacaoMenu::Item ? $chavesItens : $chavesSecoes,
                    true,
                );
            })
            ->values();

        return [
            'secoes' => $this->estruturaMesclada(),
            'orfas' => $orfas,
        ];
    }

    /**
     * @return list<string>
     */
    public function chavesDeItens(): array
    {
        $chaves = [];

        foreach ($this->registro() as $secao) {
            foreach ($secao['items'] as $item) {
                $chaves[] = $item['key'];
            }
        }

        return $chaves;
    }

    /**
     * Keys das seções REGISTRADAS no config (não inclui seções custom —
     * para destinos de drag/criação use destinosDeSecao()).
     *
     * @return list<string>
     */
    public function chavesDeSecoes(): array
    {
        return array_column($this->registro(), 'key');
    }

    /**
     * Keys dos grupos existentes (sempre criados pela tela).
     *
     * @return list<string>
     */
    public function chavesDeGrupos(): array
    {
        return $this->personalizacoes()
            ->filter(fn (MenuPersonalizacao $p): bool => $p->tipo === TipoPersonalizacaoMenu::Grupo)
            ->pluck('key')
            ->values()
            ->all();
    }

    /**
     * Seções válidas como destino (config + custom).
     *
     * @return list<string>
     */
    public function destinosDeSecao(): array
    {
        $customs = $this->personalizacoes()
            ->filter(fn (MenuPersonalizacao $p): bool => $p->tipo === TipoPersonalizacaoMenu::Secao && $p->e_custom)
            ->pluck('key')
            ->all();

        return array_values(array_unique(array_merge($this->chavesDeSecoes(), $customs)));
    }

    /**
     * Permissão vinculada ao item no config (null = visível para todos
     * ou key desconhecida).
     */
    public function permissaoDoItem(string $itemKey): ?string
    {
        foreach ($this->registro() as $secao) {
            foreach ($secao['items'] as $item) {
                if ($item['key'] === $itemKey) {
                    return $item['permission'] ?? null;
                }
            }
        }

        return null;
    }

    /**
     * Valores padrão (do config) de um item ou seção — base da normalização
     * das personalizações (valor igual ao padrão vira null). Grupos e seções
     * custom não têm padrão (retorna null).
     *
     * @return array{label: string, icone: string|null}|null
     */
    public function padraoDe(TipoPersonalizacaoMenu $tipo, string $key): ?array
    {
        foreach ($this->registro() as $secao) {
            if ($tipo === TipoPersonalizacaoMenu::Secao && $secao['key'] === $key) {
                return ['label' => $secao['title'], 'icone' => null];
            }

            if ($tipo === TipoPersonalizacaoMenu::Item) {
                foreach ($secao['items'] as $item) {
                    if ($item['key'] === $key) {
                        return ['label' => $item['label'], 'icone' => $item['icon'] ?? null];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Seção em que o item está registrado no config.
     */
    public function secaoNaturalDoItem(string $itemKey): ?string
    {
        foreach ($this->registro() as $secao) {
            foreach ($secao['items'] as $item) {
                if ($item['key'] === $itemKey) {
                    return $secao['key'];
                }
            }
        }

        return null;
    }

    public function invalidarCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Config + personalizações mesclados e ordenados, sem filtro de permissão
     * (o filtro é por usuário e roda por request, já amparado pelo AccessCache).
     *
     * @return list<array<string, mixed>>
     */
    private function estruturaMesclada(): array
    {
        /** @var list<array<string, mixed>> */
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn (): array => $this->mesclar());
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mesclar(): array
    {
        $registro = $this->registro();
        $personalizacoes = $this->personalizacoes();

        $porChave = $personalizacoes->keyBy(
            fn (MenuPersonalizacao $personalizacao): string => $personalizacao->tipo->value . ':' . $personalizacao->key,
        );

        // 1. Seções do config (com overrides) + seções custom ao fim.
        $secoes = [];
        $indiceSecao = 0;

        foreach ($registro as $secao) {
            $ajuste = $porChave->get('secao:' . $secao['key']);

            $secoes[$secao['key']] = [
                'key' => $secao['key'],
                'title' => $ajuste->label ?? $secao['title'],
                'titlePadrao' => $secao['title'],
                'personalizado' => $ajuste !== null,
                'eCustom' => false,
                'posicao' => [$ajuste->ordem ?? PHP_INT_MAX, $indiceSecao],
                'items' => [],
            ];

            $indiceSecao++;
        }

        foreach ($personalizacoes as $personalizacao) {
            if ($personalizacao->tipo !== TipoPersonalizacaoMenu::Secao || ! $personalizacao->e_custom) {
                continue;
            }

            $secoes[$personalizacao->key] = [
                'key' => $personalizacao->key,
                'title' => $personalizacao->label ?? $personalizacao->key,
                'titlePadrao' => null,
                'personalizado' => true,
                'eCustom' => true,
                'posicao' => [$personalizacao->ordem ?? PHP_INT_MAX, $indiceSecao],
                'items' => [],
            ];

            $indiceSecao++;
        }

        if ($secoes === []) {
            return [];
        }

        // 2. Grupos (sempre custom): seção destino com fallback na 1ª seção.
        $grupos = [];
        $indiceGrupo = 0;

        foreach ($personalizacoes as $personalizacao) {
            if ($personalizacao->tipo !== TipoPersonalizacaoMenu::Grupo) {
                continue;
            }

            $destino = $personalizacao->secao_key !== null && isset($secoes[$personalizacao->secao_key])
                ? $personalizacao->secao_key
                : array_key_first($secoes);

            $grupos[$personalizacao->key] = [
                'tipo' => 'grupo',
                'key' => $personalizacao->key,
                'label' => $personalizacao->label ?? $personalizacao->key,
                'icon' => $personalizacao->icone ?? 'tabler--folder',
                'ativo' => $personalizacao->ativo,
                'eCustom' => true,
                'personalizado' => true,
                'destinoSecao' => $destino,
                // Tie-break alto: grupo sem ordem cai depois dos itens sem ordem.
                'posicao' => [$personalizacao->ordem ?? PHP_INT_MAX, 100000 + $indiceGrupo],
                'children' => [],
            ];

            $indiceGrupo++;
        }

        // 3. Itens do config: container = grupo > seção personalizada > natural.
        //    Duas passadas: filhos coletados primeiro, anexados na montagem.
        $childrenPorGrupo = [];
        $indiceNatural = 0;

        foreach ($registro as $secao) {
            foreach ($secao['items'] as $item) {
                $ajuste = $porChave->get('item:' . $item['key']);

                // O registry é flat: agrupamento é apresentação (banco).
                unset($item['children']);

                $entry = array_merge($item, [
                    'tipo' => 'item',
                    'label' => $ajuste->label ?? $item['label'],
                    'labelPadrao' => $item['label'],
                    'icon' => $ajuste->icone ?? $item['icon'],
                    'iconPadrao' => $item['icon'],
                    'ativo' => $ajuste->ativo ?? true,
                    'secaoNaturalKey' => $secao['key'],
                    'grupoKey' => null,
                    'personalizado' => $ajuste !== null,
                    'posicao' => [$ajuste->ordem ?? PHP_INT_MAX, $indiceNatural],
                ]);

                $indiceNatural++;

                if ($ajuste?->grupo_key !== null && isset($grupos[$ajuste->grupo_key])) {
                    $entry['grupoKey'] = $ajuste->grupo_key;
                    $childrenPorGrupo[$ajuste->grupo_key][] = $entry;

                    continue;
                }

                $destino = $ajuste?->secao_key !== null && isset($secoes[$ajuste->secao_key])
                    ? $ajuste->secao_key
                    : $secao['key'];

                $secoes[$destino]['items'][] = $entry;
            }
        }

        // 4. Grupos entram na sequência de itens da seção destino.
        foreach ($grupos as $key => $grupo) {
            $children = $childrenPorGrupo[$key] ?? [];
            usort($children, fn (array $a, array $b): int => $a['posicao'] <=> $b['posicao']);

            $grupo['children'] = $children;
            $destino = $grupo['destinoSecao'];
            unset($grupo['destinoSecao']);

            $secoes[$destino]['items'][] = $grupo;
        }

        // 5. Ordena por [ordem personalizada, posição natural]: registro novo
        //    de módulo futuro cai no fim de um container já reordenado.
        $secoes = array_values($secoes);
        usort($secoes, fn (array $a, array $b): int => $a['posicao'] <=> $b['posicao']);

        foreach ($secoes as &$secao) {
            usort($secao['items'], fn (array $a, array $b): int => $a['posicao'] <=> $b['posicao']);
            unset($secao['posicao']);

            foreach ($secao['items'] as &$entry) {
                unset($entry['posicao']);

                if ($entry['tipo'] === 'grupo') {
                    foreach ($entry['children'] as &$filho) {
                        unset($filho['posicao']);
                    }
                    unset($filho);
                }
            }
            unset($entry);
        }
        unset($secao);

        return $secoes;
    }

    /**
     * @return Collection<int, MenuPersonalizacao>
     */
    private function personalizacoes(): Collection
    {
        try {
            return MenuPersonalizacao::query()->get()->toBase();
        } catch (QueryException) {
            // A sidebar renderiza em toda página do painel: antes da migration
            // rodar, degrada para o menu puro do config em vez de quebrar.
            return new Collection;
        }
    }

    /**
     * Config validado: toda seção e item de 1º nível exigem `key` estável
     * (âncora das personalizações). Falha cedo para o dev do módulo.
     *
     * @return list<array{key: string, title: string, items: list<array<string, mixed>>}>
     */
    private function registro(): array
    {
        $registro = [];

        /** @var list<array<string, mixed>> $config */
        $config = config('admin-menu', []);

        foreach ($config as $indice => $secao) {
            $secaoKey = $secao['key'] ?? null;

            if (! is_string($secaoKey) || $secaoKey === '') {
                throw new LogicException(
                    "Seção #{$indice} sem 'key' em config/admin-menu.php — toda seção exige uma key estável.",
                );
            }

            foreach ($secao['items'] ?? [] as $posicao => $item) {
                $itemKey = $item['key'] ?? null;

                if (! is_string($itemKey) || $itemKey === '') {
                    throw new LogicException(
                        "Item #{$posicao} da seção '{$secaoKey}' sem 'key' em config/admin-menu.php — todo item exige uma key estável.",
                    );
                }
            }

            $registro[] = $secao;
        }

        /** @var list<array{key: string, title: string, items: list<array<string, mixed>>}> $registro */
        return $registro;
    }
}
