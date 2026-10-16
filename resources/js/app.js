import Chart from 'chart.js/auto';

const isDark = () => document.documentElement.classList.contains('dark');

function chartTokens() {
    const dark = isDark();

    return {
        tekst: dark ? '#a1a1aa' : '#52525b',
        raster: dark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.08)',
    };
}

function readConfig(canvas) {
    try {
        return JSON.parse(canvas.dataset.chart ?? '{}');
    } catch {
        return {};
    }
}

function initCharts() {
    const tokens = chartTokens();

    Chart.defaults.font.family = "'Instrument Sans', ui-sans-serif, system-ui, sans-serif";
    Chart.defaults.color = tokens.tekst;

    document.querySelectorAll('canvas[data-chart-type]').forEach((canvas) => {
        if (canvas.dataset.chartInitialized === 'true') {
            return;
        }

        const config = readConfig(canvas);
        const type = canvas.dataset.chartType;

        canvas.dataset.chartInitialized = 'true';

        if (type === 'doughnut') {
            new Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels: config.labels ?? [],
                    datasets: [{
                        data: config.data ?? [],
                        backgroundColor: config.kleuren ?? ['#ef4444', '#f59e0b'],
                        borderWidth: 0,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '62%',
                    animation: false,
                    interaction: { mode: null },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            onClick: () => {},
                            labels: {
                                color: tokens.tekst,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                padding: 16,
                            },
                        },
                        tooltip: { enabled: false },
                    },
                },
            });

            return;
        }

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: config.labels ?? [],
                datasets: [{
                    label: 'Open tickets',
                    data: config.data ?? [],
                    backgroundColor: '#6366f1',
                    borderRadius: 6,
                    maxBarThickness: 42,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                interaction: { mode: null },
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: false },
                },
                scales: {
                    x: {
                        ticks: { color: tokens.tekst },
                        grid: { display: false },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { color: tokens.tekst, precision: 0 },
                        grid: { color: tokens.raster },
                    },
                },
            },
        });
    });
}

document.addEventListener('DOMContentLoaded', initCharts);
document.addEventListener('livewire:navigated', initCharts);
