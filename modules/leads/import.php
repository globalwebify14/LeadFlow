<?php
require_once '../../includes/SimpleXLSX.php';

require_once '../../config/auth.php';
requireLogin();
require_once '../../config/db.php';
require_once '../../models/Lead.php';

// Removed Composer autoloader check

$orgId = getOrgId();
$userId = getUserId();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $allowed_extensions = ['csv', 'xls', 'xlsx'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($file_ext, $allowed_extensions)) {
        $error = "Invalid file format. Please upload a CSV or Excel file.";
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $error = "File upload error. Error code: " . $file['error'];
    } else {
        $leadModel = new Lead($pdo);
        $importedCount = 0;
        $row = 1;

        if ($file_ext === 'csv') {
            if (($handle = fopen($file['tmp_name'], "r")) !== FALSE) {
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    // Skip header row
                    if ($row == 1) { $row++; continue; }
                    
                    // Expected Format: Name | Phone | Email | Source | Note
                    $name = trim($data[0] ?? '');
                    $phone = trim($data[1] ?? '');
                    $email = trim($data[2] ?? '');
                    $source = trim($data[3] ?? '');
                    $note = trim($data[4] ?? '');

                    if (!empty($name) && !empty($phone)) {
                        $result = $leadModel->addLead([
                            'organization_id' => $orgId,
                            'name' => $name,
                            'phone' => $phone,
                            'email' => $email,
                            'source' => $source,
                            'note' => $note,
                            'status' => 'New Lead',
                            'user_id' => $userId
                        ]);
                        if ($result) {
                            $importedCount++;
                        }
                    }
                    $row++;
                }
                fclose($handle);
                $success = "Successfully imported $importedCount leads from CSV.";
            } else {
                $error = "Failed to open the uploaded CSV file.";
            }
        } else {
            // Excel import via SimpleXLSX
            try {
                if ($xlsx = \Shuchkin\SimpleXLSX::parse($file['tmp_name'])) {
                    $rows = $xlsx->rows();
                } else {
                    throw new Exception("Excel Parsing Error: " . \Shuchkin\SimpleXLSX::parseError());
                }
                    
                    foreach ($rows as $index => $data) {
                        if ($index === 0) continue; // Skip header
                        
                        $name = trim($data[0] ?? '');
                        $phone = trim($data[1] ?? '');
                        $email = trim($data[2] ?? '');
                        $source = trim($data[3] ?? '');
                        $note = trim($data[4] ?? '');

                        if (!empty($name) && !empty($phone)) {
                            $result = $leadModel->addLead([
                                'organization_id' => $orgId,
                                'name' => $name,
                                'phone' => $phone,
                                'email' => $email,
                                'source' => $source,
                                'note' => $note,
                                'status' => 'New Lead',
                                'user_id' => $userId
                            ]);
                            if ($result) {
                                $importedCount++;
                            }
                        }
                    }
                    $success = "Successfully imported $importedCount leads from Excel.";
                } catch (Exception $e) {
                    $error = "Error processing Excel file: " . $e->getMessage();
                }
        }
    }
}

include '../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white pb-0 border-0 pt-4">
                <h4 class="mb-0 fw-bold"><i class="bi bi-file-earmark-excel text-success me-2"></i>Import Leads</h4>
            </div>
            <div class="card-body p-4">
                <?php if($error): ?>
                    <div class="alert alert-danger d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <?php if($success): ?>
                    <div class="alert alert-success d-flex align-items-center">
                        <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <div class="alert alert-info border-0 bg-light text-dark mb-4 p-4 rounded">
                    <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-2 text-primary"></i>Expected File Format</h6>
                    <p class="mb-2">Your CSV or Excel file should have the following columns in exact order (include a header row):</p>
                    <table class="table table-bordered table-sm bg-white mt-2 mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name (Required)</th>
                                <th>Phone (Required)</th>
                                <th>Email</th>
                                <th>Source</th>
                                <th>Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Rahul Sharma</td>
                                <td>9876543210</td>
                                <td>rahul@example.com</td>
                                <td>Website</td>
                                <td>Interested in services</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Upload File (.csv, .xls, .xlsx)</label>
                        <input class="form-control mb-2" type="file" name="file" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" required>
                        <div class="form-text">If you experience issues with Excel (.xlsx) files, please save your file as CSV (Comma delimited) and upload the CSV.</div>
                    </div>
                    <hr>
                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-upload me-2"></i>Import Leads Data</button>
                    <a href="<?= BASE_URL ?>modules/leads/" class="btn btn-outline-secondary ms-2">Go to Leads</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
