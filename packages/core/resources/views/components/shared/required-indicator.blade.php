{{--
    Indicador visual de campo obrigatório (asterisco vermelho).

    O "*" é uma convenção PURAMENTE VISUAL para usuários videntes. A obrigatoriedade
    real é comunicada a tecnologias assistivas pelo atributo required/aria-required do
    próprio controle do campo. Por isso o glifo recebe aria-hidden="true": sem ele,
    leitores de tela anunciam "asterisco"/"estrela" — ruído sem significado semântico.

    Fonte única de verdade do indicador para todos os componentes de campo do design
    system (input, select, date-picker, toggle, etc.) — evita a duplicação do markup.
--}}
<span {{ $attributes->merge(['class' => 'text-danger', 'aria-hidden' => 'true']) }}>*</span>
