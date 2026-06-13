<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TipoPersonalizacaoMenu;
use App\Models\Concerns\Auditavel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Personalização do menu lateral por cima do registro em config/admin-menu.php.
 *
 * Global por instalação (sem empresa). Campos null herdam o padrão do config;
 * uma key que não existe mais no config é uma personalização órfã (ignorada
 * na sidebar, listada na tela de gestão).
 *
 * @property TipoPersonalizacaoMenu $tipo
 * @property string $key
 * @property string|null $label
 * @property string|null $icone
 * @property string|null $secao_key
 * @property string|null $grupo_key
 * @property int|null $ordem
 * @property bool $ativo
 * @property bool $e_custom
 */
class MenuPersonalizacao extends Model
{
    use Auditavel;

    /** @use HasFactory<\Database\Factories\MenuPersonalizacaoFactory> */
    use HasFactory;

    protected $table = 'menu_personalizacoes';

    protected $fillable = [
        'tipo',
        'key',
        'label',
        'icone',
        'secao_key',
        'grupo_key',
        'ordem',
        'ativo',
        'e_custom',
    ];

    public function rotuloAuditoria(): string
    {
        return $this->label ?? $this->key;
    }

    protected function casts(): array
    {
        return [
            'tipo' => TipoPersonalizacaoMenu::class,
            'ordem' => 'integer',
            'ativo' => 'boolean',
            'e_custom' => 'boolean',
        ];
    }
}
