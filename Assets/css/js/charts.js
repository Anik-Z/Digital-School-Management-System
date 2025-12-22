const chrt= document.getElementById('performanceChart');

new Chart(chrt, {
    type: 'line',
    data: {
        labels: ['Quiz 1', 'Mid', 'Quiz 2', 'Final'],
        datasets: [{
            label: 'Marks',
            data: [65, 72, 75, 80],
            borderWidth: 2,
            tension: 0.4
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
