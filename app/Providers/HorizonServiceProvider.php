<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('example@example.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');
    }

    /**
     * Register the Horizon gate.
     *
     * Integrado ao RBAC do admin: a permissão `sistema.horizon` decide o acesso
     * (super-admin passa pelo bypass do AccessResolver). As rotas do Horizon usam
     * o grupo `web`, então o usuário chega pelo guard admin via sessão.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null): bool {
            $admin = auth('admin')->user();

            return $admin instanceof \HT2ML\Core\Models\AdminUser && $admin->can('sistema.horizon');
        });
    }
}
