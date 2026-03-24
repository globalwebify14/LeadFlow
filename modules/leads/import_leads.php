<?php
$pageTitle = 'Import Results';
require_once '../../config/auth.php';
requireLogin();
require_once '../../config/db.php';
require_once 'process_import.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$orgId = getOrgId();
$userId = getUserId();
$stats = null;
$errorMsg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, ['csv', 'xls', 'xlsx'])) {
        $errorMsg = "Invalid file type. Only .csv and .xlsx allowed.";
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = "Upload failed. Code: " . $file['error'];
    } else {
        // Attempt to parse via PhpSpreadsheet
        try {
            if (file_exists('../../vendor/autoload.php')) {
                require_once '../../vendor/autoload.php';
            }
            if (!class_exists(IOFactory::class)) {
                throw new Exception("PhpSpreadsheet is missing. Please run 'composer require phpoffice/phpspreadsheet' on the server.");
            }

            $spreadsheet = IOFactory::load($file['tmp_name']);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            // Execute Business Logic
            $importer = new ExcelImporter($pdo, $orgId, $userId);
            $stats = $importer->processRows($rows);

        } catch (Exception $e) {
            $errorMsg = "Excel Processing Error: " . $e->getMessage();
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
                    <strong class="d-block mb-1">Fatal Import Error</strong>
                    <span class="text-danger-emphasis"><?= htmlspecialchars($errorMsg) ?></span>
                </div>
            </div>
        <?php elseif ($stats): ?>
            <!-- Results Dashboard -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="bg-primary bg-gradient p-4 text-white text-center">
                    <i class="bi bi-check-circle-fill display-4 mb-2"></i>
                    <h3 class="fw-bold mb-0">Import Completed</h3>
                </div>
                
                <div class="card-body p-4 p-md-5">
                    <?php if (!empty($stats['errors'])): ?>
                        <div class="alert alert-warning border-0 rounded-3 mb-4">
                            <strong><i class="bi bi-exclamation-circle me-1"></i> Mapping Warnings:</strong>
                            <ul class="mb-0 mt-2">
                                <?php foreach($stats['errors'] as $err): ?>
                                    <li><?= htmlspecialchars($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

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
