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
        
        .overall-stats {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
            margin-bottom: 40px;
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
        
        .charts-container {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }
        
        .year-chart {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            position: relative;
        }
        
        .year-chart h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #25D366;
            font-size: 1.8em;
        }
        
        .chart-canvas {
            width: 100% !important;
            height: 400px !important;
            max-height: 400px;
        }
        
        .year-stats {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .year-stat {
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 8px;
            text-align: center;
            min-width: 100px;
        }
        
        .year-stat .value {
            font-weight: bold;
            color: #25D366;
            font-size: 1.2em;
        }
        
        .year-stat .label {
            font-size: 0.9em;
            color: #666;
        }
        
        #loader {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255, 255, 255, 0.9);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
            text-align: center;
            z-index: 1000;
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
        
        .error {
            background: #ff4444;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            display: none;
        }
        
        .no-data {
            text-align: center;
            padding: 50px;
            color: #666;
            font-size: 1.2em;
            display: none;
        }
        
        /* Info icon and modal styles */
        .info-icon {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #25D366;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 20px;
            font-weight: bold;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            transition: all 0.3s;
            z-index: 1001;
        }
        
        .info-icon:hover {
            background: #128C7E;
            transform: scale(1.1);
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1002;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            position: relative;
            animation: modalSlideIn 0.3s ease-out;
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .modal-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .modal-header h2 {
            color: #25D366;
            margin: 0;
            font-size: 1.8em;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            margin-left: auto;
            line-height: 1;
        }
        
        .close:hover,
        .close:focus {
            color: #25D366;
        }
        
        .modal-body {
            line-height: 1.6;
            color: #333;
        }
        
        .modal-body h3 {
            color: #25D366;
            margin-top: 25px;
            margin-bottom: 10px;
            font-size: 1.2em;
        }
        
        .modal-body ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        
        .modal-body li {
            margin: 8px 0;
        }
        
        .modal-body code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            color: #d63384;
        }
        
        .highlight {
            background: linear-gradient(135deg, #25D366, #128C7E);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
        }
        
        .step-number {
            background: #25D366;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9em;
            margin-right: 8px;
        }
        
        /* Dark mode styles */
        [data-theme="dark"] {
            --bg-color: #1a1a1a;
            --container-bg: #2d2d2d;
            --text-color: #e0e0e0;
            --card-bg: #3a3a3a;
            --border-color: #555;
            --input-bg: #404040;
            --modal-bg: #2d2d2d;
            --grid-color: rgba(255, 255, 255, 0.1);
        }
        
        [data-theme="light"] {
            --bg-color: #f5f5f5;
            --container-bg: transparent;
            --text-color: #333;
            --card-bg: white;
            --border-color: #ddd;
            --input-bg: white;
            --modal-bg: white;
            --grid-color: rgba(0, 0, 0, 0.1);
        }
        
        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            transition: background-color 0.3s, color 0.3s;
        }
        
        .container {
            background-color: var(--container-bg);
        }
        
        .stat-card, .year-chart {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
        }
        
        .year-stat {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
        }
        
        select {
            background-color: var(--input-bg);
            border-color: var(--border-color);
            color: var(--text-color);
        }
        
        .modal-content {
            background-color: var(--modal-bg);
            color: var(--text-color);
        }
        
        .modal-header {
            border-bottom-color: var(--border-color);
        }
        
        .modal-body code {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
        }
        
        /* Dark mode toggle button */
        .theme-toggle {
            position: fixed;
            bottom: 20px;
            left: 20px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #25D366;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 18px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            transition: all 0.3s;
            z-index: 1001;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .theme-toggle:hover {
            background: #128C7E;
            transform: scale(1.1);
        }
        
        /* Chart grid lines dark mode */
        [data-theme="dark"] .chart-canvas {
            filter: brightness(0.9);
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
                    <option value="week" selected>Weekly</option>
                    <option value="month">Monthly</option>
                </select>
            </div>
        </div>
        
        <div id="loader">
            <div class="loader-spinner"></div>
            <p>Processing chat data...</p>
        </div>
        
        <div class="overall-stats" id="overall-stats" style="display: none;">
            <div class="stat-card">
                <h3 id="total-messages">-</h3>
                <p>Total Messages</p>
            </div>
            <div class="stat-card">
                <h3 id="date-range">-</h3>
                <p>Date Range</p>
            </div>
            <div class="stat-card">
                <h3 id="years-count">-</h3>
                <p>Years Analyzed</p>
            </div>
        </div>
        
        <div class="no-data" id="no-data">
            <h3>No data to display</h3>
            <p>Please select a chat file to analyze.</p>
        </div>
        
        <div class="charts-container" id="charts-container"></div>
    </div>
    
    <!-- Theme Toggle -->
    <button class="theme-toggle" id="theme-toggle" title="Toggle dark/light mode">
        🌙
    </button>
    
    <!-- Info Icon -->
    <div class="info-icon" id="info-icon" title="How to use this tool">
        ?
    </div>
    
    <!-- Info Modal -->
    <div class="modal" id="info-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>📱 How to Use WhatsApp Chat Analyzer</h2>
                <span class="close" id="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <div class="highlight">
                    <strong>📋 Quick Start:</strong> Export your WhatsApp chat as a text file and place it in the chats/ directory to start analyzing your messaging patterns!
                </div>
                
                <h3><span class="step-number">1</span>Export WhatsApp Chat</h3>
                <p><strong>On Mobile:</strong></p>
                <ul>
                    <li>Open WhatsApp</li>
                    <li>Go to <strong>Settings</strong> → <strong>Chats</strong> → <strong>Chat History</strong></li>
                    <li>Tap <strong>"Export Chat"</strong></li>
                    <li>Pick the contact/group you want to analyze</li>
                    <li>Choose <strong>"Without Media"</strong> (we only need text messages)</li>
                    <li>Save or share the <code>.txt</code> file</li>
                </ul>
                
                <h3><span class="step-number">2</span>Place File in Directory</h3>
                <ul>
                    <li>Copy the exported <code>.txt</code> file to the <code>chats/</code> directory</li>
                    <li>The file can have any name (e.g., <code>john_doe.txt</code>, <code>family_group.txt</code>)</li>
                    <li>Refresh this page - your chat will appear in the dropdown</li>
                </ul>
                
                <h3><span class="step-number">3</span>Analyze Your Data</h3>
                <ul>
                    <li><strong>Select Contact:</strong> Choose the chat file to analyze</li>
                    <li><strong>Chart Type:</strong> Line charts show trends, bar charts show individual values</li>
                    <li><strong>Group By:</strong> Daily for detailed patterns, Weekly/Monthly for broader trends</li>
                </ul>
                
                <h3>🔧 Features</h3>
                <ul>
                    <li><strong>Multi-year support:</strong> Each year gets its own chart for easy comparison</li>
                    <li><strong>Smart tooltips:</strong> Hover over data points for detailed information</li>
                    <li><strong>Consistent scaling:</strong> All charts use the same Y-axis for accurate comparison</li>
                    <li><strong>Privacy-focused:</strong> All processing happens locally on your server</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let currentCharts = [];
        
        // DOM elements
        const contactSelect = document.getElementById('contact-select');
        const chartTypeSelect = document.getElementById('chart-type-select');
        const groupBySelect = document.getElementById('group-by-select');
        const chartsContainer = document.getElementById('charts-container');
        const loader = document.getElementById('loader');
        const errorMessage = document.getElementById('error-message');
        const overallStats = document.getElementById('overall-stats');
        const noData = document.getElementById('no-data');
        const infoIcon = document.getElementById('info-icon');
        const infoModal = document.getElementById('info-modal');
        const closeModal = document.getElementById('close-modal');
        const themeToggle = document.getElementById('theme-toggle');
        
        // Event listeners
        contactSelect.addEventListener('change', fetchAndRenderCharts);
        chartTypeSelect.addEventListener('change', fetchAndRenderCharts);
        groupBySelect.addEventListener('change', fetchAndRenderCharts);
        
        // Info modal event listeners
        infoIcon.addEventListener('click', () => {
            infoModal.style.display = 'block';
        });
        
        closeModal.addEventListener('click', () => {
            infoModal.style.display = 'none';
        });
        
        // Close modal when clicking outside of it
        window.addEventListener('click', (event) => {
            if (event.target === infoModal) {
                infoModal.style.display = 'none';
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && infoModal.style.display === 'block') {
                infoModal.style.display = 'none';
            }
        });
        
        // Theme toggle functionality
        function initializeTheme() {
            const savedTheme = localStorage.getItem('whatsapp-analyzer-theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            updateThemeIcon(savedTheme);
        }
        
        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('whatsapp-analyzer-theme', newTheme);
            updateThemeIcon(newTheme);
        }
        
        function updateThemeIcon(theme) {
            themeToggle.textContent = theme === 'dark' ? '☀️' : '🌙';
        }
        
        // Theme toggle event listener
        themeToggle.addEventListener('click', toggleTheme);
        
        // Load default chart on page load
        window.addEventListener('load', () => {
            // Initialize theme
            initializeTheme();
            // No default contact selection
        });
        
        function showError(message) {
            errorMessage.textContent = message;
            errorMessage.style.display = 'block';
            setTimeout(() => {
                errorMessage.style.display = 'none';
            }, 5000);
        }
        
        function updateOverallStats(data) {
            if (data.totalMessages) {
                document.getElementById('total-messages').textContent = data.totalMessages.toLocaleString();
                
                if (data.dateRange && data.dateRange.start && data.dateRange.end) {
                    const startDate = new Date(data.dateRange.start).toLocaleDateString();
                    const endDate = new Date(data.dateRange.end).toLocaleDateString();
                    document.getElementById('date-range').textContent = `${startDate} - ${endDate}`;
                }
                
                document.getElementById('years-count').textContent = data.years.length;
                overallStats.style.display = 'flex';
            }
        }
        
        function calculateYearStats(yearData, groupBy) {
            const total = yearData.totalMessages;
            const nonZeroData = yearData.data.filter(count => count > 0);
            const avg = nonZeroData.length > 0 ? Math.round(total / nonZeroData.length) : 0;
            const max = Math.max(...yearData.data);
            
            return {
                total: total.toLocaleString(),
                avg: `${avg}/${groupBy === 'day' ? 'day' : 'week'}`,
                max: max.toLocaleString()
            };
        }
        
        function fetchAndRenderCharts() {
            const contactFile = contactSelect.value;
            const chartType = chartTypeSelect.value;
            const groupBy = groupBySelect.value;
            
            if (!contactFile) {
                overallStats.style.display = 'none';
                chartsContainer.innerHTML = '';
                noData.style.display = 'block';
                return;
            }
            
            // Show loader
            loader.style.display = 'block';
            overallStats.style.display = 'none';
            noData.style.display = 'none';
            
            // Clear existing charts
            currentCharts.forEach(chart => chart.destroy());
            currentCharts = [];
            chartsContainer.innerHTML = '';
            
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
                
                renderYearlyCharts(data, chartType, groupBy);
                updateOverallStats(data);
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Error loading chat data: ' + error.message);
            })
            .finally(() => {
                loader.style.display = 'none';
            });
        }
        
        function renderYearlyCharts(data, chartType, groupBy) {
            if (!data.years || data.years.length === 0) {
                noData.style.display = 'block';
                return;
            }
            
            data.years.forEach(yearData => {
                const yearDiv = document.createElement('div');
                yearDiv.className = 'year-chart';
                
                const yearStats = calculateYearStats(yearData, groupBy);
                
                yearDiv.innerHTML = `
                    <h2>${yearData.year}</h2>
                    <canvas class="chart-canvas"></canvas>
                    <div class="year-stats">
                        <div class="year-stat">
                            <div class="value">${yearStats.total}</div>
                            <div class="label">Total</div>
                        </div>
                        <div class="year-stat">
                            <div class="value">${yearStats.avg}</div>
                            <div class="label">Average</div>
                        </div>
                        <div class="year-stat">
                            <div class="value">${yearStats.max}</div>
                            <div class="label">Peak</div>
                        </div>
                    </div>
                `;
                
                chartsContainer.appendChild(yearDiv);
                
                // Create chart for this year
                const canvas = yearDiv.querySelector('canvas');
                const chart = createChart(canvas, yearData, chartType, data.yAxisMax, groupBy);
                currentCharts.push(chart);
            });
        }
        
        function createChart(canvas, yearData, chartType, yAxisMax, groupBy) {
            const ctx = canvas.getContext('2d');
            
            const currentYear = new Date().getFullYear();
            const isCurrentYear = parseInt(yearData.year) === currentYear;
            const detailsKey = groupBy === 'week' ? 'weekDetails' : (groupBy === 'month' ? 'monthDetails' : null);
            
            // Set max labels based on grouping type
            const maxLabels = groupBy === 'day' ? 12 : (groupBy === 'month' ? 12 : 26); // 12 for daily/monthly, 26 for weekly
            
            // For current year line charts, hide dots on zero values
            let pointRadius = 3;
            let pointRadiusArray = null;
            
            if (chartType === 'line' && isCurrentYear) {
                // Create array of point radii - 0 for zero values, 3 for non-zero values
                pointRadiusArray = yearData.data.map(value => value === 0 ? 0 : 3);
                pointRadius = pointRadiusArray;
            }
            
            const config = {
                type: chartType,
                data: {
                    labels: yearData.labels,
                    datasets: [{
                        label: 'Messages',
                        data: yearData.data,
                        backgroundColor: chartType === 'bar' ? 'rgba(37, 211, 102, 0.8)' : 'rgba(37, 211, 102, 0.1)',
                        borderColor: '#25D366',
                        borderWidth: 2,
                        fill: chartType === 'line',
                        tension: 0.4,
                        pointRadius: pointRadius,
                        pointHoverRadius: chartType === 'line' && isCurrentYear ? 
                            yearData.data.map(value => value === 0 ? 0 : 5) : 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: yAxisMax,
                            ticks: {
                                stepSize: Math.ceil(yAxisMax / 10)
                            }
                        },
                        x: {
                            ticks: {
                                maxTicksLimit: maxLabels,
                                callback: function(value, index, values) {
                                    const label = this.getLabelForValue(value);
                                    if (groupBy === 'day') {
                                        // Show only month/day for daily view
                                        const date = new Date(label);
                                        return date.toLocaleDateString('en-US', { 
                                            month: 'short', 
                                            day: 'numeric' 
                                        });
                                    }
                                    return label; // For weekly (now monthly), show month names directly
                                }
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
                            callbacks: {
                                title: function(context) {
                                    const label = context[0].label;
                                    const index = context[0].dataIndex;
                                    
                                    if (groupBy === 'day') {
                                        const date = new Date(label);
                                        return date.toLocaleDateString('en-US', { 
                                            weekday: 'long',
                                            year: 'numeric',
                                            month: 'long', 
                                            day: 'numeric' 
                                        });
                                    } else {
                                        // For weekly/monthly grouping, show the date range if available
                                        if (yearData[detailsKey] && yearData[detailsKey][index]) {
                                            const detail = yearData[detailsKey][index];
                                            
                                            if (groupBy === 'week') {
                                                if (detail.startDate && detail.endDate) {
                                                    const startDate = new Date(detail.startDate);
                                                    const endDate = new Date(detail.endDate);
                                                    const startFormatted = startDate.toLocaleDateString('en-US', { 
                                                        month: 'short', day: 'numeric' 
                                                    });
                                                    const endFormatted = endDate.toLocaleDateString('en-US', { 
                                                        month: 'short', day: 'numeric' 
                                                    });
                                                    return `Week ${detail.weekNumber} of ${yearData.year} (${startFormatted} - ${endFormatted})`;
                                                } else {
                                                    return `Week ${detail.weekNumber} of ${yearData.year} (${detail.monthName})`;
                                                }
                                            } else if (groupBy === 'month') {
                                                if (detail.startDate && detail.endDate) {
                                                    const startDate = new Date(detail.startDate);
                                                    const endDate = new Date(detail.endDate);
                                                    const startFormatted = startDate.toLocaleDateString('en-US', { 
                                                        month: 'short', day: 'numeric' 
                                                    });
                                                    const endFormatted = endDate.toLocaleDateString('en-US', { 
                                                        month: 'short', day: 'numeric' 
                                                    });
                                                    return `${detail.monthName} ${yearData.year} (${startFormatted} - ${endFormatted})`;
                                                } else {
                                                    return `${detail.monthName} ${yearData.year}`;
                                                }
                                            }
                                        }
                                        return `${label} ${yearData.year}`;
                                    }
                                }
                            }
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    }
                }
            };
            
            return new Chart(ctx, config);
        }
    </script>
</body>
</html>