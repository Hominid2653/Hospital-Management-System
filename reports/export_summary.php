<?php
session_start();
require_once '../config/database.php';

// Disable error reporting and output buffering
error_reporting(0);
ini_set('display_errors', 0);
ob_end_clean();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

try {
    // Get filter parameters
    $period = isset($_GET['period']) ? $_GET['period'] : 'month';
    $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
    $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');

    // Build the date condition
    $dateCondition = match($period) {
        'month' => "AND YEAR(m.visit_date) = ? AND MONTH(m.visit_date) = ?",
        'year' => "AND YEAR(m.visit_date) = ?",
        'all' => "",
    };

    // Get period text
    $periodText = match($period) {
        'month' => date('F Y', mktime(0, 0, 0, $month, 1, $year)),
        'year' => $year,
        default => 'All Time'
    };

    // 1. Get worker statistics
    $workerSql = "
        SELECT 
            COUNT(DISTINCT m.payroll_number) as total_workers_treated,
            COUNT(DISTINCT CASE WHEN w.department LIKE '%Management%' THEN m.payroll_number END) as management_treated,
            COUNT(DISTINCT CASE WHEN w.department NOT LIKE '%Management%' THEN m.payroll_number END) as workers_treated,
            COUNT(m.id) as total_consultations,
            COUNT(DISTINCT DATE(m.visit_date)) as total_days
        FROM medical_history m
        JOIN workers w ON m.payroll_number = w.payroll_number
        WHERE 1=1 " . ($dateCondition ? $dateCondition : "");
    
    $stmt = $pdo->prepare($workerSql);
    if ($period === 'month') {
        $stmt->execute([$year, $month]);
    } elseif ($period === 'year') {
        $stmt->execute([$year]);
    } else {
        $stmt->execute();
    }
    $workerStats = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Get disease classification statistics
    $diseaseSql = "
        SELECT 
            dc.name,
            COUNT(m.id) as consultation_count,
            COUNT(DISTINCT m.payroll_number) as unique_patients,
            ROUND(COUNT(m.id) * 100.0 / SUM(COUNT(m.id)) OVER(), 1) as percentage
        FROM medical_history m
        JOIN disease_classifications dc ON m.disease_classification_id = dc.id
        WHERE 1=1 " . ($dateCondition ? $dateCondition : "") . "
        GROUP BY dc.id, dc.name
        ORDER BY consultation_count DESC";
    
    $stmt = $pdo->prepare($diseaseSql);
    if ($period === 'month') {
        $stmt->execute([$year, $month]);
    } elseif ($period === 'year') {
        $stmt->execute([$year]);
    } else {
        $stmt->execute();
    }
    $diseaseStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Get drug usage statistics
    $drugSql = "
        SELECT 
            d.name,
            COUNT(pd.id) as times_prescribed,
            SUM(pd.quantity) as total_quantity,
            d.unit_of_measure,
            ROUND(COUNT(pd.id) * 100.0 / SUM(COUNT(pd.id)) OVER(), 1) as prescription_rate
        FROM prescribed_drugs pd
        JOIN drugs d ON pd.drug_id = d.id
        JOIN medical_history m ON pd.medical_history_id = m.id
        WHERE 1=1 " . ($dateCondition ? $dateCondition : "") . "
        GROUP BY d.id, d.name, d.unit_of_measure
        ORDER BY times_prescribed DESC";
    
    $stmt = $pdo->prepare($drugSql);
    if ($period === 'month') {
        $stmt->execute([$year, $month]);
    } elseif ($period === 'year') {
        $stmt->execute([$year]);
    } else {
        $stmt->execute();
    }
    $drugStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Clear output buffer
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Set headers for Word document download
    header("Content-Type: application/vnd.ms-word");
    header("Content-Disposition: attachment; filename=Medical_Summary_Report_" . str_replace(' ', '_', $periodText) . ".doc");
    header("Pragma: no-cache");
    header("Expires: 0");

    // Start HTML document
    echo '
    <html xmlns:o="urn:schemas-microsoft-com:office:office" 
          xmlns:w="urn:schemas-microsoft-com:office:word" 
          xmlns="http://www.w3.org/TR/REC-html40">
    <head>
        <meta charset="utf-8">
        <title>Medical Summary Report</title>
        <style>
            body { font-family: Arial, sans-serif; }
            table { border-collapse: collapse; width: 100%; margin: 10px 0; }
            th, td { border: 1px solid #000; padding: 8px; }
            th { background-color: #f0f0f0; }
            h1, h2 { color: #2c3e50; }
            .section { margin: 20px 0; }
            .header { text-align: center; margin-bottom: 30px; }
        </style>
    </head>
    <body>';

    // Header Section
    echo '<div class="header">
        <h1>SIAN ROSES MEDICAL FACILITY</h1>
        <h2>Period Summary Report - ' . htmlspecialchars($periodText) . '</h2>
        <p>Generated on: ' . date('F d, Y H:i:s') . '</p>
    </div>';

    // 1. Consultation Summary
    echo '<div class="section">
        <h2>CONSULTATION SUMMARY</h2>
        <table>
            <tr><td>Total Workers Treated:</td><td>' . $workerStats['total_workers_treated'] . '</td></tr>
            <tr><td>Management Staff Treated:</td><td>' . $workerStats['management_treated'] . '</td></tr>
            <tr><td>Farm Workers Treated:</td><td>' . $workerStats['workers_treated'] . '</td></tr>
            <tr><td>Total Consultations:</td><td>' . $workerStats['total_consultations'] . '</td></tr>
            <tr><td>Average Daily Consultations:</td><td>' . 
                round($workerStats['total_consultations'] / max($workerStats['total_days'], 1), 1) . '</td></tr>
        </table>
    </div>';

    // 2. Disease Classification Summary
    echo '<div class="section">
        <h2>DISEASE CLASSIFICATION SUMMARY</h2>
        <table>
            <tr>
                <th>Disease</th>
                <th>Consultations</th>
                <th>Unique Patients</th>
                <th>Percentage</th>
            </tr>';
    foreach ($diseaseStats as $disease) {
        echo '<tr>
            <td>' . htmlspecialchars($disease['name']) . '</td>
            <td>' . $disease['consultation_count'] . '</td>
            <td>' . $disease['unique_patients'] . '</td>
            <td>' . $disease['percentage'] . '%</td>
        </tr>';
    }
    echo '</table></div>';

    // 3. Drug Usage Summary
    echo '<div class="section">
        <h2>DRUG USAGE SUMMARY</h2>
        <table>
            <tr>
                <th>Drug Name</th>
                <th>Times Prescribed</th>
                <th>Total Quantity</th>
                <th>Unit</th>
                <th>Prescription Rate</th>
            </tr>';
    foreach ($drugStats as $drug) {
        echo '<tr>
            <td>' . htmlspecialchars($drug['name']) . '</td>
            <td>' . $drug['times_prescribed'] . '</td>
            <td>' . $drug['total_quantity'] . '</td>
            <td>' . htmlspecialchars($drug['unit_of_measure']) . '</td>
            <td>' . $drug['prescription_rate'] . '%</td>
        </tr>';
    }
    echo '</table></div>';

    // 4. Recommendations Section
    echo '<div class="section">
        <h2>KEY FINDINGS AND RECOMMENDATIONS</h2>
        <table>';
    
    if (!empty($diseaseStats)) {
        echo '<tr><td>Most Common Disease:</td><td>' . 
            htmlspecialchars($diseaseStats[0]['name']) . ' (' . $diseaseStats[0]['percentage'] . '% of all cases)</td></tr>';
    }
    
    if (!empty($drugStats)) {
        echo '<tr><td>Most Prescribed Drug:</td><td>' . 
            htmlspecialchars($drugStats[0]['name']) . ' (' . $drugStats[0]['prescription_rate'] . '% of prescriptions)</td></tr>';
    }
    
    $avgConsultations = $workerStats['total_consultations'] / max($workerStats['total_days'], 1);
    echo '<tr><td>Daily Consultation Load:</td><td>' . round($avgConsultations, 1) . ' consultations per day</td></tr>';
    echo '</table>';

    // Recommendations
    echo '<h3>RECOMMENDATIONS:</h3><ul>';
    if ($avgConsultations > 20) {
        echo '<li>High consultation load detected. Consider additional medical staff.</li>';
    }
    if (!empty($diseaseStats) && $diseaseStats[0]['percentage'] > 30) {
        echo '<li>High prevalence of ' . htmlspecialchars($diseaseStats[0]['name']) . '. Consider preventive measures.</li>';
    }
    echo '</ul></div>';

    echo '</body></html>';
    exit();

} catch (Exception $e) {
    error_log("Export Error: " . $e->getMessage());
    if (ob_get_level()) {
        ob_end_clean();
    }
    $_SESSION['error'] = "Unable to generate report. Please try again.";
    header("Location: index.php");
    exit();
} 