# Models base com HasUlid e relacionamentos

**ID:** STORY-011
**Epic:** F1-E4 — Modelos e banco de dados
**Priority:** Must Have
**Story Points:** 3
**Status:** Not Started
**Skills:** `laravel-best-practices`, `eloquent-best-practices`, `php-best-practices`

## User Story

Como **desenvolvedor da equipe Portal ArtFinal**
Quero **ter os models base de identidade e cadastro implementados com a trait HasUlid, relacionamentos corretos e factories com dados em pt_BR**
Para que **toda a camada de serviços, actions e testes possam manipular entidades tipadas sem depender de dados externos**

## Acceptance Criteria

- [ ] Trait `app/Models/Concerns/HasUlid.php` existe, gera ULID automaticamente no evento `creating` via `\Symfony\Component\Uid\Ulid::generate()` (ou `Str::ulid()`) e define `getRouteKeyName(): string` retornando `'ulid'`
- [ ] `app/Models/Acesso/AdminUser.php` existe, implementa `Authenticatable` (extends `Illuminate\Foundation\Auth\User`), usa `HasUlid`, `HasFactory`, `SoftDeletes`, tem `$fillable`, `$hidden` e `$casts` corretos
- [ ] `app/Models/Acesso/PortalUser.php` existe, implementa `Authenticatable`, usa `HasUlid`, `HasFactory`, tem `belongsTo(Turma::class)`, `$fillable`, `$hidden`, `$casts` corretos
- [ ] `app/Models/Acesso/ComissaoUser.php` existe, implementa `Authenticatable`, usa `HasUlid`, `HasFactory`, tem `belongsTo(Turma::class)`, `$fillable`, `$hidden`, `$casts` corretos
- [ ] `app/Models/Cadastro/Organizacao.php` existe, usa `HasUlid`, `HasFactory`, tem `hasMany(Instituicao::class)`, `$fillable`, `$casts`
- [ ] `app/Models/Cadastro/Instituicao.php` existe, usa `HasUlid`, `HasFactory`, tem `belongsTo(Organizacao::class)` e `hasMany(Curso::class)`, `$fillable`, `$casts`
- [ ] `app/Models/Cadastro/Curso.php` existe, usa `HasUlid`, `HasFactory`, tem `belongsTo(Instituicao::class)` e `hasMany(Turma::class)`, `$fillable`, `$casts`
- [ ] `app/Models/Cadastro/Turma.php` existe, usa `HasUlid`, `HasFactory`, tem `belongsTo(Curso::class)`, `hasMany(Evento::class)`, `hasMany(PortalUser::class)`, `$fillable`, `$casts`
- [ ] `app/Models/Cadastro/Evento.php` existe, usa `HasUlid`, `HasFactory`, tem `belongsTo(Turma::class)`, `$fillable`, `$casts`
- [ ] Factories criadas para todos os 8 models com dados pt_BR via Faker (nomes, emails, CNPJs brasileiros via `laravellegends/pt-br-validator` faker provider)
- [ ] `AdminUserFactory` usa `fake()->name()`, senha bcrypt, `active: true` por padrão e state `inactive()` disponível
- [ ] `TurmaFactory` gera `codigo` único no formato `YYYY-SIGLA-NNN` (ex: `2025-ADM-001`) e `ano_formatura` entre 2025 e 2030
- [ ] Todos os models têm `declare(strict_types=1)` e return types em todos os métodos
- [ ] `getRouteKeyName()` retorna `'ulid'` em todos os models (via `HasUlid`)
- [ ] `./vendor/bin/pint --dirty` passa sem alterações
- [ ] `./vendor/bin/phpstan analyse --level=6` sem erros

## Technical Notes

### Arquivos a criar/modificar

- `app/Models/Concerns/HasUlid.php` — trait compartilhada por todos os models
- `app/Models/Acesso/AdminUser.php`
- `app/Models/Acesso/PortalUser.php`
- `app/Models/Acesso/ComissaoUser.php`
- `app/Models/Cadastro/Organizacao.php`
- `app/Models/Cadastro/Instituicao.php`
- `app/Models/Cadastro/Curso.php`
- `app/Models/Cadastro/Turma.php`
- `app/Models/Cadastro/Evento.php`
- `database/factories/Acesso/AdminUserFactory.php`
- `database/factories/Acesso/PortalUserFactory.php`
- `database/factories/Acesso/ComissaoUserFactory.php`
- `database/factories/Cadastro/OrganizacaoFactory.php`
- `database/factories/Cadastro/InstituicaoFactory.php`
- `database/factories/Cadastro/CursoFactory.php`
- `database/factories/Cadastro/TurmaFactory.php`
- `database/factories/Cadastro/EventoFactory.php`
- `config/auth.php` — adicionar guards `admin` e `portal` apontando para seus respectivos providers

### Observações técnicas

**Trait HasUlid:**

```php
declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** @mixin Model */
trait HasUlid
{
    public static function bootHasUlid(): void
    {
        static::creating(function (Model $model): void {
            if (empty($model->ulid)) {
                $model->ulid = (string) Str::ulid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }
}
```

**AdminUser — estrutura mínima:**

```php
declare(strict_types=1);

namespace App\Models\Acesso;

use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

final class AdminUser extends Authenticatable
{
    use HasFactory, HasUlid, SoftDeletes;

    protected $fillable = ['ulid', 'name', 'email', 'password', 'mfa_secret', 'active'];

    protected $hidden = ['password', 'remember_token', 'mfa_secret'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'active'            => 'boolean',
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }
}
```

**Guards em config/auth.php:**

```php
'guards' => [
    'admin' => [
        'driver'   => 'session',
        'provider' => 'admin_users',
    ],
    'portal' => [
        'driver'   => 'sanctum',
        'provider' => 'portal_users',
    ],
    // ...
],
'providers' => [
    'admin_users' => [
        'driver' => 'eloquent',
        'model'  => \App\Models\Acesso\AdminUser::class,
    ],
    'portal_users' => [
        'driver' => 'eloquent',
        'model'  => \App\Models\Acesso\PortalUser::class,
    ],
],
```

**Factories com dados pt_BR:**

```php
// TurmaFactory
public function definition(): array
{
    $ano = fake()->numberBetween(2025, 2030);
    $sigla = strtoupper(fake()->lexify('???'));
    $num = fake()->numerify('###');

    return [
        'ulid'           => (string) \Illuminate\Support\Str::ulid(),
        'curso_id'       => \App\Models\Cadastro\Curso::factory(),
        'name'           => fake('pt_BR')->company().' '.$ano,
        'codigo'         => "{$ano}-{$sigla}-{$num}",
        'ano_formatura'  => $ano,
        'meta_formandos' => fake()->numberBetween(30, 200),
        'active'         => true,
    ];
}
```

**Localidade do Faker:** usar `fake('pt_BR')` para dados como nomes, empresas, endereços. Para CPF/CNPJ usar o provider do `laravellegends/pt-br-validator`.

**Namespaces de factories:** Laravel resolve factories por convenção. Se os models estão em `App\Models\Acesso\AdminUser`, a factory correspondente deve estar em `Database\Factories\Acesso\AdminUserFactory` e o model deve usar `newFactory()` override:

```php
protected static function newFactory(): AdminUserFactory
{
    return \Database\Factories\Acesso\AdminUserFactory::new();
}
```

**$casts como método:** no Laravel 11+, `$casts` é definido como método `protected function casts(): array` (não propriedade). Seguir esta convenção.

**Relacionamentos com return type explícito:**

```php
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

public function curso(): BelongsTo
{
    return $this->belongsTo(Curso::class);
}

public function eventos(): HasMany
{
    return $this->hasMany(Evento::class);
}
```

## Dependencies

- **Blocked by:** STORY-009 (tabelas de identidade), STORY-010 (tabelas de cadastro)
- **Blocks:** STORY-008 (MeController retorna PortalUser completo), F1-E5 (Enums e DTOs usam models)

## Testing Requirements

- [ ] Teste unit: `HasUlid` gera ULID automaticamente no `creating` event
- [ ] Teste unit: `AdminUser::create([...])` persiste e `ulid` não é nulo
- [ ] Teste unit: `Turma::factory()->create()` cria a hierarquia completa (Organizacao → Instituicao → Curso → Turma)
- [ ] Teste unit: `Turma::with('eventos')->first()` não dispara N+1 (usar `assertQueryCount`)
- [ ] Teste unit: `PortalUser::factory()->create()->turma` retorna instância de `Turma`
- [ ] Teste architecture (Pest arch): todos os models em `App\Models` implementam `getRouteKeyName()` retornando `'ulid'`
- [ ] Teste architecture: nenhum model em `App\Models\Acesso` expõe `password` em `$fillable`
- [ ] `php artisan test --compact --filter=STORY011` verde
