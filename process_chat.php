<?php
header('Content-Type: application/json');

// Increase memory limit and execution time for large files
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 120);

// Get POST parameters
$contactFile = $_POST['contactFile'] ?? null;
$chartType = $_POST['chartType'] ?? null;
$groupBy = $_POST['groupBy'] ?? null;

// Input validation
if (!$contactFile || !$chartType || !$groupBy) {
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

// Security: Construct file path and validate
$chatsDir = __DIR__ . '/chats/';
$filePath = $chatsDir . $contactFile;

// Use realpath to resolve any path traversal attempts
$realPath = realpath($filePath);
$realChatsDir = realpath($chatsDir);

if (!$realPath || !$realChatsDir || strpos($realPath, $realChatsDir) !== 0) {
    echo json_encode(['error' => 'Invalid file path']);
    exit;
}

// Check if file exists
if (!file_exists($realPath)) {
    echo json_encode(['error' => 'Chat file not found']);
    exit;
}

// Initialize data structure
$dailyMessageCounts = [];

// WhatsApp message pattern: "11/11/22, 1:33 AM - Sender Name: message"
// Note: There's a narrow no-break space (U+202F) between time and AM/PM
$messagePattern = '/^(\d{1,2}\/\d{1,2}\/\d{2,4}), \d{1,2}:\d{2}[\s\x{202F}]*[AP]M - ([^:]+): /u';

// Process file line by line for memory efficiency
$handle = fopen($realPath, 'r');
if (!$handle) {
    echo json_encode(['error' => 'Unable to open chat file']);
    exit;
}

while (($line = fgets($handle)) !== false) {
    // Skip empty lines
    $line = trim($line);
    if (empty($line)) {
        continue;
    }
    
    // Check if line matches message pattern
    if (preg_match($messagePattern, $line, $matches)) {
        $dateString = $matches[1];
        
        // Convert date to standard format
        try {
            // Handle both 2-digit and 4-digit years
            $date = DateTime::createFromFormat('n/j/y', $dateString);
            if (!$date) {
                $date = DateTime::createFromFormat('n/j/Y', $dateString);
            }
            
            if ($date) {
                $normalizedDate = $date->format('Y-m-d');
                
                // Increment counter
                if (isset($dailyMessageCounts[$normalizedDate])) {
                    $dailyMessageCounts[$normalizedDate]++;
                } else {
                    $dailyMessageCounts[$normalizedDate] = 1;
                }
            }
        } catch (Exception $e) {
            // Skip invalid dates
            continue;
        }
    }
}

fclose($handle);

// Sort dates chronologically
ksort($dailyMessageCounts);

// Group data by year
$yearlyData = [];

foreach ($dailyMessageCounts as $date => $count) {
    $year = date('Y', strtotime($date));
    
    if (!isset($yearlyData[$year])) {
        $yearlyData[$year] = [];
    }
    
    $yearlyData[$year][$date] = $count;
}

// Prepare response data grouped by year
$response = [
    'years' => [],
    'totalMessages' => array_sum($dailyMessageCounts),
    'dateRange' => []
];

if (!empty($dailyMessageCounts)) {
    $allDates = array_keys($dailyMessageCounts);
    $response['dateRange'] = [
        'start' => $allDates[0],
        'end' => $allDates[count($allDates) - 1]
    ];
}

foreach ($yearlyData as $year => $yearMessages) {
    $yearData = [
        'year' => $year,
        'totalMessages' => array_sum($yearMessages)
    ];
    
    if ($groupBy === 'day') {
        // Create full year data with zeros for missing days
        $yearStart = $year . '-01-01';
        $yearEnd = $year . '-12-31';
        $fullYearData = [];
        $fullYearLabels = [];
        
        $currentDate = new DateTime($yearStart);
        $endDate = new DateTime($yearEnd);
        
        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $fullYearLabels[] = $dateStr;
            $fullYearData[] = $yearMessages[$dateStr] ?? 0;
            $currentDate->add(new DateInterval('P1D'));
        }
        
        $yearData['labels'] = $fullYearLabels;
        $yearData['data'] = $fullYearData;
        
    } elseif ($groupBy === 'week') {
        // Group by weeks within the year (52 data points with month context)
        $weeklyData = [];
        $weekDetails = [];
        
        foreach ($yearMessages as $date => $count) {
            $timestamp = strtotime($date);
            $jan1 = strtotime($year . '-01-01');
            $daysDiff = floor(($timestamp - $jan1) / (60 * 60 * 24));
            $weekNumber = floor($daysDiff / 7) + 1;
            
            $weekKey = 'W' . sprintf('%02d', $weekNumber);
            
            if (isset($weeklyData[$weekKey])) {
                $weeklyData[$weekKey] += $count;
                $weekDetails[$weekKey]['endDate'] = $date;
            } else {
                $weeklyData[$weekKey] = $count;
                $weekDetails[$weekKey] = [
                    'startDate' => $date,
                    'endDate' => $date
                ];
            }
        }
        
        // Create full year of weeks (52-53 weeks) with month-aware labels
        $fullWeeklyData = [];
        $fullWeeklyLabels = [];
        $fullWeekDetails = [];
        
        for ($week = 1; $week <= 53; $week++) {
            $weekKey = 'W' . sprintf('%02d', $week);
            
            // Calculate what month this week primarily falls in
            $weekStartDay = ($week - 1) * 7 + 1;
            $weekDate = mktime(0, 0, 0, 1, $weekStartDay, $year);
            $monthName = date('M', $weekDate);
            
            // Create month-aware label (e.g., "Jan W1", "Feb W2")
            $weekInYear = $week;
            $label = $monthName . ' W' . sprintf('%02d', $weekInYear);
            
            $fullWeeklyLabels[] = $label;
            $fullWeeklyData[] = $weeklyData[$weekKey] ?? 0;
            
            if (isset($weekDetails[$weekKey])) {
                $fullWeekDetails[] = [
                    'weekNumber' => $week,
                    'monthName' => $monthName,
                    'startDate' => $weekDetails[$weekKey]['startDate'],
                    'endDate' => $weekDetails[$weekKey]['endDate']
                ];
            } else {
                $fullWeekDetails[] = [
                    'weekNumber' => $week,
                    'monthName' => $monthName,
                    'startDate' => null,
                    'endDate' => null
                ];
            }
        }
        
        $yearData['labels'] = $fullWeeklyLabels;
        $yearData['data'] = $fullWeeklyData;
        $yearData['weekDetails'] = $fullWeekDetails;
    }
    
    $response['years'][] = $yearData;
}

// Sort years chronologically
usort($response['years'], function($a, $b) {
    return (int)$a['year'] - (int)$b['year'];
});

// Calculate Y-axis max from all processed data
$allProcessedValues = [];
foreach ($response['years'] as $year) {
    $allProcessedValues = array_merge($allProcessedValues, $year['data']);
}
$maxValue = empty($allProcessedValues) ? 10 : max($allProcessedValues);
$response['yAxisMax'] = ceil($maxValue * 1.1); // Add 10% padding

echo json_encode($response);
?>