/**
 * Dashboard Charts Module
 * 
 * Consolidated ApexCharts functionality for dashboard pages.
 * Used by admin-dashboard and leader-dashboard.
 */

// Chart instances (global for resize handling)
window.trainingChart = null;
window.donutTypeChart = null;
window.donutGroupChart = null;

/**
 * Initialize the main training area chart
 * @param {Array} data - Monthly training data
 * @param {Array} labels - Month labels
 * @param {Object} livewireComponent - Livewire component reference for callbacks
 */
function initTrainingChart(data, labels, livewireComponent) {
    const chartElement = document.querySelector("#training-chart");
    if (!chartElement) return;

    // Clear any existing chart
    chartElement.innerHTML = '';

    const options = {
        chart: {
            type: 'area',
            height: 350,
            fontFamily: 'inherit',
            redrawOnParentResize: true,
            redrawOnWindowResize: true,
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 400,
                dynamicAnimation: {
                    enabled: true,
                    speed: 350
                }
            },
            toolbar: {
                show: false
            },
            zoom: {
                enabled: false
            },
            dropShadow: {
                enabled: true,
                top: 3,
                left: 0,
                blur: 4,
                opacity: 0.15,
                color: '#6366f1'
            },
            events: {
                // Handle click on data point
                dataPointSelection: function(event, chartContext, config) {
                    const monthIndex = config.dataPointIndex;
                    if (livewireComponent) {
                        livewireComponent.call('selectMonth', monthIndex);
                    }
                },
                // Handle click on marker
                markerClick: function(event, chartContext, { seriesIndex, dataPointIndex, config }) {
                    if (livewireComponent) {
                        livewireComponent.call('selectMonth', dataPointIndex);
                    }
                }
            }
        },
        series: [{
            name: 'Training Count',
            data: data
        }],
        stroke: {
            curve: 'smooth',
            width: 3,
            colors: ['#6366f1']
        },
        colors: ['#6366f1'],
        fill: {
            type: 'gradient',
            gradient: {
                shade: 'light',
                type: 'vertical',
                shadeIntensity: 0.5,
                gradientToColors: ['#6366f1'],
                opacityFrom: 0.5,
                opacityTo: 0.05,
                stops: [0, 100]
            }
        },
        xaxis: {
            categories: labels,
            labels: {
                style: {
                    colors: '#9ca3af',
                    fontSize: '12px'
                }
            },
            axisBorder: {
                show: false
            },
            axisTicks: {
                show: false
            },
            crosshairs: {
                show: true,
                stroke: {
                    color: '#6366f1',
                    width: 1,
                    dashArray: 3
                }
            }
        },
        yaxis: {
            labels: {
                style: {
                    colors: '#9ca3af',
                    fontSize: '12px'
                },
                formatter: function(val) {
                    return Math.floor(val);
                }
            },
            min: 0
        },
        grid: {
            borderColor: '#e5e7eb',
            strokeDashArray: 4,
            xaxis: {
                lines: {
                    show: false
                }
            }
        },
        markers: {
            size: 5,
            colors: ['#6366f1'],
            strokeColors: '#fff',
            strokeWidth: 2,
            hover: {
                size: 8,
                sizeOffset: 3
            }
        },
        tooltip: {
            enabled: true,
            shared: false,
            intersect: true,
            theme: 'light',
            style: {
                fontSize: '12px'
            },
            y: {
                formatter: function(val) {
                    return val + ' Trainings';
                }
            },
            marker: {
                show: true
            }
        },
        dataLabels: {
            enabled: false
        },
        responsive: [{
            breakpoint: 768,
            options: {
                chart: {
                    height: 280
                },
                markers: {
                    size: 4
                }
            }
        }]
    };

    const chart = new ApexCharts(chartElement, options);
    chart.render();

    // Store chart instance for later use
    window.trainingChart = chart;
}

/**
 * Render donut chart for training types (In-House/Out-House)
 * @param {Object} data - Type breakdown data { 'IN': count, 'OUT': count }
 */
function renderDonutTypeChart(data) {
    const el = document.querySelector('#donut-type-chart');
    if (!el) return;

    // Destroy existing chart
    if (window.donutTypeChart) {
        window.donutTypeChart.destroy();
    }

    // Map type labels for display
    const typeLabels = {
        'IN': 'In-House',
        'OUT': 'Out-House',
    };

    const labels = Object.keys(data).map(k => typeLabels[k] || k || 'Unspecified');
    const series = Object.values(data);

    const options = {
        series: series,
        labels: labels,
        chart: {
            type: 'donut',
            height: 220,
            fontFamily: 'inherit',
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 400,
            }
        },
        colors: ['#4863a0', '#f59e0b', '#10b981', '#ef4444', '#8b5cf6', '#06b6d4'],
        plotOptions: {
            pie: {
                donut: {
                    size: '60%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Total',
                            fontSize: '12px',
                            fontWeight: 600,
                            color: '#6b7280'
                        }
                    }
                }
            }
        },
        dataLabels: {
            enabled: false
        },
        legend: {
            position: 'bottom',
            fontSize: '12px',
            markers: {
                width: 10,
                height: 10,
                radius: 2
            }
        },
        stroke: {
            width: 2,
            colors: ['#fff']
        },
        tooltip: {
            y: {
                formatter: function(val) {
                    return val + ' Trainings';
                }
            }
        },
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    height: 180
                },
                legend: {
                    position: 'bottom'
                }
            }
        }]
    };

    window.donutTypeChart = new ApexCharts(el, options);
    window.donutTypeChart.render();
}

/**
 * Render donut chart for group company breakdown
 * @param {Object} data - Group company breakdown data { 'COMPANY_A': count, ... }
 */
function renderDonutGroupChart(data) {
    const el = document.querySelector('#donut-group-chart');
    if (!el) return;

    // Destroy existing chart
    if (window.donutGroupChart) {
        window.donutGroupChart.destroy();
    }

    const labels = Object.keys(data).map(k => (k || 'Unspecified').toUpperCase());
    const series = Object.values(data);

    const options = {
        series: series,
        labels: labels,
        chart: {
            type: 'donut',
            height: 220,
            fontFamily: 'inherit',
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 400,
            }
        },
        colors: ['#3b82f6', '#22c55e', '#f97316', '#a855f7', '#ec4899', '#14b8a6'],
        plotOptions: {
            pie: {
                donut: {
                    size: '60%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Total',
                            fontSize: '12px',
                            fontWeight: 600,
                            color: '#6b7280'
                        }
                    }
                }
            }
        },
        dataLabels: {
            enabled: false
        },
        legend: {
            position: 'bottom',
            fontSize: '12px',
            markers: {
                width: 10,
                height: 10,
                radius: 2
            }
        },
        stroke: {
            width: 2,
            colors: ['#fff']
        },
        tooltip: {
            y: {
                formatter: function(val) {
                    return val + ' Trainings';
                }
            }
        },
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    height: 180
                },
                legend: {
                    position: 'bottom'
                }
            }
        }]
    };

    window.donutGroupChart = new ApexCharts(el, options);
    window.donutGroupChart.render();
}

/**
 * Handle sidebar toggle - resize all charts smoothly during transition
 */
function handleSidebarResize() {
    const resizeCharts = () => {
        if (window.trainingChart) {
            window.trainingChart.updateOptions({}, false, false);
        }
        if (window.donutTypeChart) {
            window.donutTypeChart.updateOptions({}, false, false);
        }
        if (window.donutGroupChart) {
            window.donutGroupChart.updateOptions({}, false, false);
        }
    };

    // Resize during transition (~500ms at 60fps)
    let frame = 0;
    const animate = () => {
        resizeCharts();
        frame++;
        if (frame < 30) requestAnimationFrame(animate);
    };
    requestAnimationFrame(animate);
}

/**
 * Setup Livewire event listener for breakdown data
 */
function setupBreakdownListener() {
    if (typeof Livewire !== 'undefined') {
        Livewire.on('breakdown-loaded', ({ byType, byGroupComp, total }) => {
            setTimeout(() => {
                if (byType && Object.keys(byType).length > 0) {
                    renderDonutTypeChart(byType);
                }
                if (byGroupComp && Object.keys(byGroupComp).length > 0) {
                    renderDonutGroupChart(byGroupComp);
                }
            }, 150);
        });
    }
}

// Setup sidebar resize listener
window.addEventListener('sidebar-toggled', handleSidebarResize);

// Setup Livewire breakdown listener on init
document.addEventListener('livewire:initialized', setupBreakdownListener);

// Export functions for use in blade templates
window.DashboardCharts = {
    initTrainingChart,
    renderDonutTypeChart,
    renderDonutGroupChart,
    handleSidebarResize,
    setupBreakdownListener
};
