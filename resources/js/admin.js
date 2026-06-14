import 'preline';
import 'simplebar';

// Livewire PowerGrid — bundle pre-compilado (self-contained, registra os
// helpers Alpine pgRenderActions/pgRowAttributes/etc. no alpine:init).
import '../../vendor/power-components/livewire-powergrid/dist/powergrid.js';

// Folhas vendor com `@charset "UTF-8";` no topo. Importadas via JS (pipeline do
// Vite) em vez de @import no CSS do Tailwind, para o @charset ser tratado pelo
// Vite e nao gerar warning no lightningcss interno do Tailwind.
import 'animate.css/animate.min.css';
import 'plyr/dist/plyr.css';

import { createIcons, icons } from 'lucide';
import moment from 'moment';
import './admin/preline';
import './admin/sidebar';
import './admin/theme';
import './admin/fullscreen';
import './admin/row-actions';
import './admin/toast';
import './admin/confirm';
import './admin/combobox';
import './admin/forms';
import './admin/avatar-cropper';
import './admin/sortable';
import './admin/charts';
import './admin/copy';

window.moment = moment;

createIcons({ icons });
