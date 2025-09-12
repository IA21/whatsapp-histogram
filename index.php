<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Chat Analyzer</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            text-align: center;
            margin-bottom: 30px;
            background: linear-gradient(135deg, #25D366, #128C7E);
            color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        header p {
            font-size: 1.1em;
            opacity: 0.9;
        }
        
        .controls {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .control-group {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .control-group label {
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        select {
            padding: 12px 16px;
            border: 2px solid #ddd;
            border-radius: 8px;
            background-color: white;
            font-size: 16px;
            min-width: 200px;
            cursor: pointer;
            transition: border-color 0.3s;
        }
        
        select:hover {
            border-color: #25D366;
        }
        
        select:focus {
            outline: none;
            border-color: #25D366;
            box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.1);
        }
        
        #chart-container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            min-height: 400px;
            position: relative;
        }
        
        #myChart {
            max-height: 400px;
        }
        
        #loader {
            display: none;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }
        
        .loader-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #25D366;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 2s linear infinite;
            margin: 0 auto 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .stats {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
            min-width: 150px;
        }
        
        .stat-card h3 {
            color: #25D366;
            font-size: 2em;
            margin-bottom: 5px;
        }
        
        .stat-card p {
            color: #666;
            font-weight: 600;
        }
        
        .error {
            background: #ff4444;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>📱 WhatsApp Chat Analyzer</h1>
            <p>Visualize your chat patterns and communication frequency over time</p>
        </header>
        
        <div class="error" id="error-message"></div>
        
        <div class="controls">
            <div class="control-group">
                <label for="contact-select">Select Contact:</label>
                <select id="contact-select">
                    <option value="">-- Select a chat file --</option>
                    <?php
                    $chatsDir = './chats/';
                    if (is_dir($chatsDir)) {
                        $files = scandir($chatsDir);
                        foreach ($files as $file) {
                            if ($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) === 'txt') {
                                $displayName = pathinfo($file, PATHINFO_FILENAME);
                                $displayName = ucfirst(str_replace(['_', '-'], ' ', $displayName));
                                echo "<option value=\"$file\">$displayName</option>";
                            }
                        }
                    }
                    ?>
                </select>
            </div>
            
            <div class="control-group">
                <label for="chart-type-select">Chart Type:</label>
                <select id="chart-type-select">
                    <option value="line">Line Chart</option>
                    <option value="bar">Bar Chart</option>
                </select>
            </div>
            
            <div class="control-group">
                <label for="group-by-select">Group By:</label>
                <select id="group-by-select">
                    <option value="day">Daily</option>
                    <option value="week">Weekly</option>
                </select>
            </div>
        </div>
        
        <div id="chart-container">
            <div id="loader">
                <div class="loader-spinner"></div>
                <p>Processing chat data...</p>
            </div>
            <canvas id="myChart"></canvas>
        </div>
        
        <div class="stats" id="stats-container" style="display: none;">
            <div class="stat-card">
                <h3 id="total-messages">-</h3>
                <p>Total Messages</p>
            </div>
            <div class="stat-card">
                <h3 id="date-range">-</h3>
                <p>Date Range</p>
            </div>
            <div class="stat-card">
                <h3 id="avg-daily">-</h3>
                <p>Avg per Day</p>
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let currentChart = null;
        
        // DOM elements
        const contactSelect = document.getElementById('contact-select');
        const chartTypeSelect = document.getElementById('chart-type-select');
        const groupBySelect = document.getElementById('group-by-select');
        const chartContainer = document.getElementById('chart-container');
        const loader = document.getElementById('loader');
        const errorMessage = document.getElementById('error-message');
        const statsContainer = document.getElementById('stats-container');
        
        // Event listeners
        contactSelect.addEventListener('change', fetchAndRenderChart);
        chartTypeSelect.addEventListener('change', fetchAndRenderChart);
        groupBySelect.addEventListener('change', fetchAndRenderChart);
        
        // Load default chart on page load
        window.addEventListener('load', () => {
            // Set default to sample.txt if available
            const sampleOption = contactSelect.querySelector('option[value="sample.txt"]');
            if (sampleOption) {
                contactSelect.value = 'sample.txt';
                fetchAndRenderChart();
            }
        });
        
        function showError(message) {
            errorMessage.textContent = message;
            errorMessage.style.display = 'block';
            setTimeout(() => {
                errorMessage.style.display = 'none';
            }, 5000);
        }
        
        function updateStats(data) {
            if (data.totalMessages) {
                document.getElementById('total-messages').textContent = data.totalMessages.toLocaleString();
                
                if (data.labels && data.labels.length > 0) {
                    const firstDate = data.labels[0];
                    const lastDate = data.labels[data.labels.length - 1];
                    document.getElementById('date-range').textContent = `${firstDate} to ${lastDate}`;
                    
                    // Calculate average daily messages
                    const daysDiff = Math.ceil((new Date(lastDate) - new Date(firstDate)) / (1000 * 60 * 60 * 24)) + 1;
                    const avgDaily = Math.round(data.totalMessages / daysDiff);
                    document.getElementById('avg-daily').textContent = avgDaily.toLocaleString();
                }
                
                statsContainer.style.display = 'flex';
            }
        }
        
        function fetchAndRenderChart() {
            const contactFile = contactSelect.value;
            const chartType = chartTypeSelect.value;
            const groupBy = groupBySelect.value;
            
            if (!contactFile) {
                return;
            }
            
            // Show loader
            loader.style.display = 'block';
            statsContainer.style.display = 'none';
            
            // Clear existing chart
            if (currentChart) {
                currentChart.destroy();
                currentChart = null;
            }
            
            // Create FormData for POST request
            const formData = new FormData();
            formData.append('contactFile', contactFile);
            formData.append('chartType', chartType);
            formData.append('groupBy', groupBy);
            
            // Fetch data from backend
            fetch('process_chat.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
                
                renderChart(data, chartType);
                updateStats(data);
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Error loading chat data: ' + error.message);
            })
            .finally(() => {
                loader.style.display = 'none';
            });
        }
        
        function renderChart(data, chartType) {
            const ctx = document.getElementById('myChart').getContext('2d');
            
            const config = {
                type: chartType,
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Messages',
                        data: data.data,
                        backgroundColor: chartType === 'bar' ? 'rgba(37, 211, 102, 0.8)' : 'rgba(37, 211, 102, 0.1)',
                        borderColor: '#25D366',
                        borderWidth: 2,
                        fill: chartType === 'line',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        },
                        x: {
                            ticks: {
                                maxTicksLimit: 20
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    }
                }
            };
            
            currentChart = new Chart(ctx, config);
        }
    </script>
</body>
</html>