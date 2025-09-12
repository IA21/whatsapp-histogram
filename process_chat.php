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

// Prepare response data
$response = [];

if ($groupBy === 'day') {
    $response['labels'] = array_keys($dailyMessageCounts);
    $response['data'] = array_values($dailyMessageCounts);
} elseif ($groupBy === 'week') {
    $weeklyMessageCounts = [];
    
    foreach ($dailyMessageCounts as $date => $count) {
        $timestamp = strtotime($date);
        $year = date('Y', $timestamp);
        
        // Custom week calculation: Jan 1 = start of week 1
        $jan1 = strtotime($year . '-01-01');
        $daysDiff = floor(($timestamp - $jan1) / (60 * 60 * 24));
        $weekNumber = floor($daysDiff / 7) + 1;
        
        $weekKey = $year . '-W' . sprintf('%02d', $weekNumber);
        
        if (isset($weeklyMessageCounts[$weekKey])) {
            $weeklyMessageCounts[$weekKey] += $count;
        } else {
            $weeklyMessageCounts[$weekKey] = $count;
        }
    }
    
    ksort($weeklyMessageCounts);
    $response['labels'] = array_keys($weeklyMessageCounts);
    $response['data'] = array_values($weeklyMessageCounts);
}

// Add metadata
if (!empty($dailyMessageCounts)) {
    $firstDate = array_keys($dailyMessageCounts)[0];
    $response['year'] = date('Y', strtotime($firstDate));
} else {
    $response['year'] = date('Y');
}

$response['totalMessages'] = array_sum($dailyMessageCounts);

echo json_encode($response);
?>