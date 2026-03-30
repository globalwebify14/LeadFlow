<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle . ' — LeadFlow' : 'LeadFlow — Advanced Lead Management SaaS' ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Premium Landing CSS -->
    <link href="<?= BASE_URL ?>assets/css/landing-premium.css?v=<?= time() ?>" rel="stylesheet">
</head>
<body class="lp-body">

    <!-- Navbar -->
    <nav class="lp-nav" id="mainNav">
        <div class="lp-container">
            <div class="lp-nav-content">
                <a class="lp-logo" href="<?= BASE_URL ?>index.php">
                    <i class="bi bi-rocket-takeoff-fill"></i> Lead<span>Flow</span>
                </a>
                
                <div class="lp-nav-links d-none d-lg-flex">
                    <a href="<?= BASE_URL ?>index.php#features">Features</a>
                    <a href="<?= BASE_URL ?>index.php#pricing">Pricing</a>
                    <a href="<?= BASE_URL ?>index.php#testimonials">Testimonials</a>
                    <a href="<?= BASE_URL ?>docs.php">User Guide</a>
                    <a href="<?= BASE_URL ?>login.php" class="lp-btn lp-btn-outline" style="padding: 10px 24px; font-size: 14px;">Sign In</a>
                    <a href="<?= BASE_URL ?>login.php" class="lp-btn lp-btn-primary" style="padding: 10px 24px; font-size: 14px;">Get Started</a>
                </div>

                <button class="d-lg-none btn border-0 fs-3" type="button" data-bs-toggle="collapse" data-bs-target="#mobileMenu">
                    <i class="bi bi-list"></i>
                </button>
            </div>
        </div>
        
        <!-- Mobile Menu -->
        <div class="collapse d-lg-none bg-white p-4 border-bottom shadow-sm" id="mobileMenu">
            <div class="d-flex flex-column gap-3">
                <a href="<?= BASE_URL ?>index.php#features" class="text-decoration-none text-dark fw-medium">Features</a>
                <a href="<?= BASE_URL ?>index.php#pricing" class="text-decoration-none text-dark fw-medium">Pricing</a>
                <a href="<?= BASE_URL ?>docs.php" class="text-decoration-none text-dark fw-medium">User Guide</a>
                <a href="<?= BASE_URL ?>login.php" class="lp-btn lp-btn-primary w-100 justify-content-center">Get Started</a>
            </div>
        </div>
    </nav>
