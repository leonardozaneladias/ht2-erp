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
  resolve: {
    // Colapsa todas as referencias a estes pacotes numa unica instancia.
    // Sem isso, datatables.net-dt (core 2.3.3 aninhado) e as extensoes Buttons
    // (core 2.3.4 hoisted) acabam em objetos DataTable distintos: a extensao
    // acopla `.Buttons` numa instancia e o `new DataTable()` usa a outra,
    // derrubando o export (jszip/pdfMake) e o admin.js inteiro.
    dedupe: ['jquery', 'datatables.net'],
  },
  build: {
    chunkSizeWarningLimit: 2500,
    rollupOptions: {
      output: {
        manualChunks(id) {
          if (!id.includes('node_modules')) return;

          if (id.includes('apexcharts')) return 'vendor-charts';
          if (id.includes('moment')) return 'vendor-moment';
          if (id.includes('datatables') || id.includes('jquery')) return 'vendor-datatables';
          if (id.includes('flatpickr') || id.includes('choices') || id.includes('sortablejs')) return 'vendor-forms';
          if (id.includes('preline')) return 'vendor-preline';
          if (id.includes('pdfmake')) return 'vendor-pdfmake';
          if (id.includes('jszip')) return 'vendor-jszip';
          if (id.includes('sweetalert2')) return 'vendor-sweetalert';
          if (id.includes('dropzone') || id.includes('inputmask')) return 'vendor-forms';

          return 'vendor';
        },
      },
    },
  },
});
