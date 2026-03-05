<?php
    session_start();

    if (!isset($_SESSION['valid'])) {
        header("Location: login.php");
        exit;
    }
?>

<html lang = "en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Graphs</title>
   <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script src="https://cdn.zinggrid.com/zinggrid.min.js" defer</script>
</head>

<body>
     <a href = "logout.php" tite = "Logout">Logout</a>
    <h1>this is the page where the graphs and tables go</h1>
    <div style="max-width: 700px; margin: 40px auto;">
        <canvas id="pageviewsChart"></canvas>
    </div>

    <script>
        // Fetch from your existing API
        fetch('api.php/pageviews')
            .then(res => res.json())
            .then(data => {
                // Count pageviews per page
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
        window.addEventListener('load', () =>{
            const zgRef = document.querySelector('zing-grid');
            const data = [{
                    name: 'Annejulia Milian',
                    origin: 'San Diego, CA, US',},
            {
                    name: 'Dishita Joshi',
                    origin: 'San Diego, CA, US',},
            {
                    name: 'Keyura Valalla',
                    origin: 'San Diego, CA, US', },
            {
                    name: 'Suguru Geto',
                    origin: 'Tokyo, JP', }
            ];
            zgRef.setData(data);
    });
    </script>
</body>
</html>
