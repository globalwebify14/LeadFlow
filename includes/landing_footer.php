    <!-- Footer -->
    <footer class="footer mt-auto">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <a href="<?= BASE_URL ?>index.php" class="footer-brand"><i class="bi bi-rocket-takeoff me-2"></i>LEAD <span>CRM</span></a>
                    <p class="footer-desc">The modern multi-tenant SaaS CRM built to synchronize, segment, and close Real-Time Meta advertising leads.</p>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-muted fs-5"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="text-muted fs-5"><i class="bi bi-linkedin"></i></a>
                        <a href="#" class="text-muted fs-5"><i class="bi bi-facebook"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 mb-4 mb-md-0">
                    <h5 class="footer-title">Product</h5>
                    <ul class="footer-links">
                        <li><a href="<?= BASE_URL ?>index.php#features">Features</a></li>
                        <li><a href="<?= BASE_URL ?>index.php#hierarchy">Roles</a></li>
                        <li><a href="#">Pricing</a></li>
                        <li><a href="<?= BASE_URL ?>login.php">Login</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-4 mb-4 mb-md-0">
                    <h5 class="footer-title">Integration</h5>
                    <ul class="footer-links">
                        <li><a href="#">Meta Graph API</a></li>
                        <li><a href="#">OAuth Setup</a></li>
                        <li><a href="#">Webhooks</a></li>
                        <li><a href="#">Developer Docs</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-4">
                    <h5 class="footer-title">Legal</h5>
                    <ul class="footer-links">
                        <li><a href="<?= BASE_URL ?>privacy.php">Privacy Policy</a></li>
                        <li><a href="<?= BASE_URL ?>terms.php">Terms of Service</a></li>
                        <li><a href="<?= BASE_URL ?>data-deletion.php">Data Deletion</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p class="mb-0">&copy; <?= date('Y') ?> Lead CRM SaaS. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true,
            offset: 100
        });

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            let nb = document.getElementById('navbar');
            if (nb && !nb.classList.contains('sticky-top')) {
                if (window.scrollY > 50) {
                    nb.classList.add('scrolled');
                } else {
                    nb.classList.remove('scrolled');
                }
            }
        });
    </script>
</body>
</html>
