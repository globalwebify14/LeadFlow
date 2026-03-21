    <!-- Footer -->
    <footer class="lp-section py-5 bg-white border-top">
        <div class="lp-container">
            <div class="row g-5">
                <div class="col-lg-5">
                    <a href="<?= BASE_URL ?>index.php" class="lp-logo mb-4">
                        <i class="bi bi-rocket-takeoff-fill"></i> Lead<span>Flow</span>
                    </a>
                    <p class="mb-4 pe-lg-5" style="line-height: 1.8;">The modern multi-tenant SaaS CRM built to synchronize, segment, and close Real-Time Meta advertising leads with high precision and automation.</p>
                    <div class="d-flex gap-3">
                        <a href="#" class="lp-btn lp-btn-outline p-0 border-0 fs-4 text-muted"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="lp-btn lp-btn-outline p-0 border-0 fs-4 text-muted"><i class="bi bi-linkedin"></i></a>
                        <a href="#" class="lp-btn lp-btn-outline p-0 border-0 fs-4 text-muted"><i class="bi bi-facebook"></i></a>
                    </div>
                </div>
                <div class="col-6 col-lg-2">
                    <h5 class="fw-bold mb-4">Product</h5>
                    <ul class="list-unstyled d-flex flex-column gap-3">
                        <li><a href="#features" class="text-decoration-none text-muted">Features</a></li>
                        <li><a href="#pricing" class="text-decoration-none text-muted">Pricing</a></li>
                        <li><a href="#testimonials" class="text-decoration-none text-muted">Success Stories</a></li>
                        <li><a href="<?= BASE_URL ?>login.php" class="text-decoration-none text-muted">Login</a></li>
                    </ul>
                </div>
                <div class="col-6 col-lg-2">
                    <h5 class="fw-bold mb-4">Integration</h5>
                    <ul class="list-unstyled d-flex flex-column gap-3">
                        <li><a href="#" class="text-decoration-none text-muted">Meta Graph API</a></li>
                        <li><a href="#" class="text-decoration-none text-muted">OAuth Setup</a></li>
                        <li><a href="#" class="text-decoration-none text-muted">Webhooks</a></li>
                    </ul>
                </div>
                <div class="col-6 col-lg-3">
                    <h5 class="fw-bold mb-4">Legal</h5>
                    <ul class="list-unstyled d-flex flex-column gap-3">
                        <li><a href="<?= BASE_URL ?>privacy.php" class="text-decoration-none text-muted">Privacy Policy</a></li>
                        <li><a href="<?= BASE_URL ?>terms.php" class="text-decoration-none text-muted">Terms of Service</a></li>
                        <li><a href="<?= BASE_URL ?>data-deletion.php" class="text-decoration-none text-muted">Data Deletion</a></li>
                    </ul>
                </div>
            </div>
            <div class="pt-5 mt-5 border-top text-center text-muted small">
                <p class="mb-0">&copy; <?= date('Y') ?> LeadFlow SaaS. All rights reserved by Global Webify.</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 900,
            once: true,
            offset: 100
        });

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            let nav = document.getElementById('mainNav');
            if (window.scrollY > 50) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }
        });
    </script>
</body>
</html>
