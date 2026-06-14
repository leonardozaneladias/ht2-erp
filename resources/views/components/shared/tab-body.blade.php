@props ([
    // Superfície conectada às abas (x-shared.tab-nav). Reaproveita a classe
    // .card (bg, borda e raio do tema), quadra o topo e remove a borda superior:
    // a linha-base da tab-nav vira a aresta de cima e a aba ativa "abre" aqui
    // sem emenda. Os x-shared.tab-panel ficam dentro; o padding vem daqui.
    'padding' => true,
])

<div {{
$attributes->class([
        'card rounded-t-none border-t-0',
        'p-5' => $padding,
    ])
}}>
    {{ $slot }}
</div>
