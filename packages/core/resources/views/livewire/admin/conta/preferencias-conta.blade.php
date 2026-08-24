<x-shared.card
    class="rounded-t-none border-t-0"
    title="Preferências"
    subtitle="Idioma e fuso horário aplicados à sua experiência."
>
    <form wire:submit="salvar" class="grid max-w-md gap-4">
        <x-shared.select-search
            name="locale"
            label="Idioma"
            wire:model="locale"
            :value="$locale"
            :options="$locales"
            placeholder="Padrão da instância"
        />

        <x-shared.select-search
            name="timezone"
            label="Fuso horário"
            wire:model="timezone"
            :value="$timezone"
            :options="$timezones"
            placeholder="Padrão da instância"
        />

        <div class="flex justify-end">
            <x-shared.loading-button target="salvar" icon="tabler--device-floppy">
                Salvar preferências</x-shared.loading-button
            >
        </div>
    </form>
</x-shared.card>
