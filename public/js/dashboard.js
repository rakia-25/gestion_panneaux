/**
 * Dashboard : date du jour + initialisation du graphique revenus (appelé depuis le template avec les données Twig)
 */
document.addEventListener('DOMContentLoaded', function() {
    var dateEl = document.getElementById('currentDate');
    if (dateEl) {
        var now = new Date();
        var options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        dateEl.textContent = now.toLocaleDateString('fr-FR', options);
    }
});

window.initDashboardChart = function(ctx, labels, dataEncaisses, dataPrevus) {
    if (!ctx || typeof Chart === 'undefined') return;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Revenus encaissés',
                    data: dataEncaisses,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    pointBackgroundColor: '#6366f1',
                    borderWidth: 3
                },
                {
                    label: 'Revenus prévus',
                    data: dataPrevus,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    pointBackgroundColor: '#10b981',
                    borderWidth: 3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: { size: 14, weight: 'bold' },
                    bodyFont: { size: 13 },
                    borderColor: 'rgba(255, 255, 255, 0.1)',
                    borderWidth: 1,
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + Number(context.raw).toLocaleString('fr-FR') + ' FCFA';
                        }
                    }
                },
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)',
                        drawBorder: false
                    },
                    ticks: {
                        font: { size: 12, weight: '600' },
                        color: '#64748b',
                        callback: function(value) {
                            return value >= 1000000 ? (value/1000000) + 'M' : value >= 1000 ? (value/1000) + 'K' : value;
                        }
                    }
                },
                x: {
                    grid: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        font: { size: 12, weight: '600' },
                        color: '#64748b'
                    }
                }
            }
        }
    });
};
