<?php

declare(strict_types=1);

return [
    'logo_path' => env('APP_BRANDING_LOGO', 'images/logo.png'),
    'logo_dark_path' => env('APP_BRANDING_LOGO_DARK', 'images/logo-black.png'),
    'logo_sm_path' => env('APP_BRANDING_LOGO_SM', 'images/logo-sm.png'),
    'avatar_default' => env('APP_BRANDING_AVATAR_DEFAULT', 'images/users/user-default.jpg'),
    'user_default_name' => env('APP_BRANDING_USER_DEFAULT_NAME', 'Convidado'),
    'user_default_role' => env('APP_BRANDING_USER_DEFAULT_ROLE', 'Preview local'),
];
