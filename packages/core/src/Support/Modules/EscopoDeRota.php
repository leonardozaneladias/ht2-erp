<?php

declare(strict_types=1);

namespace HT2ML\Core\Support\Modules;

/**
 * Onde as rotas de um módulo entram.
 *
 * Até 2026-08-28 havia um só destino: o grupo autenticado de /admin. Funciona
 * para tela de cadastro e não funciona para mais nada — um módulo de pagamentos
 * precisa de um endpoint que o gateway chama SEM sessão e SEM CSRF, e um módulo
 * de matrícula precisa de uma página que o responsável abre sem estar logado.
 * Sem canal, os dois viram edição no `routes/web.php` do produto, que é
 * exatamente a dependência de mão dupla que o ADR-0022 proíbe.
 *
 * Os três escopos são fechados de propósito. "Deixe o módulo declarar o
 * middleware que quiser" seria mais flexível e devolveria o problema: um módulo
 * poderia registrar `/admin/qualquer-coisa` sem autenticação nenhuma e ninguém
 * veria. Aqui, o escopo diz o que a rota é, e o core decide o que isso implica.
 */
enum EscopoDeRota: string
{
    public function rotulo(): string
    {
        return match ($this) {
            self::Admin => 'painel autenticado',
            self::Publico => 'público (stack web, sem login)',
            self::Webhook => 'webhook (sem sessão, sem CSRF)',
        };
    }
    /**
     * Dentro do grupo autenticado /admin: herda prefixo, name "admin." e todo
     * o middleware do painel (tenant, 2FA, inatividade). É o default, e o que
     * todo CRUD usa.
     */
    case Admin = 'admin';

    /**
     * Stack `web` completa — sessão, cookies, CSRF — sem autenticação e sem
     * prefixo. Para páginas que o público abre: matrícula, consulta, portal.
     * O módulo escolhe o próprio prefixo de URL.
     */
    case Publico = 'publico';

    /**
     * Sem sessão e sem CSRF, porque quem chama é uma máquina que não tem
     * cookie para mandar. Prefixo `/webhooks` e name `webhooks.` são impostos:
     * um endpoint sem autenticação precisa ser reconhecível no `route:list`, e
     * o prefixo impede que ele apareça em qualquer lugar da aplicação.
     */
    case Webhook = 'webhook';
}
