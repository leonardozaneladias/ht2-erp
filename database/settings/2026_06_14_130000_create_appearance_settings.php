<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Cria o grupo de settings "appearance" (aparência/layout do Inspinia).
 *
 * Defaults idênticos aos atributos data-* hoje fixos em layout.blade.php, para
 * que instalações existentes não percebam mudança visual ao migrar. Roda em
 * instalações novas (migrate:fresh) e existentes (apenas esta migração nova).
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('appearance.tema_padrao', 'light');
        $this->migrator->add('appearance.skin', 'default');
        $this->migrator->add('appearance.topbar_color', 'light');
        $this->migrator->add('appearance.menu_color', 'dark');
        $this->migrator->add('appearance.layout_width', 'fluid');
        $this->migrator->add('appearance.sidenav_size_padrao', 'default');
        $this->migrator->add('appearance.sidenav_user', true);
        $this->migrator->add('appearance.permitir_preferencia_usuario', true);
    }
};
