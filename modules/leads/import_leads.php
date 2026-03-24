<?php
$pageTitle = 'Map Import Columns';
require_once '../../config/auth.php';
requireLogin();
require_once '../../config/db.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$orgId = getOrgId();
$userId = getUserId();
$errorMsg = null;
$headers = [];
$tempFilePath = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, ['csv', 'xls', 'xlsx'])) {
        $errorMsg = "Invalid file type. Only .csv and .xlsx allowed.";
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = "Upload failed. Code: " . $file['error'];
    } else {
        // Safely move the uploaded file to the native OS Temporary Directory
        $tempFilePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'temp_import_' . $orgId . '_' . time() . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $tempFilePath)) {
            $errorMsg = "Server Permission Error: Could not save the uploaded file to the temporary server drive. Please check disk space or permissions!";
        } else {
            try {
            if ($ext === 'csv') {
                if (($handle = fopen($tempFilePath, "r")) !== false) {
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
                    
                    $headers = fgetcsv($handle, 10000, $delimiter);
                    
                    // Strip hidden BOM characters from the very first header
                    if (!empty($headers[0])) {
                        $headers[0] = preg_replace('/^[\xef\xbb\xbf]+/', '', trim((string)$headers[0]));
                    }
                    
                    fclose($handle);
                }
            } else {
                if (file_exists('../../vendor/autoload.php')) require_once '../../vendor/autoload.php';
                if (!class_exists(IOFactory::class)) throw new Exception("PhpSpreadsheet is missing. Save file as .CSV instead!");

                $spreadsheet = IOFactory::load($tempFilePath);
                $worksheet = $spreadsheet->getActiveSheet();
                $headers = $worksheet->toArray()[0] ?? [];
            }

            if (empty($headers)) throw new Exception("Could not read any column headers from the file. The file may be empty or corrupted.");
        } catch (Exception $e) {
            $errorMsg = "Processing Error: " . $e->getMessage();
            @unlink($tempFilePath);
        }
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
                <h4 class="mb-0 fw-bold">Map Columns</h4>
                <p class="mb-0 text-muted small">Match your file's columns to the CRM fields</p>
            </div>
        </div>

        <?php if ($errorMsg): ?>
            <div class="alert alert-danger p-4 rounded-3"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($errorMsg) ?></div>
        <?php elseif (!empty($headers)): ?>
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="bg-primary bg-gradient p-3 text-white text-center">
                    <h5 class="fw-bold mb-0">Select Matching Columns</h5>
                </div>
                
                <div class="card-body p-4 p-md-5">
                    <form action="process_import.php" method="POST">
                        <input type="hidden" name="temp_file" value="<?= htmlspecialchars($tempFilePath) ?>">
                        
                        <?php 
                        $fields = [
                            'name' => ['label' => 'Full Name', 'req' => true],
                            'phone' => ['label' => 'Phone Number', 'req' => true],
                            'email' => ['label' => 'Email Address', 'req' => false],
                            'company' => ['label' => 'Company Name', 'req' => false]
                        ];
                        
                        foreach ($fields as $key => $field): ?>
                            <div class="row mb-3 align-items-center">
                                <div class="col-md-4 text-md-end">
                                    <label class="form-label fw-bold mb-0"><?= $field['label'] ?> <?= $field['req'] ? '<span class="text-danger">*</span>' : '' ?></label>
                                </div>
                                <div class="col-md-8">
                                    <select class="form-select" name="map_<?= $key ?>" <?= $field['req'] ? 'required' : '' ?>>
                                        <option value="">-- Ignore / Not in File --</option>
                                        <?php foreach ($headers as $index => $colName): 
                                            // Auto-select prediction based on common words
                                            $clean = strtolower(preg_replace('/[^a-zA-Z]/', '', (string)$colName));
                                            $selected = '';
                                            if ($key === 'name' && strpos($clean, 'name') !== false) $selected = 'selected';
                                            if ($key === 'phone' && (strpos($clean, 'phone') !== false || strpos($clean, 'mobile') !== false)) $selected = 'selected';
                                            if ($key === 'email' && strpos($clean, 'email') !== false) $selected = 'selected';
                                            if ($key === 'company' && (strpos($clean, 'company') !== false || strpos($clean, 'org') !== false)) $selected = 'selected';
                                        ?>
                                            <option value="<?= $index ?>" <?= $selected ?>><?= htmlspecialchars((string)$colName ?: "Column " . ($index+1)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="text-center mt-5 border-top pt-4">
                            <button type="submit" class="btn btn-primary px-5 py-2 fw-semibold rounded-pill shadow-sm">
                                <i class="bi bi-rocket-takeoff me-2"></i> Import Matches
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
