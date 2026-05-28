import $ from 'jquery';
import DataTable from 'datatables.net-dt';
import 'datatables.net-buttons-dt';
import 'datatables.net-buttons/js/buttons.html5.mjs';
import 'datatables.net-buttons/js/buttons.print.mjs';
import 'datatables.net-responsive-dt';
import 'datatables.net-select-dt';
import JSZip from 'jszip';
import pdfMake from 'pdfmake/build/pdfmake';
import * as pdfFonts from 'pdfmake/build/vfs_fonts';

import 'datatables.net-dt/css/dataTables.dataTables.css';
import 'datatables.net-buttons-dt/css/buttons.dataTables.css';
import 'datatables.net-responsive-dt/css/responsive.dataTables.css';
import 'datatables.net-select-dt/css/select.dataTables.css';

window.$ = window.jQuery = $;
window.JSZip = JSZip;
window.pdfMake = pdfMake;

DataTable.Buttons.jszip(JSZip);

const pdfVfs = pdfFonts?.default?.pdfMake?.vfs || pdfFonts?.default?.vfs || pdfFonts?.pdfMake?.vfs || pdfFonts?.vfs;

if (pdfVfs) {
  pdfMake.vfs = pdfVfs;
  DataTable.Buttons.pdfMake(pdfMake);
}

const DATATABLE_LANGUAGE = {
  emptyTable: 'Nenhum registro disponível',
  info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
  infoEmpty: 'Mostrando 0 a 0 de 0 registros',
  infoFiltered: '(filtrado de _MAX_ registros no total)',
  lengthMenu: 'Mostrar _MENU_ registros',
  loadingRecords: 'Carregando...',
  processing: 'Processando...',
  search: 'Buscar:',
  zeroRecords: 'Nenhum registro encontrado',
  paginate: {
    first: 'Primeiro',
    last: 'Último',
    next: 'Próximo',
    previous: 'Anterior',
  },
  buttons: {
    copy: 'Copiar',
    csv: 'CSV',
    excel: 'Excel',
    pdf: 'PDF',
    print: 'Imprimir',
  },
};

let observerStarted = false;

function collectElements(root, selector) {
  const elements = [];

  if (root?.matches?.(selector)) {
    elements.push(root);
  }

  if (root?.querySelectorAll) {
    elements.push(...root.querySelectorAll(selector));
  }

  return elements;
}

function parseJsonDataset(element, key) {
  try {
    return JSON.parse(element.dataset[key] || '{}');
  } catch (error) {
    console.warn(`Configuracao invalida em ${key}`, error);
    return {};
  }
}

function normalizeDateValue(rawValue) {
  const value = String(rawValue ?? '')
    .replace(/<[^>]*>/g, '')
    .trim();

  if (!value) {
    return null;
  }

  if (/^\d{2}\/\d{2}\/\d{4}$/.test(value)) {
    const [day, month, year] = value.split('/').map(Number);
    return Date.UTC(year, month - 1, day);
  }

  const timestamp = Date.parse(value);
  return Number.isNaN(timestamp) ? null : timestamp;
}

function buildExportButtons() {
  const baseClass = 'btn bg-light text-dark hover:text-primary';

  return [
    { extend: 'csvHtml5', text: 'CSV', className: baseClass, exportOptions: { columns: ':visible' } },
    { extend: 'excelHtml5', text: 'Excel', className: baseClass, exportOptions: { columns: ':visible' } },
    { extend: 'pdfHtml5', text: 'PDF', className: baseClass, exportOptions: { columns: ':visible' } },
    { extend: 'print', text: 'Imprimir', className: baseClass, exportOptions: { columns: ':visible' } },
  ];
}

function bindExternalSearch(root, tableApi, config) {
  const searchInput = root.querySelector('[data-af-datatable-search]');

  if (!searchInput || config.searchable === false) {
    return;
  }

  searchInput.addEventListener('input', () => {
    tableApi.search(searchInput.value).draw();
  });
}

function bindColumnSearch(root, tableApi) {
  root.querySelectorAll('[data-af-datatable-column-search]').forEach((input) => {
    if (input.dataset.afDatatableBound === 'true') {
      return;
    }

    const columnIndex = Number(input.dataset.afDatatableColumnSearch);

    input.addEventListener('input', () => {
      tableApi.column(columnIndex).search(input.value).draw();
    });

    input.dataset.afDatatableBound = 'true';
  });
}

function bindRangeFilter(root, tableApi, config) {
  if (!config.dateRange || !Number.isInteger(config.dateRangeColumn)) {
    return;
  }

  const startInput = root.querySelector('[data-af-datatable-range-start]');
  const endInput = root.querySelector('[data-af-datatable-range-end]');

  if (!startInput || !endInput) {
    return;
  }

  const filter = (settings, data) => {
    if (settings.nTable !== tableApi.table().node()) {
      return true;
    }

    const start = normalizeDateValue(startInput.value);
    const end = normalizeDateValue(endInput.value);
    const current = normalizeDateValue(data[config.dateRangeColumn]);

    if (current === null) {
      return true;
    }

    if (start !== null && current < start) {
      return false;
    }

    if (end !== null && current > end) {
      return false;
    }

    return true;
  };

  $.fn.dataTable.ext.search.push(filter);

  const redraw = () => tableApi.draw();

  startInput.addEventListener('change', redraw);
  endInput.addEventListener('change', redraw);
}

function getRowCheckboxes(root) {
  return Array.from(root.querySelectorAll('tbody [data-af-datatable-row-select]'));
}

function bindSelection(root, tableApi, config) {
  if (!config.selectable) {
    return;
  }

  const bulkContainer = root.querySelector('[data-af-datatable-bulk]');
  const countElement = root.querySelector('[data-af-datatable-selected-count]');
  const selectAll = root.querySelector('[data-af-datatable-select-all]');

  const syncSelectionState = () => {
    const rowCheckboxes = getRowCheckboxes(root);
    const selected = rowCheckboxes.filter((checkbox) => checkbox.checked);

    if (selectAll) {
      selectAll.checked = rowCheckboxes.length > 0 && selected.length === rowCheckboxes.length;
      selectAll.indeterminate = selected.length > 0 && selected.length < rowCheckboxes.length;
    }

    if (countElement) {
      countElement.textContent = String(selected.length);
    }

    if (bulkContainer) {
      bulkContainer.classList.toggle('hidden', selected.length === 0);
    }

    rowCheckboxes.forEach((checkbox) => {
      checkbox.closest('tr')?.classList.toggle('bg-primary/5', checkbox.checked);
    });

    root.dispatchEvent(
      new CustomEvent('datatable-selection-change', {
        bubbles: true,
        detail: {
          count: selected.length,
          values: selected.map((checkbox) => checkbox.value).filter(Boolean),
        },
      }),
    );
  };

  const bindRowCheckboxes = () => {
    getRowCheckboxes(root).forEach((checkbox) => {
      if (checkbox.dataset.afDatatableBound === 'true') {
        return;
      }

      checkbox.addEventListener('change', syncSelectionState);
      checkbox.dataset.afDatatableBound = 'true';
    });
  };

  if (selectAll && selectAll.dataset.afDatatableBound !== 'true') {
    selectAll.addEventListener('change', () => {
      getRowCheckboxes(root).forEach((checkbox) => {
        checkbox.checked = selectAll.checked;
      });

      syncSelectionState();
    });

    selectAll.dataset.afDatatableBound = 'true';
  }

  bindRowCheckboxes();
  syncSelectionState();
  tableApi.on('draw', () => {
    bindRowCheckboxes();
    syncSelectionState();
  });
}

function mountExportButtons(root, tableApi, config) {
  if (!config.exportable || !tableApi.buttons) {
    return;
  }

  const target = root.querySelector('[data-af-datatable-export-buttons]');

  if (!target) {
    return;
  }

  target.innerHTML = '';
  $(target).append(tableApi.buttons().container());
}

function buildColumnDefs(columns) {
  return columns.map((column, index) => ({
    targets: index,
    orderable: column.orderable !== false,
    searchable: column.searchable !== false,
    className: column.className ?? '',
  }));
}

function initDataTable(root) {
  if (root.dataset.afDatatableInitialized === 'true') {
    return;
  }

  const tableElement = root.querySelector('[data-af-datatable-table]');

  if (!tableElement) {
    return;
  }

  const config = parseJsonDataset(root, 'afDatatable');
  const instance = new DataTable(tableElement, {
    autoWidth: false,
    pageLength: Number(config.perPage || 25),
    responsive: config.responsive !== false,
    searching: config.searchable !== false,
    ordering: true,
    info: true,
    orderCellsTop: Boolean(config.columnSearch),
    language: DATATABLE_LANGUAGE,
    columnDefs: buildColumnDefs(config.columns || []),
    buttons: config.exportable ? buildExportButtons() : [],
    layout: {
      topStart: null,
      topEnd: null,
      bottomStart: 'info',
      bottomEnd: 'paging',
    },
  });

  bindExternalSearch(root, instance, config);
  bindColumnSearch(root, instance);
  bindRangeFilter(root, instance, config);
  bindSelection(root, instance, config);
  mountExportButtons(root, instance, config);

  root.dataset.afDatatableInitialized = 'true';
  root._afDatatable = instance;
}

function bootAdminDataTables(root = document) {
  collectElements(root, '[data-af-datatable]').forEach(initDataTable);

  if (observerStarted || !document.body) {
    return;
  }

  observerStarted = true;

  const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      mutation.addedNodes.forEach((node) => {
        if (!(node instanceof HTMLElement)) {
          return;
        }

        bootAdminDataTables(node);
      });
    });
  });

  observer.observe(document.body, {
    childList: true,
    subtree: true,
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => bootAdminDataTables());
} else {
  bootAdminDataTables();
}

document.addEventListener('livewire:navigated', () => bootAdminDataTables());

window.initAdminDataTables = bootAdminDataTables;
