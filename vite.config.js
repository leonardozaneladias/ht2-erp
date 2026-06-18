import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/css/admin.css', 'resources/js/admin.js'],
      refresh: true,
    }),
    tailwindcss(),
  ],
  // Vite 8 (Rolldown) mudou o interop de módulos CommonJS: libs UMD/CJS importadas
  // como default (Inputmask, Choices em resources/js/admin/forms.js, etc.) passaram a
  // resolver como `{ default: ... }`, quebrando `new X()` em runtime
  // ("X.default is not a constructor"). Esta flag restaura o interop do Vite 7.
  // É compat temporário (deprecado pelo Vite): migrar os imports e removê-la é follow-up.
  legacy: {
    inconsistentCjsInterop: true,
  },
  // Dev server acessível atrás do DDEV (HMR via *.ddev.site:5173).
  server: {
    host: '0.0.0.0',
    port: 5173,
    strictPort: true,
    origin: `${process.env.DDEV_PRIMARY_URL_WITHOUT_PORT}:5173`,
    cors: {
      origin: /https?:\/\/([A-Za-z0-9\-.]+)?(\.ddev\.site)(?::\d+)?$/,
    },
  },
  build: {
    chunkSizeWarningLimit: 2500,
    rollupOptions: {
      output: {
        manualChunks(id) {
          if (!id.includes('node_modules')) return;

          if (id.includes('apexcharts')) return 'vendor-charts';
          if (id.includes('moment')) return 'vendor-moment';
          if (id.includes('flatpickr') || id.includes('choices') || id.includes('sortablejs')) return 'vendor-forms';
          if (id.includes('preline')) return 'vendor-preline';
          if (id.includes('sweetalert2')) return 'vendor-sweetalert';
          if (id.includes('dropzone') || id.includes('inputmask')) return 'vendor-forms';
          // Chunk próprio: o avatar-cropper importa dinamicamente — só carrega no 1º uso.
          if (id.includes('cropperjs')) return 'vendor-cropper';

          return 'vendor';
        },
      },
    },
  },
});
