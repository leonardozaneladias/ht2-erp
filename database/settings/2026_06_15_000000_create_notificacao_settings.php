<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Semeia os valores de fábrica do grupo "notificacoes" (aparência/comportamento
 * dos toasts e confirmações). Padrões: pílula no topo central, duração média.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('notificacoes.toast_posicao', 'top-center');
        $this->migrator->add('notificacoes.toast_duracao', 'media');
        $this->migrator->add('notificacoes.toast_estilo', 'pilula');
        $this->migrator->add('notificacoes.toast_maximo', 3);
        $this->migrator->add('notificacoes.confirmacao_posicao', 'center');
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('notificacoes.toast_posicao');
        $this->migrator->deleteIfExists('notificacoes.toast_duracao');
        $this->migrator->deleteIfExists('notificacoes.toast_estilo');
        $this->migrator->deleteIfExists('notificacoes.toast_maximo');
        $this->migrator->deleteIfExists('notificacoes.confirmacao_posicao');
    }
};
