import 'preline';
import 'simplebar';

import { createIcons, icons } from 'lucide';
import moment from 'moment';
import './admin/toast';
import './admin/confirm';
import './admin/forms';
import './admin/data-table';
import './admin/sortable';
import './admin/charts';
import './admin/copy';

window.moment = moment;

createIcons({ icons });
