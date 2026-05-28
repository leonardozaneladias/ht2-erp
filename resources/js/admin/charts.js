import ApexCharts from 'apexcharts';

const charts = new Map();

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

function resolveThemeColor(token) {
  if (typeof token !== 'string') {
    return token;
  }

  if (token.startsWith('#') || token.startsWith('rgb') || token.startsWith('hsl')) {
    return token;
  }

  const variableName = token.startsWith('--') ? token : `--${token}`;
  const computed = getComputedStyle(document.documentElement).getPropertyValue(variableName).trim();

  return computed || token;
}

function normalizeColors(colors = []) {
  return colors.map(resolveThemeColor);
}

function buildBaseConfig(config) {
  const height = Number(config.height || 320);

  return {
    chart: {
      type: 'bar',
      height,
      toolbar: { show: false },
      zoom: { enabled: false },
      animations: { easing: 'easeinout', speed: 320 },
    },
    series: [],
    stroke: { curve: 'smooth', width: 3 },
    dataLabels: { enabled: false },
    legend: { show: true, position: 'top' },
    tooltip: { shared: true, intersect: false },
    grid: {
      borderColor: resolveThemeColor('--color-default-200'),
      strokeDashArray: 4,
    },
    colors: normalizeColors(config.colors || ['--color-primary', '--color-info', '--color-success', '--color-warning']),
    ...config.options,
  };
}

function normalizeChartConfig(config) {
  const base = buildBaseConfig(config);
  const type = config.type || 'bar';

  if (type === 'line') {
    return {
      ...base,
      chart: { ...base.chart, type: 'line' },
      series: config.series || [],
      xaxis: { categories: config.categories || [] },
    };
  }

  if (type === 'column') {
    return {
      ...base,
      chart: { ...base.chart, type: 'bar' },
      plotOptions: {
        ...(base.plotOptions || {}),
        bar: {
          horizontal: false,
          columnWidth: '60%',
          borderRadius: 4,
          borderRadiusApplication: 'end',
          ...((base.plotOptions || {}).bar || {}),
        },
      },
      series: config.series || [],
      xaxis: { categories: config.categories || [] },
    };
  }

  if (type === 'pie' || type === 'donut') {
    return {
      ...base,
      chart: { ...base.chart, type },
      series: config.series || [],
      labels: config.labels || [],
      legend: { ...base.legend, position: 'bottom' },
      dataLabels: {
        enabled: true,
        formatter: (value) => `${Number(value).toFixed(1)}%`,
      },
      plotOptions: {
        ...(base.plotOptions || {}),
        pie: {
          donut: {
            size: '65%',
            labels: {
              show: type === 'donut',
              total: {
                show: type === 'donut',
                label: 'Total',
                formatter: (w) => w.globals.seriesTotals.reduce((total, item) => total + item, 0),
              },
            },
          },
        },
      },
      tooltip: {
        y: {
          formatter: (value) => `${new Intl.NumberFormat('pt-BR').format(value)}`,
        },
      },
    };
  }

  return {
    ...base,
    chart: { ...base.chart, type: 'bar' },
    plotOptions: {
      ...(base.plotOptions || {}),
      bar: {
        horizontal: true,
        borderRadius: 4,
        dataLabels: { position: 'top' },
        ...((base.plotOptions || {}).bar || {}),
      },
    },
    series: config.series || [],
    xaxis: { categories: config.categories || [] },
  };
}

function renderChart(host) {
  const chartId = host.id || host.dataset.afChartId;

  if (!chartId) {
    return;
  }

  const config = normalizeChartConfig(parseJsonDataset(host, 'afChart'));

  if (charts.has(chartId)) {
    charts.get(chartId).updateOptions(config, true, true);
    return;
  }

  const chart = new ApexCharts(host, config);
  chart.render();
  charts.set(chartId, chart);
}

function initCharts(root = document) {
  collectElements(root, '[data-af-chart]').forEach((host) => {
    host.dataset.afChartBound = 'true';
    renderChart(host);
  });
}

function bindChartUpdateEvent() {
  if (window.__afChartUpdateBound) {
    return;
  }

  const handler = (payload = {}) => {
    const detail = payload?.detail ?? payload;
    const chartId = detail.chartId;

    if (!chartId) {
      return;
    }

    const host = document.getElementById(chartId);

    if (!host) {
      return;
    }

    const currentConfig = parseJsonDataset(host, 'afChart');
    const mergedConfig = {
      ...currentConfig,
      ...(detail.data || {}),
      type: detail.type || detail.data?.type || currentConfig.type,
    };

    host.dataset.afChart = JSON.stringify(mergedConfig);
    renderChart(host);
  };

  window.addEventListener('chart-update', handler);

  if (window.Livewire?.on) {
    window.Livewire.on('chart-update', handler);
  }

  window.__afChartUpdateBound = true;
}

function bootCharts() {
  initCharts(document);
  bindChartUpdateEvent();

  document.addEventListener('livewire:navigated', () => initCharts(document));
  document.addEventListener('livewire:init', () => bindChartUpdateEvent(), { once: true });

  const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      mutation.addedNodes.forEach((node) => {
        if (node instanceof HTMLElement) {
          initCharts(node);
        }
      });
    });
  });

  observer.observe(document.body, { childList: true, subtree: true });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', bootCharts, { once: true });
} else {
  bootCharts();
}
