<?php

declare(strict_types=1);

namespace App\Services\Admin\Menu;

use App\Enums\TipoPersonalizacaoMenu;
use App\Models\AdminUser;
use App\Models\MenuPersonalizacao;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use LogicException;

/**
 * Composição do menu lateral do admin.
 *
 * O registro dos itens vive em config/admin-menu.php (fonte de verdade dos
 * módulos — toda seção e item exigem uma `key` estável); as personalizações
 * do administrador (ordem, rótulo, ícone, seção, ativo) vivem na tabela
 * menu_personalizacoes e são mescladas por cima. Personalização cuja key
 * sumiu do config é órfã: ignorada na sidebar, listada na tela de gestão.
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
     * vale até no preview), órfãs ignoradas e filtro de permissão aplicado.
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

            foreach ($secao['items'] as $item) {
                if (! $item['ativo']) {
                    continue;
                }

                $children = array_values(array_filter(
                    $item['children'] ?? [],
                    fn (array $filho): bool => $podeVer($filho['permission'] ?? null),
                ));

                if ($children === [] && ! $podeVer($item['permission'] ?? null)) {
                    continue;
                }

                $itens[] = array_merge($item, ['children' => $children]);
            }

            if ($itens !== []) {
                $secoes[] = array_merge($secao, ['items' => $itens]);
            }
        }

        return $secoes;
    }

    /**
     * Estrutura completa para a tela de gestão: inclui itens inativos e as
     * personalizações órfãs (key que não existe mais no config).
     *
     * @return array{secoes: list<array<string, mixed>>, orfas: Collection<int, MenuPersonalizacao>}
     */
    public function estruturaParaGestao(): array
    {
        $chavesItens = $this->chavesDeItens();
        $chavesSecoes = $this->chavesDeSecoes();

        $orfas = $this->personalizacoes()
            ->filter(fn (MenuPersonalizacao $personalizacao): bool => ! in_array(
                $personalizacao->key,
                $personalizacao->tipo === TipoPersonalizacaoMenu::Item ? $chavesItens : $chavesSecoes,
                true,
            ))
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
     * @return list<string>
     */
    public function chavesDeSecoes(): array
    {
        return array_column($this->registro(), 'key');
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

        $porChave = $this->personalizacoes()->keyBy(
            fn (MenuPersonalizacao $personalizacao): string => $personalizacao->tipo->value . ':' . $personalizacao->key,
        );

        $secoes = [];

        foreach ($registro as $indice => $secao) {
            $ajuste = $porChave->get('secao:' . $secao['key']);

            $secoes[$secao['key']] = [
                'key' => $secao['key'],
                'title' => $ajuste->label ?? $secao['title'],
                'titlePadrao' => $secao['title'],
                'personalizado' => $ajuste !== null,
                'posicao' => [$ajuste->ordem ?? PHP_INT_MAX, $indice],
                'items' => [],
            ];
        }

        $indiceNatural = 0;

        foreach ($registro as $secao) {
            foreach ($secao['items'] as $item) {
                $ajuste = $porChave->get('item:' . $item['key']);

                // Seção destino só vale se ainda existir no config; senão o
                // item volta para a seção natural (sem quebrar a sidebar).
                $destino = $ajuste?->secao_key !== null && isset($secoes[$ajuste->secao_key])
                    ? $ajuste->secao_key
                    : $secao['key'];

                $secoes[$destino]['items'][] = array_merge($item, [
                    'label' => $ajuste->label ?? $item['label'],
                    'labelPadrao' => $item['label'],
                    'icon' => $ajuste->icone ?? $item['icon'],
                    'iconPadrao' => $item['icon'],
                    'ativo' => $ajuste->ativo ?? true,
                    'secaoNaturalKey' => $secao['key'],
                    'personalizado' => $ajuste !== null,
                    'posicao' => [$ajuste->ordem ?? PHP_INT_MAX, $indiceNatural],
                ]);

                $indiceNatural++;
            }
        }

        // Ordena por [ordem personalizada, posição natural no config]: item
        // novo de módulo futuro cai no fim de uma seção já reordenada.
        $secoes = array_values($secoes);
        usort($secoes, fn (array $a, array $b): int => $a['posicao'] <=> $b['posicao']);

        foreach ($secoes as &$secao) {
            usort($secao['items'], fn (array $a, array $b): int => $a['posicao'] <=> $b['posicao']);
            unset($secao['posicao']);

            foreach ($secao['items'] as &$item) {
                unset($item['posicao']);
            }
            unset($item);
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
