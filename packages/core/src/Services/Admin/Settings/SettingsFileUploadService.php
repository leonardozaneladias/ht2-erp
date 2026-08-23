<?php

declare(strict_types=1);

namespace HT2ML\Core\Services\Admin\Settings;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Grava um arquivo de configuração no disco público e remove o anterior,
 * evitando acúmulo de órfãos. Reutilizado por branding e tela de login.
 */
final class SettingsFileUploadService
{
    public function substituir(UploadedFile $arquivo, string $anterior, string $diretorio): string
    {
        $caminho = $arquivo->store($diretorio, 'public');

        if ($anterior !== '' && $anterior !== $caminho) {
            Storage::disk('public')->delete($anterior);
        }

        return $caminho;
    }
}
