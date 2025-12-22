const ctx = document.getElementById('performanceChart');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['Quiz 1', 'Mid', 'Quiz 2', 'Final'],
        datasets: [{
            label: 'Marks',
            data: [65, 72, 75, 80],
            borderWidth: 2,
            tension: 0.3
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true,
                max: 100
            }
        }
    }
});
