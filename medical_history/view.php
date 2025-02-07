<div class="layout">
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <!-- Add header at the top of main content -->
        <header class="top-bar">
            <div class="welcome-message">
                <h2>Medical History</h2>
                <p>View and manage worker's medical records</p>
            </div>
            <div class="action-buttons">
                <a href="export_history.php?id=<?php echo htmlspecialchars($worker_id); ?>" 
                   class="btn-export" title="Export Medical History">
                    <i class="fas fa-file-csv"></i>
                    Export History
                </a>
            </div>
        </header>

        <div class="content-wrapper">
            <!-- In the prescription form section -->
            <div class="form-group">
                <label for="drug">
                    <i class="fas fa-pills"></i>
                    Select Drug
                </label>
                <select name="drug_name" id="drug" class="styled-select" required>
                    <option value="">Select a drug...</option>
                    <?php
                    // Fetch available drugs (in stock and low stock only)
                    $stmt = $pdo->query("
                        SELECT name 
                        FROM drugs 
                        WHERE status != 'out_of_stock'
                        ORDER BY name ASC
                    ");
                    $available_drugs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($available_drugs as $drug): ?>
                        <option value="<?php echo htmlspecialchars($drug['name']); ?>">
                            <?php echo htmlspecialchars($drug['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="dosage">
                    <i class="fas fa-prescription"></i>
                    Dosage Instructions
                </label>
                <input type="text" id="dosage" name="dosage" 
                       placeholder="e.g., 1 tablet 3 times daily after meals" required>
            </div>

            <!-- Sick Leave Recommendation Section -->
            <div class="form-group">
                <label>
                    <i class="fas fa-bed"></i>
                    Sick Leave Recommendation
                </label>
                <div class="sick-leave-form">
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="sick_leave_days">Number of Days</label>
                            <input type="number" id="sick_leave_days" name="sick_leave_days" 
                                   min="1" max="21" placeholder="Enter number of days">
                        </div>
                        <div class="form-group half">
                            <label for="start_date">Start Date</label>
                            <input type="date" id="start_date" name="start_date" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="recommendation_reason">Reason for Recommendation</label>
                        <textarea id="recommendation_reason" name="recommendation_reason" 
                                  placeholder="Explain the reason for sick leave and payment recommendation"
                                  rows="3"></textarea>
                    </div>
                </div>
                <div class="info-box">
                    <i class="fas fa-info-circle"></i>
                    <p>As per Kenyan law:
                       <ul>
                           <li>21 days of sick leave per year after 2 months of service</li>
                           <li>First 7 days: Full pay</li>
                           <li>Next 14 days: Half pay</li>
                       </ul>
                    </p>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Add these styles if not already present -->
<style>
.action-buttons {
    display: flex;
    gap: 1rem;
    margin-left: auto;
}

.btn-export {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background-color: #27ae60;
    color: white;
    border: none;
    border-radius: 4px;
    text-decoration: none;
    font-size: 0.9rem;
    transition: background-color 0.3s;
    cursor: pointer;
}

.btn-export:hover {
    background-color: #219a52;
}

.btn-export i {
    font-size: 1rem;
}
</style> 