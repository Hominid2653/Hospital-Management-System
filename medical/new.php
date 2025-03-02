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
        header("Location: " . url('workers/list.php'));
        exit();
    }

    // Get available drugs
    $stmt = $pdo->prepare("
        SELECT name, status 
        FROM drugs 
        WHERE status != 'out_of_stock'
        ORDER BY name ASC
    ");
    $stmt->execute();
    $available_drugs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get disease classifications
    $stmt = $pdo->prepare("SELECT * FROM disease_classifications ORDER BY name ASC");
    $stmt->execute();
    $disease_classifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle form submission
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
            
            // Handle leave recommendation
            if (!empty($_POST['leave_days'])) {
                $calculation = calculateLeavePayment(
                    $worker_id,
                    $_POST['leave_days'],
                    $_POST['start_date']
                );
                
                $end_date = date('Y-m-d', strtotime($_POST['start_date'] . ' + ' . ($_POST['leave_days'] - 1) . ' days'));
                
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
            
            // Redirect back to worker profile
            header("Location: " . url('workers/view.php?id=' . $worker_id . '&success=1'));
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error adding medical record: " . $e->getMessage();
        }
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Medical Visit - <?php echo htmlspecialchars($worker['name']); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .medical-form {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin: 2rem auto;
            max-width: 800px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .form-header {
            margin-bottom: 2rem;
            text-align: center;
        }

        .form-header h1 {
            color: var(--primary);
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .form-header p {
            color: var(--text-secondary);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .form-group label i {
            color: var(--primary);
            width: 16px;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(231, 84, 128, 0.1);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 2rem 0 1rem;
        }

        .prescription-row {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .leave-section {
            background: var(--secondary-light);
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 1.5rem;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn-submit, .btn-cancel {
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .btn-submit {
            background: var(--primary);
            color: white;
        }

        .btn-cancel {
            background: var(--surface);
            color: var(--text-primary);
        }

        .btn-submit:hover, .btn-cancel:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: white;
            color: var(--text-primary);
            border-radius: 25px;
            text-decoration: none;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            border: 1px solid var(--border);
        }

        .btn-back:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            color: var(--primary);
            border-color: var(--primary);
        }

        .btn-add {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-add:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-icon.remove {
            background: #ff5757;
            color: white;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-icon.remove:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .prescription-row {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 1rem;
            margin-bottom: 1rem;
            align-items: start;
        }
    </style>
</head>
<body>
    <div class="layout">
        <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="section-header">
                <div>
                    <h1>New Medical Visit</h1>
                    <p>Record a new medical visit for <?php echo htmlspecialchars($worker['name']); ?></p>
                </div>
                <a href="../workers/view.php?id=<?php echo $worker_id; ?>" class="btn-back">
                    <i class="fas fa-arrow-left"></i>
                    Back to Profile
                </a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- New Visit Form -->
            <div class="medical-form">
                <form method="POST" class="modern-form">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="visit_date">
                                <i class="fas fa-calendar"></i>
                                Visit Date
                            </label>
                            <input type="date" 
                                   id="visit_date" 
                                   name="visit_date" 
                                   class="form-control"
                                   value="<?php echo date('Y-m-d'); ?>" 
                                   required>
                        </div>

                        
                        <div class="form-group">
                            <label for="disease_classification">
                                <i class="fas fa-tag"></i>
                                Disease Classification
                            </label>
                            <select id="disease_classification" 
                                    name="disease_classification" 
                                    class="form-control" 
                                    required>
                                <option value="">Select Classification</option>
                                <?php foreach ($disease_classifications as $dc): ?>
                                    <option value="<?php echo $dc['id']; ?>">
                                        <?php echo htmlspecialchars($dc['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group full-width">
                            <label for="diagnosis">
                                <i class="fas fa-stethoscope"></i>
                                Diagnosis
                            </label>
                            <textarea id="diagnosis" 
                                     name="diagnosis" 
                                     class="form-control"
                                     rows="3" 
                                     required></textarea>
                        </div>

                        <div class="form-group full-width">
                            <label for="remarks">
                                <i class="fas fa-comment-medical"></i>
                                Remarks
                            </label>
                            <textarea id="remarks" 
                                     name="remarks" 
                                     class="form-control"
                                     rows="3"></textarea>
                        </div>

                        <!-- Prescriptions Section -->
                        <div class="form-group full-width">
                            <div class="section-header">
                                <label>
                                    <i class="fas fa-pills"></i>
                                    Prescriptions
                                </label>
                            </div>
                            <div id="prescriptions-container">
                                <div class="prescription-row">
                                    <select name="prescriptions[0][drug_name]" 
                                            class="form-control" 
                                            onchange="updateAvailableDrugs()">
                                        <option value="">Select Medication</option>
                                        <?php foreach ($available_drugs as $drug): ?>
                                            <option value="<?php echo htmlspecialchars($drug['name']); ?>"
                                                    class="<?php echo $drug['status']; ?>">
                                                <?php echo htmlspecialchars($drug['name']); ?>
                                                <?php echo $drug['status'] === 'low_stock' ? ' (Low Stock)' : ''; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="text" 
                                           name="prescriptions[0][dosage]" 
                                           placeholder="Dosage"
                                           class="form-control">
                                    <button type="button" class="btn-icon add" onclick="addPrescriptionRowAfter(this)">
                                        <i class="fas fa-arrow-down"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="add-prescription">
                                <button type="button" class="btn-add" onclick="addPrescriptionRow()">
                                    <i class="fas fa-plus"></i>
                                    
                                </button>
                            </div>
                        </div>

                        <!-- Leave Recommendation Section -->
                        <div class="form-group full-width">
                            <div class="leave-section">
                                <div class="section-header">
                                    <label>
                                        <i class="fas fa-bed"></i>
                                        Leave Recommendation
                                    </label>
                                </div>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="leave_days">Number of Days</label>
                                        <input type="number" 
                                               id="leave_days" 
                                               name="leave_days" 
                                               min="1" 
                                               class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label for="start_date">Start Date</label>
                                        <input type="date" 
                                               id="start_date" 
                                               name="start_date" 
                                               class="form-control">
                                    </div>
                                    <div class="form-group full-width">
                                        <label for="recommendation_reason">Reason</label>
                                        <textarea id="recommendation_reason" 
                                                name="recommendation_reason" 
                                                class="form-control"
                                                rows="2"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-save"></i>
                            Save Record
                        </button>
                        <a href="../workers/view.php?id=<?php echo $worker_id; ?>" class="btn-cancel">
                            <i class="fas fa-times"></i>
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script src="../assets/js/menu.js"></script>
    <script>
    let prescriptionCount = 1;

    function updateAvailableDrugs() {
        const selectedDrugs = new Set();
        const selects = document.querySelectorAll('#prescriptions-container select');
        
        // Get all selected drugs (only if they have a value)
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
                if (!option.value) return; // Skip placeholder option
                
                // Only disable if drug is selected in another field
                if (selectedDrugs.has(option.value) && option.value !== currentValue) {
                    option.disabled = true;
                    option.style.backgroundColor = '#f5f5f5';
                } else {
                    option.disabled = false;
                    option.style.backgroundColor = '';
                }

                // Highlight selected option
                if (option.value === currentValue) {
                    option.style.backgroundColor = 'var(--primary)';
                    option.style.color = 'white';
                } else {
                    option.style.color = '';
                }
            });
        });

        // Show/hide the Add button based on empty fields
        const addButton = document.querySelector('.btn-add');
        const hasEmptyField = Array.from(selects).some(select => !select.value);
        const allDrugsSelected = selectedDrugs.size >= <?php echo count($available_drugs); ?>;
        
        // Only hide the add button if all drugs are selected AND there are no empty fields
        addButton.style.display = (allDrugsSelected && !hasEmptyField) ? 'none' : 'flex';
    }

    function addPrescriptionRow() {
        const container = document.getElementById('prescriptions-container');
        const selects = container.querySelectorAll('select');
        
        // Check for any empty fields first
        const hasEmptyField = Array.from(selects).some(select => !select.value);
        
        // If there's an empty field, focus on it instead of creating a new row
        if (hasEmptyField) {
            const firstEmptySelect = Array.from(selects).find(select => !select.value);
            firstEmptySelect.focus();
            return;
        }

        // If all fields are filled, create a new row
        const newRow = document.createElement('div');
        newRow.className = 'prescription-row';
        
        const firstSelect = container.querySelector('select');
        const options = Array.from(firstSelect.options)
            .map(opt => {
                const className = opt.className ? ` class="${opt.className}"` : '';
                const isDisabled = opt.value && document.querySelector(`select[name^="prescriptions"] option[value="${opt.value}"]:checked`);
                const disabled = isDisabled ? ' disabled' : '';
                return `<option value="${opt.value}"${className}${disabled}>${opt.innerHTML}</option>`;
            })
            .join('');
        
        newRow.innerHTML = `
            <select name="prescriptions[${prescriptionCount}][drug_name]" 
                    class="form-control" 
                    onchange="updateAvailableDrugs()">
                ${options}
            </select>
            <input type="text" 
                   name="prescriptions[${prescriptionCount}][dosage]" 
                   placeholder="Dosage"
                   class="form-control">
        `;
        container.appendChild(newRow);
        prescriptionCount++;
        
        // Focus the new select field
        const newSelect = newRow.querySelector('select');
        newSelect.focus();
        
        updateAvailableDrugs();
    }

    function addPrescriptionRowAfter(button) {
        const container = document.getElementById('prescriptions-container');
        const currentRow = button.closest('.prescription-row');
        const newRow = document.createElement('div');
        newRow.className = 'prescription-row';
        
        // Get the drug options from the first select element
        const firstSelect = container.querySelector('select');
        const options = Array.from(firstSelect.options)
            .map(opt => {
                const className = opt.className ? ` class="${opt.className}"` : '';
                const isDisabled = opt.value && document.querySelector(`select[name^="prescriptions"] option[value="${opt.value}"]:checked`);
                const disabled = isDisabled ? ' disabled' : '';
                return `<option value="${opt.value}"${className}${disabled}>${opt.innerHTML}</option>`;
            })
            .join('');
        
        newRow.innerHTML = `
            <select name="prescriptions[${prescriptionCount}][drug_name]" 
                    class="form-control" 
                    onchange="updateAvailableDrugs()">
                ${options}
            </select>
            <input type="text" 
                   name="prescriptions[${prescriptionCount}][dosage]" 
                   placeholder="Dosage"
                   class="form-control">
            <button type="button" class="btn-icon add" onclick="addPrescriptionRowAfter(this)">
                <i class="fas fa-arrow-down"></i>
            </button>
        `;
        
        // Insert the new row after the current row
        currentRow.insertAdjacentElement('afterend', newRow);
        prescriptionCount++;
        
        // Focus the new select field
        const newSelect = newRow.querySelector('select');
        newSelect.focus();
        
        updateAvailableDrugs();
    }

    // Initialize the first select
    document.querySelector('select[name="prescriptions[0][drug_name]"]')
        .addEventListener('change', updateAvailableDrugs);
    </script>
</body>
</html> 