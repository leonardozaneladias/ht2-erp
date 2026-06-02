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

          return 'vendor';
        },
      },
    },
  },
});
