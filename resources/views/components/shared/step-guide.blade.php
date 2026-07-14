{{-- Timeline vertical de instruções — par com x-shared.step-guide-item (mesmo
     padrão de composição de accordion/accordion-item). Cada item pode embutir a
     ação contextual do passo (botão/link) no slot nomeado `action`. --}}
<ol {{ $attributes }}>
    {{ $slot }}
</ol>
