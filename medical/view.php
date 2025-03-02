<?php
session_start();
require_once '../config/database.php';
require_once '../config/paths.php';
require_once '../includes/leave_calculator.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . url('login.php'));
    exit();
}

$worker_id = $_GET['id'] ?? '';
$error = '';
$success = '';

try {
    // Get worker details
    $stmt = $pdo->prepare("SELECT * FROM workers WHERE payroll_number = ?");
    $stmt->execute([$worker_id]);
    $worker = $stmt->fetch();

    if (!$worker) {
        header("Location: " . url('workers/search.php'));
        exit();
    }

    // Get available drugs (in stock and low stock only)
    $stmt = $pdo->prepare("
        SELECT name, status 
        FROM drugs 
        WHERE status != 'out_of_stock'
        ORDER BY name ASC
    ");
    $stmt->execute();
    $available_drugs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get medical history
    $stmt = $pdo->prepare("
        SELECT m.*, 
               GROUP_CONCAT(CONCAT(p.drug_name, ' - ', p.dosage) SEPARATOR '||') as prescriptions,
               lr.days_recommended,
               lr.payment_recommendation,
               lr.reason as recommendation_reason,
               lr.start_date as leave_start_date,
               lr.end_date as leave_end_date,
               lr.status as leave_status,
               dc.name as disease_classification
        FROM medical_history m
        LEFT JOIN prescriptions p ON m.id = p.medical_history_id
        LEFT JOIN leave_recommendations lr ON m.id = lr.medical_history_id
        LEFT JOIN disease_classifications dc ON m.disease_classification_id = dc.id
        WHERE m.payroll_number = ?
        GROUP BY m.id
        ORDER BY m.visit_date DESC
    ");
    $stmt->execute([$worker_id]);
    $medical_records = $stmt->fetchAll();

    // Handle new record submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $pdo->beginTransaction();
        
        try {
            // Insert medical history
            $stmt = $pdo->prepare("
                INSERT INTO medical_history 
                (payroll_number, diagnosis, disease_classification_id, visit_date, remarks) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $worker_id,
                $_POST['diagnosis'],
                $_POST['disease_classification'],
                $_POST['visit_date'],
                $_POST['remarks']
            ]);
            
            $medical_history_id = $pdo->lastInsertId();
            
            // Insert prescriptions if any
            if (!empty($_POST['prescriptions'])) {
                $stmt = $pdo->prepare("
                    INSERT INTO prescriptions (medical_history_id, drug_name, dosage)
                    VALUES (?, ?, ?)
                ");
                
                foreach ($_POST['prescriptions'] as $prescription) {
                    if (!empty($prescription['drug_name']) && !empty($prescription['dosage'])) {
                        $stmt->execute([
                            $medical_history_id,
                            $prescription['drug_name'],
                            $prescription['dosage']
                        ]);
                    }
                }
            }
            
            if (!empty($_POST['leave_days'])) {
                $calculation = calculateLeavePayment(
                    $worker_id,
                    $_POST['leave_days'],
                    $_POST['start_date']
                );
                
                // Calculate end date
                $end_date = date('Y-m-d', strtotime($_POST['start_date'] . ' + ' . ($_POST['leave_days'] - 1) . ' days'));
                
                // Insert leave recommendation
                $stmt = $pdo->prepare("
                    INSERT INTO leave_recommendations 
                    (medical_history_id, worker_id, days_recommended, payment_recommendation, 
                     reason, start_date, end_date) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $medical_history_id,
                    $worker_id,
                    $_POST['leave_days'],
                    $calculation['payment_recommendation'],
                    $_POST['recommendation_reason'] . "\n\nSystem Note: " . $calculation['reason'],
                    $_POST['start_date'],
                    $end_date
                ]);
            }
            
            $pdo->commit();
            $success = "Medical record added successfully";
            
            // Refresh the page to show new record
            header("Location: view.php?id=" . $worker_id . "&success=1");
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error adding medical record";
        }
    }
} catch (PDOException $e) {
    $error = "Error retrieving records";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical History - Sian Roses</title>
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .content-grid {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
            padding: 2rem;
        }

        .profile-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 2rem;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
            font-size: 2.5rem;
        }

        .profile-name {
            font-size: 1.5rem;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .profile-role {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
        }

        .profile-info {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--text-primary);
        }

        .info-item i {
            width: 20px;
            color: var(--primary);
        }

        .medical-history {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .history-title {
            font-size: 1.5rem;
            color: var(--primary);
            margin: 0;
        }

        .visit-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        .visit-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }

        .visit-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .visit-date {
            font-size: 1.1rem;
            color: var(--text-primary);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .visit-type {
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .visit-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .detail-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .detail-value {
            color: var(--text-primary);
        }

        .prescriptions-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .prescription-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
            background: var(--surface);
            border-radius: 6px;
        }

        .leave-recommendation {
            grid-column: 1 / -1;
            background: var(--secondary-light);
            padding: 1.5rem;
            border-radius: 12px;
            margin-top: 1rem;
        }

        .leave-label {
            font-size: 0.85rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            margin-left: auto;
        }

        .leave-label.full_pay {
            background: #4caf50;
            color: white;
        }

        .leave-label.half_pay {
            background: #ff9800;
            color: white;
        }

        .leave-label.no_pay {
            background: #f44336;
            color: white;
        }

        .leave-details {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .leave-info {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }

        .leave-info span:first-child {
            min-width: 80px;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .leave-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
        }

        .leave-status.approved {
            background: rgba(76, 175, 80, 0.1);
            color: #4caf50;
        }

        .leave-status.pending {
            background: rgba(255, 152, 0, 0.1);
            color: #ff9800;
        }

        .leave-status.rejected {
            background: rgba(244, 67, 54, 0.1);
            color: #f44336;
        }

        .btn-action {
            background: var(--primary);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            color: white;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
        }

        .btn-back {
            background: white;
            color: var(--text-primary);
            border: 1px solid var(--border);
        }

        .btn-back:hover {
            color: var(--primary);
            border-color: var(--primary);
        }
    </style>
</head>
<body>
    <div class="layout">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="section-header">
                <div>
                    <h1>Medical History</h1>
                    <p>View and manage medical records</p>
                </div>
                <div class="header-actions">
                    <a href="../workers/view.php?id=<?php echo $worker_id; ?>" class="btn-action btn-back">
                        <i class="fas fa-arrow-left"></i>
                        Back to Profile
                    </a>
                    <a href="new.php?id=<?php echo $worker_id; ?>" class="btn-action">
                        <i class="fas fa-plus-circle"></i>
                        New Visit
                    </a>
                </div>
            </div>

            <div class="content-grid">
                <!-- Profile Card -->
                <div class="profile-card">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <h2 class="profile-name"><?php echo htmlspecialchars($worker['name']); ?></h2>
                        <p class="profile-role"><?php echo htmlspecialchars($worker['role']); ?></p>
                    </div>

                    <div class="profile-info">
                        <div class="info-item">
                            <i class="fas fa-id-card"></i>
                            <span><?php echo htmlspecialchars($worker['payroll_number']); ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-building"></i>
                            <span><?php echo htmlspecialchars($worker['department']); ?></span>
                        </div>
                        <?php if ($worker['phone']): ?>
                        <div class="info-item">
                            <i class="fas fa-phone"></i>
                            <span><?php echo htmlspecialchars($worker['phone']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($worker['email']): ?>
                        <div class="info-item">
                            <i class="fas fa-envelope"></i>
                            <span><?php echo htmlspecialchars($worker['email']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Medical History -->
                <div class="medical-history">
                    <?php if (empty($medical_records)): ?>
                        <div class="no-records">
                            <i class="fas fa-notes-medical"></i>
                            <p>No medical records found</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($medical_records as $record): ?>
                            <div class="visit-card">
                                <!-- ... Rest of your existing visit card HTML ... -->
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/menu.js"></script>
</body>
</html>