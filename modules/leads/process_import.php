<?php
$pageTitle = 'Import Results';
require_once '../../config/auth.php';
requireLogin();
require_once '../../config/db.php';
require_once '../../models/Lead.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$orgId = getOrgId();
$userId = getUserId();
$leadModel = new Lead($pdo);
$errorMsg = null;
$stats = [
    'total'    => 0,
    'imported' => 0,
    'skipped'  => 0,
    'failed'   => 0
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['temp_file'])) {
    $tempFile = $_POST['temp_file'];
    
    $mapName    = (int)($_POST['map_name'] ?? -1);
    $mapPhone   = (int)($_POST['map_phone'] ?? -1);
    $mapEmail   = $_POST['map_email'] !== '' ? (int)$_POST['map_email'] : -1;
    $mapCompany = $_POST['map_company'] !== '' ? (int)$_POST['map_company'] : -1;

    if ($mapName === -1 || $mapPhone === -1) {
        $errorMsg = "Critical Mapping Missing: You must allocate exactly which columns correspond to the Name and Phone fields.";
    } elseif (!file_exists($tempFile)) {
        $errorMsg = "The uploaded file has expired or was removed. Please upload it again.";
    } else {
        try {
            $ext = strtolower(pathinfo($tempFile, PATHINFO_EXTENSION));
            $rows = [];

            if ($ext === 'csv') {
                if (($handle = fopen($tempFile, "r")) !== false) {
                    // Auto-detect Regional Excel Delimiters (; vs , vs TAB)
                    $firstLine = fgets($handle);
                    rewind($handle);
                    $delimiter = ',';
                    $maxCount = 0;
                    foreach ([',', ';', "\t", '|'] as $delim) {
                        $count = substr_count($firstLine, $delim);
                        if ($count > $maxCount) {
                            $maxCount = $count;
                            $delimiter = $delim;
                        }
                    }

                    while (($data = fgetcsv($handle, 10000, $delimiter)) !== false) {
                        $rows[] = $data;
                    }
                    fclose($handle);
                }
            } else {
                if (file_exists('../../vendor/autoload.php')) require_once '../../vendor/autoload.php';
                $spreadsheet = IOFactory::load($tempFile);
                $rows = $spreadsheet->getActiveSheet()->toArray();
            }

            if (count($rows) > 1) {
                $stats['total'] = count($rows) - 1; // Assuming row 0 was headers
                
                // Process from Row 1
                for ($i = 1; $i < count($rows); $i++) {
                    $row = $rows[$i];
                    
                    $name    = trim((string)($row[$mapName] ?? ''));
                    $phone   = trim((string)($row[$mapPhone] ?? ''));
                    $email   = $mapEmail !== -1 ? trim((string)($row[$mapEmail] ?? '')) : '';
                    $company = $mapCompany !== -1 ? trim((string)($row[$mapCompany] ?? '')) : '';

                    if (empty($name) || empty($phone)) {
                        $stats['failed']++;
                        continue;
                    }

                    // Duplicate Check
                    $duplicates = $leadModel->findDuplicates($orgId, $phone, $email);
                    if (!empty($duplicates)) {
                        $stats['skipped']++;
                        continue;
                    }

                    // Insert Lead
                    $result = $leadModel->addLead([
                        'organization_id' => $orgId,
                        'name'            => $name,
                        'phone'           => $phone,
                        'email'           => $email,
                        'company'         => $company,
                        'source'          => 'import',
                        'status'          => 'New Lead',
                        'priority'        => 'Warm',
                        'user_id'         => $userId
                    ]);

                    if ($result) {
                        $stats['imported']++;
                    } else {
                        $stats['failed']++;
                    }
                }
            }

            // Cleanup
            @unlink($tempFile);

        } catch (Exception $e) {
            $errorMsg = "Execution Error: " . $e->getMessage();
        }
    }
} else {
    redirect(BASE_URL . 'modules/leads/');
}

include '../../includes/header.php';
?>

<div class="row justify-content-center mt-4">
    <div class="col-lg-8">
        <div class="d-flex align-items-center mb-4">
            <a href="<?= BASE_URL ?>modules/leads/" class="btn btn-light shadow-sm me-3 border"><i class="bi bi-arrow-left text-dark"></i></a>
            <div>
                <h4 class="mb-0 fw-bold">Import Summary</h4>
                <p class="mb-0 text-muted small">Bulk Lead Ingestion Results</p>
            </div>
        </div>

        <?php if ($errorMsg): ?>
            <div class="alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-25 d-flex align-items-center rounded-3 p-4">
                <i class="bi bi-exclamation-triangle-fill fs-3 text-danger me-3"></i>
                <div>
                    <strong class="d-block mb-1">Runtime Import Error</strong>
                    <span class="text-danger-emphasis"><?= htmlspecialchars($errorMsg) ?></span>
                </div>
            </div>
            <div class="text-center mt-4">
                <a href="<?= BASE_URL ?>modules/leads/" class="btn btn-outline-secondary px-4">Return to Leads</a>
            </div>
        <?php else: ?>
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="bg-primary bg-gradient p-4 text-white text-center">
                    <i class="bi bi-check-circle-fill display-4 mb-2"></i>
                    <h3 class="fw-bold mb-0">Import Completed</h3>
                </div>
                
                <div class="card-body p-4 p-md-5">
                    <div class="row g-4 text-center">
                        <div class="col-md-3 col-6">
                            <div class="p-3 border rounded-3 bg-light h-100">
                                <i class="bi bi-file-earmark-spreadsheet text-secondary fs-3 mb-2"></i>
                                <h2 class="fw-bold mb-0"><?= $stats['total'] ?></h2>
                                <span class="text-muted small text-uppercase fw-semibold">Total Rows</span>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="p-3 border rounded-3 bg-success bg-opacity-10 border-success border-opacity-25 h-100">
                                <i class="bi bi-person-plus-fill text-success fs-3 mb-2"></i>
                                <h2 class="fw-bold text-success mb-0"><?= $stats['imported'] ?></h2>
                                <span class="text-success-emphasis small text-uppercase fw-semibold">Imported</span>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="p-3 border rounded-3 bg-warning bg-opacity-10 border-warning border-opacity-25 h-100">
                                <i class="bi bi-files text-warning fs-3 mb-2"></i>
                                <h2 class="fw-bold text-warning mb-0"><?= $stats['skipped'] ?></h2>
                                <span class="text-warning-emphasis small text-uppercase fw-semibold">Duplicates Skipped</span>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="p-3 border rounded-3 bg-danger bg-opacity-10 border-danger border-opacity-25 h-100">
                                <i class="bi bi-x-circle text-danger fs-3 mb-2"></i>
                                <h2 class="fw-bold text-danger mb-0"><?= $stats['failed'] ?></h2>
                                <span class="text-danger-emphasis small text-uppercase fw-semibold">Failed Validation</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-5">
                        <a href="<?= BASE_URL ?>modules/leads/?source=import" class="btn btn-primary px-5 py-2 fw-semibold rounded-pill shadow-sm">
                            View Imported Leads <i class="bi bi-arrow-right ms-2"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
