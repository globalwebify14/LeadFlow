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
            // Strip hidden Excel UTF-8 BOM bytes (\xEF\xBB\xBF) and invisible spaces
            $colClean = preg_replace('/^[\xef\xbb\xbf]+/', '', trim((string)$colName));
            $colClean = strtolower($colClean);
            
            // Search mapping aliases
            foreach ($mapping as $targetField => $aliases) {
                if (in_array($colClean, $aliases) && !isset($columnMap[$targetField])) {
                    $columnMap[$targetField] = $index;
                    break;
                }
            }
        }
        
        // Fallback: If absolutely no known headers were found, assume standard 0=Name, 1=Phone format
        if (!isset($columnMap['name']) || !isset($columnMap['phone'])) {
            if (count($headerRow) >= 2) {
                // If the first row looks like data instead of headers, map by index
                $columnMap['name'] = 0;
                $columnMap['phone'] = 1;
                $columnMap['email'] = count($headerRow) > 2 ? 2 : null;
                $columnMap['company'] = count($headerRow) > 3 ? 3 : null;
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
            $stats['errors'][] = "Critical Columns Missing: Could not auto-detect the 'Name' and 'Phone' columns. Please ensure your headers match common names, or that column 1 is Name and column 2 is Phone.";
            return $stats;
        }

        $stats['total'] = count($rows) - 1;

        // Start processing records (Row 2 onwards if headers matched, or Row 1 if we had to fallback to indices)
        // If we defaulted to indices (0,1) and the first row is NOT "name", it's probably actual data.
        $startIndex = 1;
        $firstCell = strtolower(trim((string)($headerRow[0] ?? '')));
        if (!in_array($firstCell, ['name', 'full name', 'first name', 'customer name'])) {
            // It's likely actual data row 1 without headers
            $startIndex = 0;
            $stats['total'] = count($rows);
        }

        for ($i = $startIndex; $i < count($rows); $i++) {
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
