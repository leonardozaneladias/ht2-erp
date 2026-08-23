@props ([
    'name',
    'id' => null,
    'value',
    'label',
    'description' => null,
    'checked' => false,
])

@php
    $fieldId = $id ?: \Illuminate\Support\Str::of(sprintf('%s-%s', $name, $value))->replace(['[]', '[', ']', '.'], ['', '-', '', '-'])->trim('-')->toString();
    $currentValue = old($name);
    $isChecked = $currentValue !== null
        ? (string) $currentValue === (string) $value
        : ($checked || $attributes->has('checked'));
    $describedBy = $description ? "{$fieldId}-description" : null;
@endphp

<label
    class="border-default-300 bg-card hover:border-primary/40 hover:bg-primary/5 flex cursor-pointer items-start gap-3 rounded-xl border px-4 py-3 transition"
>
    <input
        id="{{ $fieldId }}"
        name="{{ $name }}"
        type="radio"
        value="{{ $value }}"
        {{
$attributes->class([
            'form-radio mt-0.5 text-primary focus:ring-primary',
        ])
}}
        @checked ($isChecked)
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
    />

    <span class="min-w-0">
        <span class="text-body-color block text-sm font-medium">{{ $label }}</span>

        @if ($description)
            <span class="text-default-400 mt-1 block text-xs" id="{{ $describedBy }}">{{ $description }}</span>
        @endif
    </span>
</label>
