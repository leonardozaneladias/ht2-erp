import 'preline';
import 'simplebar';

// Folhas vendor com `@charset "UTF-8";` no topo. Importadas via JS (pipeline do
// Vite) em vez de @import no CSS do Tailwind, para o @charset ser tratado pelo
// Vite e nao gerar warning no lightningcss interno do Tailwind.
import 'animate.css/animate.min.css';
import 'plyr/dist/plyr.css';

import { createIcons, icons } from 'lucide';
import moment from 'moment';
import './admin/sidebar';
import './admin/toast';
import './admin/confirm';
import './admin/forms';
import './admin/sortable';
import './admin/charts';
import './admin/copy';

window.moment = moment;

createIcons({ icons });
