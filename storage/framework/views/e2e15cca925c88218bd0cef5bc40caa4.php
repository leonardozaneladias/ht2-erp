
<script>
    (function () {
        const html = document.documentElement;
        const storageKey = '__THEME_CONFIG__';
        const savedConfig = sessionStorage.getItem(storageKey);

        const defaultConfig = {
            dir: 'ltr',
            skin: 'default',
            theme: 'light',
            width: 'fluid',
            position: 'fixed',
            orientation: 'vertical',
            'sidenav-size': 'default',
            'sidenav-user': true,
            'topbar-color': 'light',
            'sidenav-color': 'dark',
        };

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
<?php /**PATH /Users/Shared/projects/GDF/erp/resources/views/components/admin/partials/theme-bootstrap.blade.php ENDPATH**/ ?>