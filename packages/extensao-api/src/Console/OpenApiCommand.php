<?php

declare(strict_types=1);

namespace HT2ML\Api\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use JsonException;

/**
 * Grava, ou confere, a especificação OpenAPI versionada do produto.
 *
 *     php artisan api:openapi              # regera config('api.openapi.arquivo')
 *     php artisan api:openapi --verificar  # falha se o arquivo não bate com o código
 *
 * O --verificar é o portão de CI: um endpoint que mudou sem regerar o arquivo
 * reprova o build. A comparação ignora `servers`, que o Scramble deriva do
 * APP_URL — o arquivo gerado na máquina de quem escreveu e o gerado no CI
 * teriam URLs diferentes, e o portão reprovaria por um motivo que não é
 * contrato.
 */
final class OpenApiCommand extends Command
{
    protected $signature = 'api:openapi {--verificar : Só compara, sem gravar; sai com erro se divergir}';

    protected $description = 'Gera ou verifica o arquivo OpenAPI da API';

    public function handle(): int
    {
        $arquivo = base_path((string) config('api.openapi.arquivo', 'docs/openapi.json'));

        if (! $this->option('verificar')) {
            Artisan::call('scramble:export', ['--path' => $arquivo]);
            $this->components->info("OpenAPI gravado em {$arquivo}.");

            return self::SUCCESS;
        }

        if (! is_file($arquivo)) {
            $this->components->error("{$arquivo} não existe. Rode `php artisan api:openapi` e versione o arquivo.");

            return self::FAILURE;
        }

        $gerado = (string) tempnam(sys_get_temp_dir(), 'openapi-');

        try {
            Artisan::call('scramble:export', ['--path' => $gerado]);

            $iguais = self::normalizar((string) file_get_contents($arquivo))
                === self::normalizar((string) file_get_contents($gerado));
        } catch (JsonException $e) {
            $this->components->error('JSON inválido: ' . $e->getMessage());

            return self::FAILURE;
        } finally {
            @unlink($gerado);
        }

        if (! $iguais) {
            $this->components->error("{$arquivo} não descreve mais a API. Rode `php artisan api:openapi` e versione o resultado.");

            return self::FAILURE;
        }

        $this->components->info('OpenAPI em dia com o código.');

        return self::SUCCESS;
    }

    /** @throws JsonException */
    private static function normalizar(string $json): string
    {
        /** @var array<string, mixed> $documento */
        $documento = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        unset($documento['servers']);

        return json_encode(self::ordenar($documento), JSON_THROW_ON_ERROR);
    }

    private static function ordenar(mixed $valor): mixed
    {
        if (! is_array($valor)) {
            return $valor;
        }

        if (! array_is_list($valor)) {
            ksort($valor);
        }

        return array_map(self::ordenar(...), $valor);
    }
}
