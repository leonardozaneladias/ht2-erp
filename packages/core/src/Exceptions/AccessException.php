<?php

declare(strict_types=1);

namespace HT2ML\Core\Exceptions;

use RuntimeException;

final class AccessException extends RuntimeException
{
    public static function ultimoSuperAdmin(): self
    {
        return new self('Não é possível remover ou desativar o último super-admin do sistema.');
    }

    public static function autoRemocaoGestao(): self
    {
        return new self('Você não pode remover de si mesmo as permissões de gestão de acesso.');
    }

    public static function roleProtegida(string $role): self
    {
        return new self("O perfil \"{$role}\" é protegido e não pode ser alterado ou excluído.");
    }

    public static function denyEmSuperAdmin(): self
    {
        return new self('Não é possível negar permissões de um super-admin.');
    }

    public static function foraDaHierarquia(): self
    {
        return new self('Você não tem hierarquia suficiente para gerir este usuário ou perfil.');
    }

    public static function conviteInvalido(): self
    {
        return new self('Este convite é inválido, já foi utilizado ou expirou. Solicite um novo ao administrador.');
    }
}
