<?php

declare(strict_types=1);

namespace HT2ML\Core\Enums\Admin;

use Illuminate\Support\Str;

/**
 * Grupos de configuração do sistema.
 *
 * O `value` casa com o `group` das classes em App\Settings e com a tabela
 * `settings` (coluna group). A ordem dos cases define a ordem das abas.
 */
enum SettingsGroup: string
{
    public function label(): string
    {
        return match ($this) {
            self::GERAL => 'Empresa',
            self::BRANDING => 'Marca e tema',
            self::APARENCIA => 'Aparência',
            self::LOGIN => 'Tela de login',
            self::EMAIL => 'E-mail',
            self::LOCALIZACAO => 'Localização',
            self::SEGURANCA => 'Segurança',
            self::NOTIFICACOES => 'Notificações',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::GERAL => 'tabler--building',
            self::BRANDING => 'tabler--palette',
            self::APARENCIA => 'tabler--brush',
            self::LOGIN => 'tabler--login-2',
            self::EMAIL => 'tabler--mail',
            self::LOCALIZACAO => 'tabler--world',
            self::SEGURANCA => 'tabler--shield-lock',
            self::NOTIFICACOES => 'tabler--bell',
        };
    }

    public function descricao(): string
    {
        return match ($this) {
            self::GERAL => 'Dados do cliente dono desta instalação.',
            self::BRANDING => 'Logotipos, favicon, nome do sistema e cores do tema.',
            self::APARENCIA => 'Tema, skin, cores da barra e do menu, e largura do layout.',
            self::LOGIN => 'Aparência e textos da página de login.',
            self::EMAIL => 'Servidor SMTP usado para enviar e-mails.',
            self::LOCALIZACAO => 'Idioma, fuso horário, moeda e formatos.',
            self::SEGURANCA => 'Política de senha, sessão e retenção de logs.',
            self::NOTIFICACOES => 'Posição, duração e estilo das notificações e confirmações.',
        };
    }

    /**
     * Palavras-chave para a busca de configurações (encontra seções por campos
     * internos, não só pelo rótulo). Sem acento/maiúsculas na comparação.
     *
     * @return array<int, string>
     */
    public function palavrasChave(): array
    {
        return match ($this) {
            self::GERAL => ['empresa', 'cliente', 'cnpj', 'razao social', 'inscricao estadual', 'endereco', 'telefone', 'cep', 'site'],
            self::BRANDING, self::APARENCIA => ['tema', 'cor', 'cores', 'paleta', 'logo', 'logotipo', 'favicon', 'icone', 'skin', 'escuro', 'claro', 'menu', 'barra', 'topbar', 'marca', 'branding', 'preset'],
            self::LOGIN => ['login', 'tela de login', 'fundo', 'titulo', 'subtitulo'],
            self::EMAIL => ['e-mail', 'email', 'smtp', 'servidor', 'remetente', 'porta', 'senha smtp'],
            self::LOCALIZACAO => ['idioma', 'fuso', 'fuso horario', 'timezone', 'moeda', 'formato', 'data', 'decimais'],
            self::SEGURANCA => ['senha', 'sessao', 'timeout', '2fa', 'auditoria', 'retencao', 'logs', 'lockout', 'tentativas', 'bloqueio'],
            self::NOTIFICACOES => ['notificacao', 'notificacoes', 'toast', 'alerta', 'aviso', 'mensagem', 'posicao', 'duracao', 'confirmacao', 'feedback', 'popup'],
        };
    }

    /** Casa o termo de busca contra rótulo + descrição + palavras-chave (sem acento). */
    public function correspondeBusca(string $termo): bool
    {
        $normalizar = static fn (string $valor): string => Str::lower(Str::ascii($valor));

        $alvo = $normalizar(implode(' ', [$this->label(), $this->descricao(), ...$this->palavrasChave()]));

        return str_contains($alvo, $normalizar(trim($termo)));
    }

    /**
     * @return array<int, self>
     */
    public static function abas(): array
    {
        return [
            self::GERAL,
            self::APARENCIA,
            self::LOGIN,
            self::EMAIL,
            self::LOCALIZACAO,
            self::SEGURANCA,
            self::NOTIFICACOES,
        ];
    }
    case GERAL = 'general';
    case BRANDING = 'branding';
    case APARENCIA = 'appearance';
    case LOGIN = 'login';
    case EMAIL = 'email';
    case LOCALIZACAO = 'localizacao';
    case SEGURANCA = 'seguranca';
    case NOTIFICACOES = 'notificacoes';
}
