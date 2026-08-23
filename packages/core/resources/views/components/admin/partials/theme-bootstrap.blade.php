@php
    // Defaults da instância (AppearanceSettings) — fonte da verdade do servidor.
    // O sessionStorage só sobrescreve a preferência de sessão (dark mode, colapso).
    $appearanceConfig = app(\HT2ML\Core\Services\Admin\Settings\AppearanceService::class)->paraThemeConfig();
@endphp

{{-- Script crítico de tema: precisa rodar antes de CSS/JS para evitar FOUC no shell admin. --}}
<script>
    (function () {
        const html = document.documentElement;
        const storageKey = '__THEME_CONFIG__';
        const savedConfig = sessionStorage.getItem(storageKey);

        const defaultConfig = @json ($appearanceConfig);

        function getSystemTheme() {
            return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }

        const htmlConfig = {
            dir: html.getAttribute('dir') || defaultConfig.dir,
            skin: html.getAttribute('data-skin') || defaultConfig.skin,
            theme:
                html.getAttribute('data-theme') === 'system'
                    ? getSystemTheme()
                    : html.getAttribute('data-theme') ||
                      (defaultConfig.theme === 'system' ? getSystemTheme() : defaultConfig.theme),
            'topbar-color': html.getAttribute('data-topbar-color') || defaultConfig['topbar-color'],
            'sidenav-color': html.getAttribute('data-menu-color') || defaultConfig['sidenav-color'],
            'sidenav-size': html.getAttribute('data-sidenav-size') || defaultConfig['sidenav-size'],
            'sidenav-user': html.hasAttribute('data-sidenav-user') || defaultConfig['sidenav-user'],
            position: html.getAttribute('data-layout-position') || defaultConfig.position,
            width: html.getAttribute('data-layout-width') || defaultConfig.width,
        };

        window.defaultConfig = structuredClone(htmlConfig);

        const config = savedConfig ? JSON.parse(savedConfig) : htmlConfig;
        window.config = config;

        html.setAttribute('dir', config.dir);
        html.setAttribute('data-skin', config.skin);
        html.setAttribute('data-theme', config.theme);
        html.setAttribute('data-topbar-color', config['topbar-color']);
        html.setAttribute('data-menu-color', config['sidenav-color']);
        html.setAttribute('data-layout-position', config.position);
        html.setAttribute('data-layout-width', config.width);

        if (config['sidenav-user'] === true) {
            html.setAttribute('data-sidenav-user', 'true');
        } else {
            html.removeAttribute('data-sidenav-user');
        }

        if (config['sidenav-size']) {
            let size = config['sidenav-size'];

            if (window.innerWidth <= 1140) {
                size = 'offcanvas';
            }

            html.setAttribute('data-sidenav-size', size);
        }
    })();
</script>
