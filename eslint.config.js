import js from '@eslint/js';
import prettier from 'eslint-config-prettier';
import globals from 'globals';

export default [
  js.configs.recommended,
  prettier,
  {
    languageOptions: {
      ecmaVersion: 'latest',
      sourceType: 'module',
      globals: {
        ...globals.browser,
        ...globals.node,
        ...globals.es2024,
      },
    },
    rules: {
      'no-console': 'warn',
      'no-unused-vars': 'warn',
      'prefer-const': 'error',
      'no-var': 'error',
    },
  },
  {
    ignores: [
      'node_modules/',
      'vendor/',
      'public/build/',
      'public/vendor/',
      'storage/',
      'bootstrap/cache/',
      'laradock/',
      '**/*.min.js',
      // Inspinia template files — will be audited/pruned in Task 16.
      // Our own code goes in resources/js/admin.js and resources/js/portal.js.
      'resources/js/app.js',
      'resources/js/vendor.js',
      'resources/js/pages/',
      'resources/js/data/',
      'resources/js/maps/',
      // Skills de terceiros (BMAD, composio, adr-skill, etc.) — código
      // externo consumido via Claude Code; não sujeito ao lint do projeto.
      '.agents/',
      '.continue/',
      '.junie/',
    ],
  },
];
