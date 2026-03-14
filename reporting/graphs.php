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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script src="https://cdn.zinggrid.com/zinggrid.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</head>

<body>
    <a href="logout" title="Logout">Logout</a>

    <button onclick="exportToPDF()" style="display:block; margin: 20px auto; padding: 10px 20px; cursor: pointer;">
        Export as PDF
    </button>
    <div id="report-Content">

        <!-- Row 1: Pageviews + Performance side by side -->
    <div style="display: flex; gap: 20px; margin: 40px auto; max-width: 1400px;">
        <div style="flex: 1; height: 350px;">
            <canvas id="pageviewsChart"></canvas>
        </div>
        
        <div style="flex: 1; height: 350px;">
            <canvas id="performanceChart"></canvas>
        </div>
    </div>
    
    <div style="background:#fdf6ff; border-left:4px solid #b08fd4; border-radius:4px; padding:14px 18px; font-size:14px; color:#444; max-width:1400px; margin:12px auto 0;">
    <strong style="color:#7a4fa3;">Analyst comment:</strong> Fig 1: Traffic is concentrated on the homepage, which accounts for the majority of pageviews. Most users land directly on the root URL rather than navigating to subpages.Specifically, the homepage received 12 views vs only 1 for /products.html, suggesting users are not discovering the product catalog.
    </div>
    <div style="background:#fdf6ff; border-left:4px solid #b08fd4; border-radius:4px; padding:14px 18px; font-size:14px; color:#444; max-width:1400px; margin:12px auto 0;">
    <strong style="color:#7a4fa3;">Analyst comment:</strong> Fig 2: Page load times fluctuate considerably. Spikes above 2,500ms indicate occasional performance bottlenecks that may impact user experience on slower connections.
    </div>

    <!-- Row 2: Device Split + First vs Last Page side by side -->
    <div style="display: flex; gap: 20px; margin: 40px auto; max-width: 1400px;">
    <div style="flex: 1; height: 350px;">
        <canvas id="deviceChart"></canvas>
    </div>
    <div style="flex: 1; height: 350px;">
        <canvas id="firstLastPageChart"></canvas>
    </div>

    </div>
    <div style="background:#fdf6ff; border-left:4px solid #b08fd4; border-radius:4px; padding:14px 18px; font-size:14px; color:#444; max-width:1400px; margin:12px auto 0;">
    <strong style="color:#7a4fa3;">Analyst comment:</strong> Fig 3: iOS devices make up the largest share of sessions, suggesting the audience skews mobile. Responsive design and mobile performance should be prioritized.
    </div>
    <div style="background:#fdf6ff; border-left:4px solid #b08fd4; border-radius:4px; padding:14px 18px; font-size:14px; color:#444; max-width:1400px; margin:12px auto 0;">
    <strong style="color:#7a4fa3;">Analyst comment:</strong> Fig 4: Most sessions start and end on the same page. 8 out of 11 sessions bounced, a 73% bounce rate, indicating the landing page is not driving further engagement.
    </div>

        <!-- ZingGrid Data Grid -->
        <zing-grid caption="CSE 135 Data Grid" theme="pink"></zing-grid>
    </div>

    <script>
        // Pageviews bar chart
        fetch('api.php/pageviews')
            .then(res => res.json())
            .then(data => {
                const counts = {};
                data.forEach(row => {
                    const fullKey = row.page || row.url || row.path || Object.values(row)[1];
                    const key = fullKey.replace('https://test.cse135hw1.online', '') || '/';
                    counts[key] = (counts[key] || 0) + 1;
                });

                new Chart(document.getElementById('pageviewsChart'), {
                    type: 'bar',
                    data: {
                        labels: Object.keys(counts),
                        datasets: [{
                            label: 'Pageviews',
                            data: Object.values(counts),
                            backgroundColor: '#d4a8e0'
                        }]
                    },
                    options: {
                        scales: { y: { beginAtZero: true } },
                        plugins: {
                            title: { display: true, text: 'Fig 1: Pageviews by Page' }
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
                    borderColor: '#F4A7C3',
                    backgroundColor: 'transparent',
                    tension: 0.3,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { title: { display: true, text: 'Entry ID' } },
                    y: { beginAtZero: true, title: { display: true, text: 'Load Time (ms)' } }
                },
                plugins: {
                    title: { display: true, text: 'Fig 2: Page Load Time' },
                    legend: { display: false }
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
            const element = document.getElementById('report-Content');

            const canvas = await html2canvas(element, { scale: 2 });
            const imgData = canvas.toDataURL('image/png');

            const pdf = new jsPDF('p', 'mm', 'a4');
            const pdfWidth = pdf.internal.pageSize.getWidth();
            const pdfHeight = (canvas.height * pdfWidth) / canvas.width;

            pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
            pdf.save('report-' + new Date().toISOString().slice(0, 10) + '.pdf');
        }
    </script>
    
    <script>
    function getDevice(ua) {
        if (!ua) return 'Unknown';
        if (/iphone|ipad|ipod/i.test(ua)) return 'iOS';
        if (/windows/i.test(ua)) return 'Windows';
        if (/android/i.test(ua)) return 'Android';
        if (/mac/i.test(ua)) return 'Mac';
        return 'Other';
    }
    fetch('api.php/sessions')
    .then(res => res.json())
    .then(data => {
        const deviceCounts = {};
        data.forEach(row => {
            const d = getDevice(row.user_agent);
            deviceCounts[d] = (deviceCounts[d] || 0) + 1;
        });

        new Chart(document.getElementById('deviceChart'), {
            type: 'doughnut',
            data: {
                labels: Object.keys(deviceCounts),
                datasets: [{
                    data: Object.values(deviceCounts),
                    backgroundColor: ['#d4a8e0', '#f4a7c3', '#b08fd4', '#f9d0e3', '#e8c8f0']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: { display: true, text: 'Fig 3: Device Split' },
                    legend: { position: 'bottom' }
                }
            }
        });

        // First vs last page
        const pagePairs = {};
        data.forEach(row => {
            const domain = 'https://test.cse135hw1.online';
            const shorten = url => {
                const path = (url || '').replace(domain, '') || '/';
                return path === '/' ? 'root' : path;
            };
            const first = shorten(row.first_page);
            const last = shorten(row.last_page);
            const key = first === last ? 'Bounced (same page)' : `${first} → ${last}`;
            pagePairs[key] = (pagePairs[key] || 0) + 1;
        });

        new Chart(document.getElementById('firstLastPageChart'), {
            type: 'bar',
            data: {
                labels: Object.keys(pagePairs),
                datasets: [{
                    label: 'Sessions',
                    data: Object.values(pagePairs),
                    backgroundColor: Object.keys(pagePairs).map(k =>
                        k.startsWith('Bounced') ? '#f4a7c3' : '#d4a8e0'
                    )
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                scales: {
                    x: { beginAtZero: true, title: { display: true, text: 'Sessions' } },
                    y: { ticks: { font: { size: 10 } } }
                },
                plugins: {
                    title: { display: true, text: 'Fig 4: First Page vs Last Page' },
                    legend: { display: false }
                }
            }
        });
    })
    .catch(err => console.error('sessions fetch error:', err));
</script>
</body>
</html>