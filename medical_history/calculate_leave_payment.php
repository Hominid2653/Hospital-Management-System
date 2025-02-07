<?php
function calculateLeavePayment($workerId, $requestedDays, $startDate) {
    global $pdo;
    
    // Get worker's employment start date and department
    $stmt = $pdo->prepare("SELECT created_at, department FROM workers WHERE payroll_number = ?");
    $stmt->execute([$workerId]);
    $worker = $stmt->fetch();
    
    if (!$worker) {
        return ['error' => 'Worker not found'];
    }
    
    // Determine if worker is management
    $isManagement = (strpos(strtolower($worker['department']), 'management') !== false);
    
    // Set entitlements based on worker type
    if ($isManagement) {
        $totalEntitlement = 180;
        $fullPayDays = 90;
        $halfPayDays = 90;
    } else {
        $totalEntitlement = 113;
        $fullPayDays = 55;
        $halfPayDays = 58;
    }
    
    $employmentStart = new DateTime($worker['created_at']);
    $today = new DateTime();
    $employmentDuration = $employmentStart->diff($today);
    
    // Check if worker has completed 2 months of service
    if ($employmentDuration->m < 2 && $employmentDuration->y == 0) {
        return [
            'payment_recommendation' => 'no_pay',
            'reason' => 'Less than 2 months of service'
        ];
    }
    
    // Get total sick leave days taken in current year
    $year = date('Y', strtotime($startDate));
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(days_recommended), 0) as used_days
        FROM sick_leave_recommendations 
        WHERE worker_id = ? 
        AND YEAR(start_date) = ? 
        AND status = 'approved'
    ");
    $stmt->execute([$workerId, $year]);
    $result = $stmt->fetch();
    $usedDays = (int)$result['used_days'];
    
    // Calculate remaining leave days
    $remainingDays = $totalEntitlement - $usedDays;
    
    if ($remainingDays <= 0) {
        return [
            'payment_recommendation' => 'no_pay',
            'reason' => sprintf('Annual sick leave entitlement exhausted (%d days)', $totalEntitlement)
        ];
    }
    
    // Calculate payment recommendation based on used days
    $fullPayDaysRemaining = max(0, $fullPayDays - min($usedDays, $fullPayDays));
    $halfPayDaysRemaining = max(0, $halfPayDays - max(0, $usedDays - $fullPayDays));
    
    if ($requestedDays <= $fullPayDaysRemaining) {
        return [
            'payment_recommendation' => 'full_pay',
            'reason' => sprintf('Within first %d days of sick leave entitlement', $fullPayDays)
        ];
    } elseif ($requestedDays <= ($fullPayDaysRemaining + $halfPayDaysRemaining)) {
        return [
            'payment_recommendation' => 'half_pay',
            'reason' => sprintf('Beyond first %d days of sick leave entitlement', $fullPayDays)
        ];
    } else {
        return [
            'payment_recommendation' => 'no_pay',
            'reason' => 'Beyond annual sick leave entitlement'
        ];
    }
} 