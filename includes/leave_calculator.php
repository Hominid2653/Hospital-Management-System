<?php
function calculateLeavePayment($workerId, $requestedDays, $startDate) {
    global $pdo;
    
    // Get total leave days taken in current year, ordered by date
    $year = date('Y', strtotime($startDate));
    $stmt = $pdo->prepare("
        SELECT days_recommended, payment_recommendation
        FROM leave_recommendations 
        WHERE worker_id = ? 
        AND YEAR(start_date) = ? 
        AND status != 'rejected'
        ORDER BY start_date ASC
    ");
    $stmt->execute([$workerId, $year]);
    $leaveHistory = $stmt->fetchAll();
    
    // Calculate cumulative days and determine payment category
    $totalUsedDays = 0;
    $fullPayUsed = 0;
    $halfPayUsed = 0;
    
    foreach ($leaveHistory as $leave) {
        $totalUsedDays += $leave['days_recommended'];
        if ($leave['payment_recommendation'] === 'full_pay') {
            $fullPayUsed += $leave['days_recommended'];
        } elseif ($leave['payment_recommendation'] === 'half_pay') {
            $halfPayUsed += $leave['days_recommended'];
        }
    }
    
    // Constants for leave limits
    $totalEntitlement = 113;
    $fullPayLimit = 55;
    
    // Calculate remaining days in each category
    $totalRemainingDays = $totalEntitlement - $totalUsedDays;
    $fullPayRemaining = max(0, $fullPayLimit - $fullPayUsed);
    
    // If no days remaining at all
    if ($totalRemainingDays <= 0) {
        return [
            'payment_recommendation' => 'no_pay',
            'reason' => "Annual leave entitlement exhausted. Already used {$totalUsedDays} days this year.",
            'used_days' => $totalUsedDays,
            'remaining_days' => 0
        ];
    }
    
    // If requested days exceed total remaining
    if ($requestedDays > $totalRemainingDays) {
        return [
            'payment_recommendation' => 'no_pay',
            'reason' => "Requested days ({$requestedDays}) exceed remaining entitlement ({$totalRemainingDays} days).",
            'used_days' => $totalUsedDays,
            'remaining_days' => $totalRemainingDays
        ];
    }
    
    // If full pay days are still available
    if ($fullPayRemaining > 0) {
        if ($requestedDays <= $fullPayRemaining) {
            return [
                'payment_recommendation' => 'full_pay',
                'reason' => "Within first 55 days of leave entitlement. Used: {$fullPayUsed} days at full pay, Remaining at full pay: {$fullPayRemaining} days",
                'used_days' => $totalUsedDays,
                'remaining_days' => $totalRemainingDays
            ];
        } else {
            // Split between full pay and half pay
            return [
                'payment_recommendation' => 'half_pay',
                'reason' => "Partially exceeds full pay limit. Used: {$fullPayUsed} days at full pay. This leave will use remaining {$fullPayRemaining} days at full pay and " . 
                           ($requestedDays - $fullPayRemaining) . " days at half pay.",
                'used_days' => $totalUsedDays,
                'remaining_days' => $totalRemainingDays
            ];
        }
    }
    
    // If only half pay days are available
    if ($totalRemainingDays > 0) {
        return [
            'payment_recommendation' => 'half_pay',
            'reason' => "Beyond first 55 days. Used: {$totalUsedDays} days total. This leave will be at half pay.",
            'used_days' => $totalUsedDays,
            'remaining_days' => $totalRemainingDays
        ];
    }
} 