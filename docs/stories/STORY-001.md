# Instalar e configurar pacotes Composer base

**ID:** STORY-001  
**Epic:** F1-E1 — Setup & Configuração  
**Priority:** Must Have  
**Story Points:** 3  
**Status:** Not Started  
**Skills:** `laravel-best-practices`, `laravel-packages`

## User Story

Como **desenvolvedor do Portal ArtFinal**
Quero **ter todos os pacotes Composer base instalados e configurados corretamente**
Para que **as demais stories da Fundação possam depender dessas bibliotecas sem conflitos de versão ou configuração faltante**

## Acceptance Criteria

- [ ] `laravel/sanctum` instalado — config publicada em `config/sanctum.php`, migration `personal_access_tokens` disponível
- [ ] `spatie/laravel-data` instalado — sem configuração extra obrigatória neste momento
- [ ] `saloonphp/laravel-plugin` instalado — sem configuração extra obrigatória neste momento
- [ ] `sentry/sentry-laravel` instalado — `config/sentry.php` publicada, variável `SENTRY_LARAVEL_DSN` adicionada ao `.env.example` com valor vazio
- [ ] `league/flysystem-aws-s3-v3` instalado — sem publicação de config (usa `config/filesystems.php` existente)
- [ ] `laravellegends/pt-br-validator` instalado — provider `LaravelLegends\PtBrValidator\Providers\ValidationServiceProvider` registrado em `bootstrap/providers.php`
- [ ] `spatie/laravel-medialibrary` instalado — `config/media-library.php` publicada, migration `media` disponível
- [ ] `bootstrap/providers.php` contém todos os providers na ordem correta: `AppServiceProvider`, `HorizonServiceProvider`, `ValidationServiceProvider` (pt-br-validator)
- [ ] `php artisan config:clear && php artisan config:show app.name` executa sem erros após as alterações
- [ ] `./vendor/bin/pint --dirty` passa sem alterações
- [ ] `./vendor/bin/phpstan analyse --level=6` sem erros neste arquivo

## Technical Notes

### Arquivos a criar/modificar

- `composer.json` — adicionar dependências via `composer require`
- `bootstrap/providers.php` — registrar `LaravelLegends\PtBrValidator\Providers\ValidationServiceProvider`
- `config/sanctum.php` — publicado via `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"`
- `config/sentry.php` — publicado via `php artisan vendor:publish --provider="Sentry\Laravel\ServiceProvider"`
- `config/media-library.php` — publicado via `php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-config"`
- `.env.example` — adicionar `SENTRY_LARAVEL_DSN=` ao bloco de monitoramento

### Observações técnicas

- Executar todos os `composer require` em um único comando para evitar resolver dependências múltiplas vezes:
    ```bash
    composer require laravel/sanctum spatie/laravel-data saloonphp/laravel-plugin \
      sentry/sentry-laravel league/flysystem-aws-s3-v3 \
      laravellegends/pt-br-validator spatie/laravel-medialibrary
    ```
- O `laravellegends/pt-br-validator` **não** usa auto-discovery em todas as versões — verificar manualmente se o provider foi registrado.
- O `spatie/laravel-medialibrary` requer a coluna `conversions_disk` na migration — confirmar que a versão instalada é compatível com PostgreSQL 16 (sem colunas JSON não suportadas).
- O `league/flysystem-aws-s3-v3` é uma dependência de driver de storage; nenhum provider manual é necessário — o `FilesystemServiceProvider` do Laravel já o detecta.
- O `sentry/sentry-laravel` registra seu provider via auto-discovery; não adicionar manualmente ao `bootstrap/providers.php` para evitar duplicata.
- Não rodar `php artisan migrate` nesta story — migrations serão executadas na STORY-004 e F1-E4.

## Dependencies

- **Blocked by:** Nenhuma
- **Blocks:** STORY-002, STORY-003, STORY-004, STORY-005

## Testing Requirements

- [ ] `php artisan test --compact --filter=STORY001` verde
- [ ] Teste de smoke: `php artisan tinker --execute 'echo class_exists(\Spatie\LaravelData\Data::class) ? "OK" : "FAIL";'` retorna `OK`
- [ ] Teste de smoke: `php artisan tinker --execute 'echo class_exists(\Saloon\Laravel\SaloonServiceProvider::class) ? "OK" : "FAIL";'` retorna `OK`
- [ ] Teste de smoke: `php artisan tinker --execute 'echo class_exists(\Spatie\MediaLibrary\MediaLibraryServiceProvider::class) ? "OK" : "FAIL";'` retorna `OK`
- [ ] `php artisan config:clear` executa sem exceções
