<?php

declare(strict_types=1);

namespace HT2ML\Documentos\Enums;

/**
 * Tipos de documento com numeração sequencial gerenciada por empresa/ano.
 * Cada case expõe um template padrão com placeholders {ANO} e {SEQ:N}.
 */
enum TipoDocumento: string
{
    public function label(): string
    {
        return match ($this) {
            self::Matricula => 'Matrícula',
            self::Contrato => 'Contrato',
            self::Protocolo => 'Protocolo',
            self::Recibo => 'Recibo',
        };
    }

    /**
     * Template padrão para geração do número formatado.
     * Placeholders: {ANO} = ano com 4 dígitos, {SEQ:N} = sequencial com N dígitos.
     */
    public function template(): string
    {
        return match ($this) {
            self::Matricula => 'MAT-{ANO}-{SEQ:6}',
            self::Contrato => 'CTR-{ANO}-{SEQ:6}',
            self::Protocolo => 'PRT-{ANO}-{SEQ:6}',
            self::Recibo => 'RCB-{ANO}-{SEQ:6}',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
    case Matricula = 'matricula';
    case Contrato = 'contrato';
    case Protocolo = 'protocolo';
    case Recibo = 'recibo';
}
