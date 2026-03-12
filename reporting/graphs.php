<?php
    session_start();

    if (!isset($_SESSION['valid'])) {
        header("Location: login.php");
        exit;
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Graphs</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script src="https://cdn.zinggrid.com/zinggrid.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</head>

<body>
    <a href="logout.php" title="Logout">Logout</a>

    <button onclick="exportToPDF()" style="display:block; margin: 20px auto; padding: 10px 20px; cursor: pointer;">
        Export as PDF
    </button>
    <div id="reportContent">

        <!-- Pageviews Bar Chart -->
        <div style="max-width: 700px; margin: 40px auto;">
            <canvas id="pageviewsChart"></canvas>
        </div>

        <!-- Performance Line Chart -->
        <div style="max-width: 700px; margin: 40px auto;">
            <canvas id="performanceChart"></canvas>
        </div>

        <!-- ZingGrid Data Grid -->
        <zing-grid caption="CSE 135 HW 4 Data Grid"></zing-grid>
    </div>

    <script>
        // Pageviews bar chart
        fetch('api.php/pageviews')
            .then(res => res.json())
            .then(data => {
                const counts = {};
                data.forEach(row => {
                    const key = row.page || row.url || row.path || Object.values(row)[1];
                    counts[key] = (counts[key] || 0) + 1;
                });

                new Chart(document.getElementById('pageviewsChart'), {
                    type: 'bar',
                    data: {
                        labels: Object.keys(counts),
                        datasets: [{
                            label: 'Pageviews',
                            data: Object.values(counts),
                            backgroundColor: '#16a085'
                        }]
                    },
                    options: {
                        scales: { y: { beginAtZero: true } },
                        plugins: {
                            title: { display: true, text: 'Pageviews by Page' }
                        }
                    }
                });
            });

        // Performance line chart
        fetch('api.php/performance')
            .then(res => res.json())
            .then(data => {
                new Chart(document.getElementById('performanceChart'), {
                    type: 'line',
                    data: {
                        labels: data.map(row => row.id),
                        datasets: [{
                            label: 'Load Time (ms)',
                            data: data.map(row => row.load_time),
                            borderColor: '#16a085',
                            backgroundColor: 'rgba(22, 160, 133, 0.1)',
                            fill: true,
                            tension: 0.3,
                            pointRadius: 4,
                            pointBackgroundColor: '#16a085'
                        }]
                    },
                    options: {
                        scales: { y: { beginAtZero: true } },
                        plugins: {
                            title: { display: true, text: 'Page Load Time' }
                        }
                    }
                });
            });

        // ZingGrid data
        window.addEventListener('load', () => {
            const zgRef = document.querySelector('zing-grid');
            
            fetch('api.php/performance')
                .then(res => res.json())
                .then(data => {
                    zgRef.setData(data);
                });
    });

    async function exportToPDF() {
            const { jsPDF } = window.jspdf;
            const element = document.getElementById('report-content');

            const canvas = await html2canvas(element, { scale: 2 });
            const imgData = canvas.toDataURL('image/png');

            const pdf = new jsPDF('p', 'mm', 'a4');
            const pdfWidth = pdf.internal.pageSize.getWidth();
            const pdfHeight = (canvas.height * pdfWidth) / canvas.width;

            pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
            pdf.save('report-' + new Date().toISOString().slice(0, 10) + '.pdf');
        }
    </script>
</body>
</html>