<?php
// modules/leads/process_import.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../models/Lead.php';

class ExcelImporter {
    private $pdo;
    private $leadModel;
    private $orgId;
    private $userId;

    public function __construct($pdo, $orgId, $userId) {
        $this->pdo = $pdo;
        $this->leadModel = new Lead($pdo);
        $this->orgId = $orgId;
        $this->userId = $userId;
    }

    /**
     * Maps Excel header row dynamically based on common aliases
     */
    private function mapHeaders($headerRow) {
        $mapping = [
            'name'    => ['name', 'full name', 'customer name', 'first name', 'lead name'],
            'phone'   => ['phone', 'mobile', 'phone number', 'contact number', 'whatsapp'],
            'email'   => ['email', 'email address', 'e-mail'],
            'company' => ['company', 'organization', 'business', 'company name']
        ];

        $columnMap = []; // format: 'name' => index (0, 1, 2, etc)

        foreach ($headerRow as $index => $colName) {
            $colClean = strtolower(trim((string)$colName));
            
            // Search mapping aliases
            foreach ($mapping as $targetField => $aliases) {
                if (in_array($colClean, $aliases) && !isset($columnMap[$targetField])) {
                    $columnMap[$targetField] = $index;
                    break;
                }
            }
        }
        return $columnMap;
    }

    /**
     * Process an array of rows from PhpSpreadsheet
     */
    public function processRows($rows) {
        $stats = [
            'total'    => 0,
            'imported' => 0,
            'skipped'  => 0,
            'failed'   => 0,
            'errors'   => []
        ];

        if (empty($rows) || count($rows) < 2) {
            $stats['errors'][] = "The file appears to be empty or missing data rows.";
            return $stats;
        }

        // Row 1 is header
        $headerRow = $rows[0];
        $columnMap = $this->mapHeaders($headerRow);

        if (!isset($columnMap['name']) || !isset($columnMap['phone'])) {
            $stats['errors'][] = "Critical Columns Missing: Could not auto-detect 'Name' and 'Phone' columns. Please ensure your headers match common names.";
            return $stats;
        }

        $stats['total'] = count($rows) - 1;

        // Start processing records (Row 2 onwards)
        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            
            // Map values
            $nameIdx = $columnMap['name'] ?? null;
            $phoneIdx = $columnMap['phone'] ?? null;
            $emailIdx = $columnMap['email'] ?? null;
            $compIdx  = $columnMap['company'] ?? null;

            $name    = isset($nameIdx) ? trim((string)($row[$nameIdx] ?? '')) : '';
            $phone   = isset($phoneIdx) ? trim((string)($row[$phoneIdx] ?? '')) : '';
            $email   = isset($emailIdx) ? trim((string)($row[$emailIdx] ?? '')) : '';
            $company = isset($compIdx) ? trim((string)($row[$compIdx] ?? '')) : '';

            // 1. Validate required
            if (empty($name) || empty($phone)) {
                $stats['failed']++;
                continue;
            }

            // 2. Duplicate Check
            $duplicates = $this->leadModel->findDuplicates($this->orgId, $phone, $email);
            if (!empty($duplicates)) {
                $stats['skipped']++;
                continue;
            }

            // 3. Insert Lead
            $result = $this->leadModel->addLead([
                'organization_id' => $this->orgId,
                'name'            => $name,
                'phone'           => $phone,
                'email'           => $email,
                'company'         => $company,
                'source'          => 'import',
                'status'          => 'New Lead',
                'priority'        => 'Warm',
                'user_id'         => $this->userId
            ]);

            if ($result) {
                $stats['imported']++;
            } else {
                $stats['failed']++;
            }
        }

        return $stats;
    }
}
?>
