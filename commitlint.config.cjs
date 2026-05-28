/**
 * Alinhado a docs/devops/conventions.md (tipos §1.2, escopos §1.3).
 */
module.exports = {
    extends: ['@commitlint/config-conventional'],
    rules: {
        'type-enum': [
            2,
            'always',
            [
                'feat',
                'fix',
                'refactor',
                'docs',
                'style',
                'test',
                'chore',
                'perf',
                'ci',
                'revert',
            ],
        ],
        'scope-enum': [
            2,
            'always',
            [
                'admin',
                'adesao',
                'auth',
                'docs',
                'financeiro',
                'gateway',
                'infra',
                'models',
                'portal',
                'skills',
                'squad',
                'ui',
            ],
        ],
        'scope-empty': [2, 'never'],
        'subject-case': [0],
        'subject-max-length': [2, 'always', 72],
        'subject-full-stop': [2, 'never', '.'],
        'header-max-length': [2, 'always', 72],
    },

    prompt: {
        messages: {
            skip: '(opcional, Enter)',
            max: 'máximo %d caracteres na primeira linha',
            min: 'mínimo %d caracteres',
            emptyWarning: 'não pode ficar vazio',
            upperLimitWarning: 'acima do limite · edite antes de confirmar',
            lowerLimitWarning: 'abaixo do mínimo',
        },
        questions: {
            type: {
                description: 'Tipo de mudança (portal ArtFinal):',
                enum: {
                    feat: {
                        description: 'Nova funcionalidade',
                        title: 'feat',
                        emoji: '✨',
                    },
                    fix: {
                        description: 'Correção de bug',
                        title: 'fix',
                        emoji: '🐛',
                    },
                    refactor: {
                        description: 'Refatoração sem mudar comportamento',
                        title: 'refactor',
                        emoji: '📦',
                    },
                    docs: {
                        description: 'Só documentação',
                        title: 'docs',
                        emoji: '📚',
                    },
                    style: {
                        description: 'Formatação, estilo · sem mudar lógica',
                        title: 'style',
                        emoji: '💎',
                    },
                    test: {
                        description: 'Testes novos ou ajustados',
                        title: 'test',
                        emoji: '🚨',
                    },
                    chore: {
                        description: 'Manutenção, deps, tooling',
                        title: 'chore',
                        emoji: '♻️',
                    },
                    perf: {
                        description: 'Performance',
                        title: 'perf',
                        emoji: '🚀',
                    },
                    ci: {
                        description: 'CI/CD, pipelines',
                        title: 'ci',
                        emoji: '⚙️',
                    },
                    revert: {
                        description: 'Reverte um commit anterior',
                        title: 'revert',
                        emoji: '🗑',
                    },
                },
            },
            scope: {
                description:
                    'Escopo (lista fechada); migrations/schema/seeders ⇒ models — nunca database',
            },
            subject: {
                description:
                    'Resumo em pt-BR, imperativo, minúscula após dois-pontos, sem ponto final (≤72 chars no cabeçalho)',
            },
            body: {
                description: 'Corpo opcional em pt-BR (detalhes, bullets)',
            },
            isBreaking: {
                description: 'Inclui breaking change?',
            },
            breakingBody: {
                description: 'Descrição obrigatória do breaking change',
            },
            breaking: {
                description: 'Descrição curta do breaking change',
            },
            isIssueAffected: {
                description: 'Referencia issue Plane/GitHub?',
            },
            issuesBody: {
                description: 'Detalhar fechamento de issues se necessário',
            },
            issues: {
                description: 'Refs (ex.: PAF-123, #45)',
            },
        },
    },
};
