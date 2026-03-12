<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle . ' — Lead CRM' : 'Lead CRM — Advanced Lead Management SaaS' ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- Global CRM Stylesheet -->
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
    
    <!-- End Head -->
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg <?= isset($isLanding) && $isLanding ? 'fixed-top' : 'border-bottom sticky-top bg-white' ?>" id="navbar">
        <div class="container">
            <a class="navbar-brand" href="<?= BASE_URL ?>index.php"><i class="bi bi-rocket-takeoff me-2"></i>LEAD <span>CRM</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>index.php#features">Platform Features</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>index.php#hierarchy">Roles Structure</a></li>
                    <li class="nav-item ms-lg-4">
                        <a href="<?= BASE_URL ?>login.php" class="btn btn-login"><i class="bi bi-box-arrow-in-right me-2"></i> Login to Portal</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
