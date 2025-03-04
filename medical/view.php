<?php
session_start();
require_once '../config/database.php';
require_once '../config/paths.php';
require_once '../includes/Database.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header("Location: " . url('login.php'));
    exit();
}

// Initialize variables
$worker_id = $_GET['id'] ?? '';
$error = '';
$success = '';
$page = $_GET['page'] ?? 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

try {
    $db = Database::getInstance();
    
    // Get worker details
    $worker = $db->query(
        "SELECT payroll_number, name, department, role 
         FROM workers 
         WHERE payroll_number = ?",
        [$worker_id]
    )[0] ?? null;

    if (!$worker) {
        header("Location: " . url('workers/list.php'));
        exit();
    }

    // Get medical records with all related information
    $medical_records = $db->query(
        "SELECT 
            m.*,
            dc.name as disease_category,
            lr.days_recommended,
            lr.start_date,
            lr.end_date,
            lr.payment_recommendation,
            GROUP_CONCAT(
                CONCAT(p.drug_name, ' (', p.dosage, ')')
                SEPARATOR ', '
            ) as prescriptions
         FROM medical_history m
         LEFT JOIN disease_classifications dc ON m.disease_classification_id = dc.id
         LEFT JOIN leave_recommendations lr ON lr.medical_history_id = m.id
         LEFT JOIN prescriptions p ON p.medical_history_id = m.id
         WHERE m.payroll_number = ?
         GROUP BY m.id
         ORDER BY m.visit_date DESC
         LIMIT ? OFFSET ?",
        [$worker_id, $per_page, $offset]
    );

    // Get total records for pagination
    $total_records = $db->query(
        "SELECT COUNT(DISTINCT m.id) as count 
         FROM medical_history m
         WHERE m.payroll_number = ?",
        [$worker_id]
    )[0]['count'];

    $total_pages = ceil($total_records / $per_page);

} catch (Exception $e) {
    error_log("Database Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    $error = "Database Error: " . $e->getMessage();
}

// Move the function definition to the top, before it's used
function getLeaveProgressInfo($worker_id) {
    global $pdo; // Use $pdo instead of $db
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                SUM(CASE WHEN payment_recommendation = 'full_pay' THEN days_recommended ELSE 0 END) as full_pay_used,
                SUM(CASE WHEN payment_recommendation = 'half_pay' THEN days_recommended ELSE 0 END) as half_pay_used,
                SUM(CASE WHEN payment_recommendation = 'no_pay' THEN days_recommended ELSE 0 END) as no_pay_used
            FROM leave_recommendations
            WHERE worker_id = ? 
            AND YEAR(start_date) = YEAR(CURRENT_DATE)
        ");
        $stmt->execute([$worker_id]);
        $leave_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'full_pay_used' => (int)($leave_info['full_pay_used'] ?? 0),
            'half_pay_used' => (int)($leave_info['half_pay_used'] ?? 0),
            'no_pay_used' => (int)($leave_info['no_pay_used'] ?? 0),
            'full_pay_limit' => 55,
            'half_pay_limit' => 38
        ];
    } catch (Exception $e) {
        error_log("Error getting leave info: " . $e->getMessage());
        return [
            'full_pay_used' => 0,
            'half_pay_used' => 0,
            'no_pay_used' => 0,
            'full_pay_limit' => 55,
            'half_pay_limit' => 38
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical History - <?php echo htmlspecialchars($worker['name'] ?? ''); ?></title>
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .profile-header {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .profile-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .back-button {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            text-decoration: none;
            transition: color 0.3s;
        }

        .back-button:hover {
            color: var(--primary);
        }

        .new-visit-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .new-visit-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .new-visit-btn i {
            font-size: 0.9em;
        }

        .profile-info {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 2rem;
            align-items: center;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
        }

        .profile-details h1 {
            margin: 0 0 0.5rem 0;
            color: var(--text-primary);
        }

        .profile-meta {
            display: flex;
            gap: 2rem;
            color: var(--text-secondary);
        }

        .profile-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .medical-records {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .records-header {
            margin-bottom: 2rem;
        }

        .records-header h2 {
            margin: 0;
            color: var(--text-primary);
        }

        .record-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .record-item {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 1.5rem;
            transition: all 0.3s;
        }

        .record-item:hover {
            border-color: var(--primary);
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
        }

        .record-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .record-date {
            font-size: 0.9rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .record-category {
            background: var(--bg-light);
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .record-content {
            color: var(--text-primary);
        }

        .record-footer {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        .leave-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .leave-full-pay {
            background-color: #e3f7e9;
            color: #0a6b2d;
            border: 1px solid #a3e4b7;
        }
        
        .leave-half-pay {
            background-color: #fff4e5;
            color: #925e16;
            border: 1px solid #ffd8a8;
        }
        
        .leave-no-pay {
            background-color: #ffe8e8;
            color: #c92a2a;
            border: 1px solid #ffc9c9;
        }

        .leave-progress {
            margin-top: 0.75rem;
            background: rgba(0,0,0,0.08);
            height: 6px;
            border-radius: 3px;
            overflow: hidden;
        }

        .leave-progress-bar {
            height: 100%;
            transition: width 0.3s ease;
        }

        .leave-details {
            font-size: 0.85rem;
            margin-top: 0.5rem;
            opacity: 0.9;
        }

        .no-records {
            text-align: center;
            padding: 3rem;
            color: var(--text-secondary);
            background: var(--bg-light);
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        .no-records i {
            font-size: 2rem;
            color: var(--primary);
        }

        .pagination {
            margin-top: 2rem;
            display: flex;
            justify-content: center;
            gap: 0.5rem;
        }

        .pagination a {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            background: var(--bg-light);
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.3s;
        }

        .pagination a:hover,
        .pagination a.active {
            background: var(--primary);
            color: white;
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="profile-header">
            <div class="profile-nav">
                <a href="<?php echo url('workers/view.php?id=' . $worker_id); ?>" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                    Back to Profile
                </a>
                <a href="<?php echo url('medical/new.php?id=' . $worker_id); ?>" class="new-visit-btn">
                    <i class="fas fa-plus"></i>
                    New Visit
                </a>
            </div>

            <div class="profile-info">
                <div class="profile-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="profile-details">
                    <h1><?php echo htmlspecialchars($worker['name']); ?></h1>
                    <div class="profile-meta">
                        <div class="profile-meta-item">
                            <i class="fas fa-id-card"></i>
                            <?php echo htmlspecialchars($worker['payroll_number']); ?>
                        </div>
                        <div class="profile-meta-item">
                            <i class="fas fa-building"></i>
                            <?php echo htmlspecialchars($worker['department']); ?>
                        </div>
                        <div class="profile-meta-item">
                            <i class="fas fa-user-tag"></i>
                            <?php echo htmlspecialchars($worker['role']); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="medical-records">
            <div class="records-header">
                <h2>Medical History</h2>
            </div>

            <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php elseif (empty($medical_records)): ?>
                <div class="no-records">
                    <i class="fas fa-file-medical"></i>
                    <p>No medical records found</p>
                </div>
            <?php else: ?>
                <div class="record-list">
                    <?php foreach ($medical_records as $record): ?>
                        <div class="record-item">
                            <div class="record-header">
                                <div class="record-date">
                                    <i class="far fa-calendar"></i>
                                    <?php echo date('F j, Y', strtotime($record['visit_date'])); ?>
                                </div>
                                <?php if ($record['disease_category']): ?>
                                    <span class="record-category">
                                        <?php echo htmlspecialchars($record['disease_category']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="record-content">
                                <p><strong>Diagnosis:</strong> <?php echo nl2br(htmlspecialchars($record['diagnosis'])); ?></p>
                                <?php if ($record['remarks']): ?>
                                    <p><strong>Remarks:</strong> <?php echo nl2br(htmlspecialchars($record['remarks'])); ?></p>
                                <?php endif; ?>
                                <?php if ($record['prescriptions']): ?>
                                    <p><strong>Prescriptions:</strong> <?php echo htmlspecialchars($record['prescriptions']); ?></p>
                                <?php endif; ?>
                            </div>
                            <?php if ($record['days_recommended']): ?>
                                <?php
                                $leave_class = match($record['payment_recommendation']) {
                                    'full_pay' => 'leave-full-pay',
                                    'half_pay' => 'leave-half-pay',
                                    'no_pay' => 'leave-no-pay',
                                    default => ''
                                };
                                $leave_info = getLeaveProgressInfo($worker['payroll_number']);
                                ?>
                                <div class="record-footer">
                                    <div class="leave-badge <?php echo $leave_class; ?>">
                                        <i class="fas fa-bed"></i>
                                        <?php echo htmlspecialchars($record['days_recommended']); ?> days leave
                                        <?php if ($record['payment_recommendation']): ?>
                                            • <?php echo str_replace('_', ' ', htmlspecialchars($record['payment_recommendation'])); ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($record['payment_recommendation'] === 'full_pay'): ?>
                                        <div class="leave-progress">
                                            <div class="leave-progress-bar <?php echo $leave_class; ?>" 
                                                 style="width: <?php echo min(100, ($leave_info['full_pay_used'] / $leave_info['full_pay_limit']) * 100); ?>%">
                                            </div>
                                        </div>
                                        <div class="leave-details">
                                            <?php echo $leave_info['full_pay_used']; ?> of <?php echo $leave_info['full_pay_limit']; ?> full pay days used this year
                                        </div>
                                    <?php elseif ($record['payment_recommendation'] === 'half_pay'): ?>
                                        <div class="leave-progress">
                                            <div class="leave-progress-bar <?php echo $leave_class; ?>" 
                                                 style="width: <?php echo min(100, ($leave_info['half_pay_used'] / $leave_info['half_pay_limit']) * 100); ?>%">
                                            </div>
                                        </div>
                                        <div class="leave-details">
                                            <?php echo $leave_info['half_pay_used']; ?> of <?php echo $leave_info['half_pay_limit']; ?> half pay days used this year
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?id=<?php echo $worker_id; ?>&page=<?php echo $i; ?>" 
                               class="<?php echo $page == $i ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>