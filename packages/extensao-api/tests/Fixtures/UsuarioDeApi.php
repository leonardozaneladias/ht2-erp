<?php

declare(strict_types=1);

namespace HT2ML\Api\Tests\Fixtures;

use HT2ML\Api\Contracts\PodeSerDesativado;
use HT2ML\Api\Contracts\TemIdiomaPreferido;
use Illuminate\Foundation\Auth\User;
use Laravel\Sanctum\HasApiTokens;

/**
 * Um usuário de API neutro, sem domínio nenhum, para os testes da extensão.
 * Não é persistido: os testes usam Sanctum::actingAs() com a instância.
 */
final class UsuarioDeApi extends User implements PodeSerDesativado, TemIdiomaPreferido
{
    use HasApiTokens;

    public ?string $idioma = null;

    public bool $ativo = true;

    public static function com(int $id, ?string $idioma = null, bool $ativo = true): self
    {
        $usuario = new self;
        $usuario->id = $id;
        $usuario->idioma = $idioma;
        $usuario->ativo = $ativo;

        return $usuario;
    }

    public function idiomaPreferido(): ?string
    {
        return $this->idioma;
    }

    public function estaAtivo(): bool
    {
        return $this->ativo;
    }
}
