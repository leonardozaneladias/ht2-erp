import 'preline';
import 'simplebar';

// Livewire PowerGrid — bundle pre-compilado (self-contained, registra os
// helpers Alpine pgRenderActions/pgRowAttributes/etc. no alpine:init).
import '../../vendor/power-components/livewire-powergrid/dist/powergrid.js';

import momentModule from 'moment';
import './admin/preline';
import './admin/sidebar';
import './admin/theme';
import './admin/appearance-preview';
import './admin/fullscreen';
import './admin/row-actions';
import './admin/toast';
import './admin/validation-errors';
import './admin/unsaved-guard';
import './admin/confirm';
import './admin/combobox';
import './admin/forms';
import './admin/avatar-cropper';
import './admin/sortable';
import './admin/charts';
import './admin/copy';

// Vite 8 (Rolldown): normaliza o interop CJS/UMD do default export do moment.
const moment = momentModule?.default ?? momentModule;
window.moment = moment;

// Sem Lucide: os ícones da base são todos `iconify tabler--*` (via @iconify/tailwind4),
// e `data-lucide` não tem uma única ocorrência no projeto. O import trazia o barrel de
// ícones inteiro — que, por ser um objeto, o tree-shaking não conseguia podar.
