<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

// O data-table (x-admin.table.*) é o componente de listagem central do ERP, presente
// em toda Index/listagem. Seus ícones são puramente visuais: a lupa de busca reforça
// um <input> que já tem aria-label, o toggle de densidade vive num <button> com
// aria-label/title, e o spinner do overlay duplica o estado já comunicado por
// wire:loading. Nenhum carrega informação para o leitor de tela.

function assertTodosIconesAriaHidden(string $html, int $esperado): void
{
    // Os ícones do data-table são uma mistura de <i> (lupa) e <span> (densidade/spinner).
    preg_match_all('/<(?:i|span)\b[^>]*iconify[^>]*>/', $html, $todos);
    preg_match_all('/<(?:i|span)\b[^>]*iconify[^>]*aria-hidden="true"[^>]*>/', $html, $comHidden);

    expect($todos[0])->toHaveCount($esperado);
    expect($comHidden[0])->toHaveCount(count($todos[0]));
}

it('marca os ícones decorativos da toolbar como aria-hidden', function (): void {
    $html = Blade::render('<x-admin.table.toolbar />');

    // Lupa de busca (o input tem aria-label) + toggle de densidade (button com
    // aria-label="Alternar densidade da tabela" e title). Ambos decorativos.
    assertTodosIconesAriaHidden($html, 2);
});

it('marca o spinner do overlay de carregamento da tabela como aria-hidden', function (): void {
    $html = Blade::render('<x-admin.table.table><tbody></tbody></x-admin.table.table>');

    // O estado de carregamento é comunicado por wire:loading; o spinner é decorativo.
    assertTodosIconesAriaHidden($html, 1);
});
