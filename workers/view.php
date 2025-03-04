<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$base_url = '../';
$error = null;

// Get worker details and medical history
if (isset($_GET['id'])) {
    try {
        // Get worker details
        $stmt = $pdo->prepare("SELECT * FROM workers WHERE payroll_number = ?");
        $stmt->execute([$_GET['id']]);
        $worker = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$worker) {
            header("Location: list.php");
            exit();
        }

        // Get medical history
        $stmt = $pdo->prepare("
            SELECT * FROM medical_history 
            WHERE payroll_number = ? 
            ORDER BY visit_date DESC
        ");
        $stmt->execute([$_GET['id']]);
        $medical_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $error = "Error fetching data: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Details - <?php echo htmlspecialchars($worker['name']); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .content-grid {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }

        .profile-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 2rem;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border);
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
            font-size: 3rem;
        }

        .profile-name {
            font-size: 1.5rem;
            color: var(--text-primary);
            margin: 0.5rem 0;
        }

        .profile-role {
            color: var(--text-secondary);
            font-size: 1.1rem;
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
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
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
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .visit-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border);
        }

        .visit-date {
            font-size: 1.1rem;
            color: var(--text-primary);
            font-weight: 500;
        }

        .visit-type {
            color: var(--text-secondary);
        }

        .visit-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .detail-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .detail-value {
            color: var(--text-primary);
            font-weight: 500;
        }

        .visit-notes {
            background: rgba(var(--secondary-light-rgb), 0.3);
            padding: 1rem;
            border-radius: 8px;
            color: var(--text-primary);
        }

        .no-records {
            text-align: center;
            padding: 3rem;
            color: var(--text-secondary);
        }

        .no-records i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .btn-action {
            background-color: var(--secondary);
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

        .btn-primary {
            background-color: var(--primary);
        }

        .profile-actions {
            text-align: center;
            margin-top: 1rem;
        }

        .btn-edit-profile {
            background-color: var(--primary);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-edit-profile:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            color: white;
        }

        .leave-recommendation {
            background: var(--secondary-light);
            padding: 1.5rem;
            border-radius: 12px;
            margin-top: 1.5rem;
        }

        .leave-recommendation h4 {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
            font-size: 1.1rem;
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
        }

        .leave-info {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
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
    </style>
</head>
<body>
    <div class="layout">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="section-header">
                <div>
                    <h1>Employee Details</h1>
                    <p>View employee information and medical history</p>
                </div>
                <div class="header-actions">
                    
                    <a href="list.php" class="btn-action">
                        <i class="fas fa-arrow-left"></i>
                        To Employee List
                    </a>
                    <a href="../medical/new.php?id=<?php echo $worker['payroll_number']; ?>" class="btn-action btn-primary">
                        <i class="fas fa-plus-circle"></i>
                        New Visit
                    </a>
                </div>
            </div>

            <div class="content-grid">
                <!-- Employee Profile Card -->
                <div class="profile-card">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <h2 class="profile-name"><?php echo htmlspecialchars($worker['name']); ?></h2>
                        <p class="profile-role"><?php echo htmlspecialchars($worker['role']); ?></p>
                        
                        <a href="edit.php?id=<?php echo $worker['payroll_number']; ?>" class="btn-edit-profile">
                            <i class="fas fa-edit"></i>
                            Edit Details
                        </a>
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

                <!-- Medical History Section -->
                <div class="medical-history">
                    <div class="history-header">
                        <h2 class="history-title">Medical History</h2>
                        <a href="../medical/view.php?id=<?php echo $worker['payroll_number']; ?>" class="btn-action btn-primary">
                            <i class="fas fa-arrow-right"></i>
                            To History and Leave
                        </a>
                    </div>

                    <?php if (empty($medical_history)): ?>
                        <div class="no-records">
                            <i class="fas fa-notes-medical"></i>
                            <p>No medical records found</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($medical_history as $visit): ?>
                            <div class="visit-card">
                                <div class="visit-header">
                                    <div class="visit-date">
                                        <i class="fas fa-calendar"></i>
                                        <?php echo date('F j, Y', strtotime($visit['visit_date'])); ?>
                                    </div>
                                    <?php if (isset($visit['disease_classification'])): ?>
                                        <div class="visit-type">
                                            <i class="fas fa-tag"></i>
                                            <?php echo htmlspecialchars($visit['disease_classification']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="visit-details">
                                    <?php if (isset($visit['diagnosis'])): ?>
                                    <div class="detail-item">
                                        <span class="detail-label">
                                            <i class="fas fa-stethoscope"></i>
                                            Diagnosis
                                        </span>
                                        <span class="detail-value"><?php echo htmlspecialchars($visit['diagnosis']); ?></span>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (isset($visit['remarks'])): ?>
                                    <div class="detail-item">
                                        <span class="detail-label">
                                            <i class="fas fa-comment-medical"></i>
                                            Remarks
                                        </span>
                                        <span class="detail-value"><?php echo nl2br(htmlspecialchars($visit['remarks'])); ?></span>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (!empty($visit['prescriptions'])): ?>
                                    <div class="detail-item">
                                        <span class="detail-label">
                                            <i class="fas fa-pills"></i>
                                            Prescribed Medication
                                        </span>
                                        <div class="prescriptions-list">
                                            <?php 
                                            $prescriptions = explode('||', $visit['prescriptions']);
                                            foreach ($prescriptions as $prescription): 
                                            ?>
                                                <div class="prescription-item">
                                                    <i class="fas fa-capsules"></i>
                                                    <?php echo htmlspecialchars($prescription); ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (isset($visit['days_recommended'])): ?>
                                    <div class="leave-recommendation">
                                        <h4>
                                            <i class="fas fa-bed"></i>
                                            Sick Leave
                                            <span class="leave-label <?php echo $visit['payment_recommendation']; ?>">
                                                <?php 
                                                switch($visit['payment_recommendation']) {
                                                    case 'full_pay':
                                                        echo 'Paid Leave';
                                                        break;
                                                    case 'half_pay':
                                                        echo 'Half Pay';
                                                        break;
                                                    case 'no_pay':
                                                        echo 'Unpaid Leave';
                                                        break;
                                                    default:
                                                        echo 'Leave';
                                                }
                                                ?>
                                            </span>
                                        </h4>
                                        <div class="leave-details">
                                            <div class="leave-info">
                                                <span>Duration:</span>
                                                <div>
                                                    <?php echo htmlspecialchars($visit['days_recommended']); ?> days
                                                    (<?php echo date('M d', strtotime($visit['leave_start_date'])); ?> - 
                                                    <?php echo date('M d, Y', strtotime($visit['leave_end_date'])); ?>)
                                                </div>
                                            </div>
                                            <?php if ($visit['recommendation_reason']): ?>
                                            <div class="leave-info">
                                                <span>Reason:</span>
                                                <div><?php echo nl2br(htmlspecialchars($visit['recommendation_reason'])); ?></div>
                                            </div>
                                            <?php endif; ?>
                                            <div class="leave-info">
                                                <span>Status:</span>
                                                <span class="leave-status <?php echo $visit['leave_status']; ?>">
                                                    <?php echo ucfirst($visit['leave_status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
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