<div>
    <form wire:submit="salvar" class="grid gap-6">
        <x-shared.card title="Idioma e fuso" subtitle="Aplicados a toda a aplicação.">
            <div class="grid gap-x-4 md:grid-cols-2">
                <x-shared.select-search
                    name="idioma"
                    label="Idioma"
                    wire:model="idioma"
                    :value="$idioma"
                    :placeholder="null"
                    :options="$idiomas"
                />
                <x-shared.select-search
                    name="timezone"
                    label="Fuso horário"
                    wire:model="timezone"
                    :value="$timezone"
                    :placeholder="null"
                    :options="$timezones"
                />
            </div>
        </x-shared.card>

        <x-shared.card title="Formatos" subtitle="Como datas e valores monetários são exibidos.">
            <div class="grid gap-x-4 md:grid-cols-3">
                <x-shared.select-search
                    name="formato_data"
                    label="Formato de data"
                    wire:model="formato_data"
                    :value="$formato_data"
                    :placeholder="null"
                    :options="$formatos"
                />
                <x-shared.input
                    name="moeda_simbolo"
                    label="Símbolo da moeda"
                    wire:model="moeda_simbolo"
                    placeholder="R$"
                />
                <x-shared.select-search
                    name="casas_decimais"
                    label="Casas decimais"
                    wire:model="casas_decimais"
                    :value="$casas_decimais"
                    :placeholder="null"
                    :options="$opcoesCasas"
                />
            </div>
        </x-shared.card>

        <div class="flex justify-end">
            <x-shared.loading-button target="salvar" icon="tabler--device-floppy">
                Salvar localização
            </x-shared.loading-button>
        </div>
    </form>
</div>
