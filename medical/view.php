<?php
session_start();
require_once '../config/database.php';
require_once '../includes/leave_calculator.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
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
        header("Location: ../workers/search.php");
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
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    .styled-select option.low-stock {
        color: #e67e22;
        font-weight: 500;
    }
    .styled-select option:disabled {
        color: #999;
    }
    .styled-select option:checked {
        background-color: #27ae60 !important;
        color: white !important;
    }
    .payment-status {
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-weight: 500;
    }

    .payment-status.full_pay {
        background-color: #e3fcef;
        color: #27ae60;
    }

    .payment-status.half_pay {
        background-color: #fef5e9;
        color: #e67e22;
    }

    .payment-status.no_pay {
        background-color: #fee7e7;
        color: #e74c3c;
    }

    .leave-status {
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-weight: 500;
    }

    .leave-status.pending {
        background-color: #fef5e9;
        color: #e67e22;
    }

    .leave-status.approved {
        background-color: #e3fcef;
        color: #27ae60;
    }

    .leave-status.rejected {
        background-color: #fee7e7;
        color: #e74c3c;
    }

    .recommendation-details {
        margin-top: 1rem;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 8px;
        border-left: 4px solid #3498db;
    }

    .classification {
        margin-top: 0.5rem;
        color: #666;
    }

    .classification .badge {
        background-color: #e3f2fd;
        color: #1976d2;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.9em;
    }
    </style>
</head>
<body>
    <div class="layout">
        <?php 
        $base_url = '../'; // Set base URL for sidebar links
        include '../includes/sidebar.php'; 
        ?>

        <!-- Main Content -->
        <main class="main-content">
            <header class="top-bar">
                <div class="welcome-message">
                    <h2><?php echo htmlspecialchars($worker['name']); ?>'s Medical History</h2>
                    <p>Payroll Number: <?php echo htmlspecialchars($worker['payroll_number']); ?> | 
                       Department: <?php echo htmlspecialchars($worker['department']); ?></p>
                </div>
            </header>

            <div class="content-wrapper">
                <?php if ($error): ?>
                    <div class="error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['success'])): ?>
                    <div class="success">
                        <i class="fas fa-check-circle"></i>
                        Medical record added successfully
                    </div>
                <?php endif; ?>

                <!-- Add New Record Section -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-plus-circle"></i> Add New Medical Record</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" class="styled-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="visit_date">
                                        <i class="fas fa-calendar"></i>
                                        Visit Date
                                    </label>
                                    <input type="date" id="visit_date" name="visit_date" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="diagnosis">
                                    <i class="fas fa-stethoscope"></i>
                                    Diagnosis
                                </label>
                                <textarea id="diagnosis" name="diagnosis" rows="3" required></textarea>
                            </div>

                            <div class="form-group">
                                <label for="disease_classification">
                                    <i class="fas fa-tag"></i>
                                    Disease Classification
                                </label>
                                <?php
                                // Fetch disease classifications
                                $stmt = $pdo->prepare("SELECT id, name FROM disease_classifications ORDER BY name ASC");
                                $stmt->execute();
                                $classifications = $stmt->fetchAll();
                                ?>
                                <select name="disease_classification" id="disease_classification" required class="styled-select">
                                    <option value="">Select Classification</option>
                                    <?php foreach ($classifications as $classification): ?>
                                        <option value="<?php echo $classification['id']; ?>">
                                            <?php echo htmlspecialchars($classification['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="remarks">
                                    <i class="fas fa-comment-medical"></i>
                                    Remarks
                                </label>
                                <textarea id="remarks" name="remarks" rows="2"></textarea>
                            </div>

                            <div class="prescriptions-section">
                                <label>
                                    <i class="fas fa-pills"></i>
                                    Prescriptions
                                </label>
                                <div id="prescriptions-container">
                                    <div class="prescription-row">
                                        <select name="prescriptions[0][drug_name]" class="styled-select" required>
                                            <option value="">Select a drug...</option>
                                            <?php foreach ($available_drugs as $drug): ?>
                                                <option value="<?php echo htmlspecialchars($drug['name']); ?>"
                                                        <?php echo $drug['status'] === 'low_stock' ? 'class="low-stock"' : ''; ?>>
                                                    <?php echo htmlspecialchars($drug['name']); ?>
                                                    <?php echo $drug['status'] === 'low_stock' ? ' (Low Stock)' : ''; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="text" name="prescriptions[0][dosage]" 
                                               placeholder="Dosage" required>
                                        <button type="button" class="btn-icon" onclick="addPrescriptionRow()">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="leave-recommendation-section">
                                <label>
                                    <i class="fas fa-bed"></i>
                                    Leave Recommendation
                                </label>
                                <div class="recommendation-form">
                                    <div class="form-row">
                                        <div class="form-group half">
                                            <label for="leave_days">Number of Days</label>
                                            <input type="number" id="leave_days" name="leave_days" 
                                                   min="1" max="113" placeholder="Enter number of days">
                                        </div>
                                        <div class="form-group half">
                                            <label for="start_date">Start Date</label>
                                            <input type="date" id="start_date" name="start_date" 
                                                   value="<?php echo date('Y-m-d'); ?>">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="recommendation_reason">Reason for Leave</label>
                                        <textarea id="recommendation_reason" name="recommendation_reason" 
                                                  placeholder="Explain why leave is recommended"
                                                  rows="3"></textarea>
                                    </div>
                                </div>
                                <div class="info-box">
                                    <i class="fas fa-info-circle"></i>
                                    <p>Leave Entitlement Rules:
                                       <ul>
                                           <li>Total annual leave: 113 days</li>
                                           <li>First 55 days: Full pay</li>
                                           <li>Days 56-113: Half pay</li>
                                           <li>Beyond 113 days: No pay</li>
                                       </ul>
                                    </p>
                                </div>
                            </div>

                            <button type="submit" class="btn-primary">
                                <i class="fas fa-save"></i>
                                Save Medical Record
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Medical History Section -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h3><i class="fas fa-history"></i> Medical History</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($medical_records)): ?>
                            <div class="no-records">
                                <i class="fas fa-file-medical fa-3x"></i>
                                <p>No medical records found</p>
                            </div>
                        <?php else: ?>
                            <div class="timeline">
                                <?php foreach ($medical_records as $record): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-date">
                                            <i class="fas fa-calendar"></i>
                                            <?php echo date('M d, Y', strtotime($record['visit_date'])); ?>
                                        </div>
                                        <div class="timeline-content">
                                            <div class="diagnosis-details">
                                                <h4>Diagnosis</h4>
                                                <p><?php echo nl2br(htmlspecialchars($record['diagnosis'])); ?></p>
                                                <?php if ($record['disease_classification']): ?>
                                                    <p class="classification">
                                                        <i class="fas fa-tag"></i>
                                                        Classification: <span class="badge"><?php echo htmlspecialchars($record['disease_classification']); ?></span>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <?php if ($record['remarks']): ?>
                                                <h4>Remarks</h4>
                                                <p><?php echo nl2br(htmlspecialchars($record['remarks'])); ?></p>
                                            <?php endif; ?>

                                            <?php if ($record['prescriptions']): ?>
                                                <h4>Prescriptions</h4>
                                                <ul class="prescriptions-list">
                                                    <?php foreach (explode('||', $record['prescriptions']) as $prescription): ?>
                                                        <li><i class="fas fa-pills"></i> <?php echo htmlspecialchars($prescription); ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php endif; ?>

                                            <?php if ($record['days_recommended']): ?>
                                                <div class="recommendation-details">
                                                    <h4><i class="fas fa-bed"></i> Leave Recommendation</h4>
                                                    <p>Days Recommended: <?php echo htmlspecialchars($record['days_recommended']); ?></p>
                                                    <p>Payment Status: 
                                                        <span class="payment-status <?php echo $record['payment_recommendation']; ?>">
                                                            <?php echo ucfirst(str_replace('_', ' ', $record['payment_recommendation'])); ?>
                                                        </span>
                                                    </p>
                                                    <p>Period: <?php 
                                                        echo date('M d, Y', strtotime($record['leave_start_date'])) . ' to ' . 
                                                             date('M d, Y', strtotime($record['leave_end_date']));
                                                    ?></p>
                                                    <p>Reason: <?php echo nl2br(htmlspecialchars($record['recommendation_reason'])); ?></p>
                                                    <p>Status: <span class="leave-status <?php echo $record['leave_status']; ?>">
                                                        <?php echo ucfirst($record['leave_status']); ?>
                                                    </span></p>
                                                    
                                                    <!-- Add leave summary -->
                                                    <?php if (isset($record['used_days'])): ?>
                                                    <div class="leave-summary">
                                                        <h5>Leave Summary for <?php echo date('Y'); ?></h5>
                                                        <ul>
                                                            <li>Used: <?php echo $record['used_days']; ?> days</li>
                                                            <li>Remaining: <?php echo $record['remaining_days']; ?> days</li>
                                                            <li>Status: 
                                                                <?php if ($record['used_days'] < 55): ?>
                                                                    <span class="status-text">Within full pay period</span>
                                                                <?php elseif ($record['used_days'] < 113): ?>
                                                                    <span class="status-text">Within half pay period</span>
                                                                <?php else: ?>
                                                                    <span class="status-text">Exceeded annual entitlement</span>
                                                                <?php endif; ?>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
    let prescriptionCount = 1;

    function updateAvailableDrugs() {
        // Get all selected drugs except the current one
        const selectedDrugs = new Set();
        const selects = document.querySelectorAll('#prescriptions-container select');
        
        selects.forEach(select => {
            if (select.value) {
                selectedDrugs.add(select.value);
            }
        });

        // Update each select's options
        selects.forEach(select => {
            const currentValue = select.value;
            const options = Array.from(select.options);
            
            options.forEach(option => {
                // Skip the placeholder option
                if (!option.value) return;
                
                // Disable if drug is selected in another row
                if (selectedDrugs.has(option.value) && option.value !== currentValue) {
                    option.disabled = true;
                } else {
                    option.disabled = false;
                }

                // Add selected class for highlighting
                if (option.value === currentValue) {
                    option.style.backgroundColor = '#27ae60';
                    option.style.color = 'white';
                } else {
                    option.style.backgroundColor = '';
                    option.style.color = '';
                }
            });
        });
    }

    function addPrescriptionRow() {
        const container = document.getElementById('prescriptions-container');
        const newRow = document.createElement('div');
        newRow.className = 'prescription-row';
        
        // Get the drug options from the first select element
        const firstSelect = container.querySelector('select');
        const options = Array.from(firstSelect.options)
            .map(opt => {
                const className = opt.className ? ` class="${opt.className}"` : '';
                // Add disabled attribute if the option is already selected
                const isDisabled = opt.value && document.querySelector(`select[name^="prescriptions"] option[value="${opt.value}"]:checked`);
                const disabled = isDisabled ? ' disabled' : '';
                return `<option value="${opt.value}"${className}${disabled}>${opt.innerHTML}</option>`;
            })
            .join('');
        
        newRow.innerHTML = `
            <select name="prescriptions[${prescriptionCount}][drug_name]" class="styled-select" required 
                    onchange="updateAvailableDrugs()">
                ${options}
            </select>
            <input type="text" name="prescriptions[${prescriptionCount}][dosage]" 
                   placeholder="Dosage" required>
            <button type="button" class="btn-icon remove" onclick="removePrescriptionRow(this)">
                <i class="fas fa-minus"></i>
            </button>
        `;
        container.appendChild(newRow);
        prescriptionCount++;
    }

    function removePrescriptionRow(button) {
        button.parentElement.remove();
        // Update available options after removing a row
        updateAvailableDrugs();
    }

    // Add onchange handler to the first select
    document.querySelector('select[name="prescriptions[0][drug_name]"]')
        .addEventListener('change', updateAvailableDrugs);
    </script>
</body>
</html>