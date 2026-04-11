            </div><!-- /.container-fluid -->
        </div><!-- /#page-content-wrapper -->
    </div><!-- /#wrapper -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Sidebar Toggle
        const menuToggle = document.getElementById('menu-toggle');
        const wrapper = document.getElementById('wrapper');
        const sidebarOverlay = document.getElementById('sidebar-overlay');

        if (menuToggle) {
            menuToggle.addEventListener('click', function () {
                wrapper.classList.toggle('toggled');
            });
        }

        // Close sidebar when clicking the overlay or close button on mobile
        const sidebarCloseBtn = document.getElementById('sidebar-close-btn');
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', function () {
                wrapper.classList.remove('toggled');
            });
        }
        if (sidebarCloseBtn) {
            sidebarCloseBtn.addEventListener('click', function () {
                wrapper.classList.remove('toggled');
            });
        }

        <?php if (in_array(getUserRole(), ['agent', 'team_lead', 'org_owner']) && strpos($_SERVER['REQUEST_URI'], 'modules/dashboard') !== false): ?>
        // Instant Lead Popup Poller (only on dashboard)
        function checkNewLeads() {
            console.log("Checking for new leads at: <?= BASE_URL ?>ajax/check_new_leads.php");
            fetch('<?= BASE_URL ?>ajax/check_new_leads.php')
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.has_new) {
                        const lead = data.lead;

                        // Trigger table/dashboard refresh if function exists
                        if (typeof refreshLeadData === 'function') {
                            refreshLeadData();
                        }
                        
                        Swal.fire({
                            title: '🔔 New Lead Assigned!',
                            html: `
                                <div class="text-start mt-3" style="font-size: 14px;">
                                    <p class="mb-1 align-items-center d-flex"><i class="bi bi-person text-muted me-2 border rounded-circle p-1"></i> <strong class="me-2">Name:</strong> ${lead.name}</p>
                                    <p class="mb-1 align-items-center d-flex"><i class="bi bi-telephone text-muted me-2 border rounded-circle p-1"></i> <strong class="me-2">Phone:</strong> ${lead.phone}</p>
                                    <p class="mb-1 mt-2">
                                        <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1"><i class="bi bi-box-arrow-in-right me-1"></i>${lead.source}</span>
                                    </p>
                                    <p class="mb-0 text-muted small mt-2 border-top pt-2"><i class="bi bi-clock me-1"></i> Received at ${lead.time}</p>
                                </div>
                            `,
                            showCancelButton: true,
                            confirmButtonText: '<i class="bi bi-eye me-1"></i> Open Lead',
                            cancelButtonText: '<i class="bi bi-telephone me-1"></i> Call Now',
                            confirmButtonColor: '#4f46e5',
                            cancelButtonColor: '#10b981',
                            toast: true,
                            position: 'bottom-end',
                            showConfirmButton: true,
                            timer: 30000,
                            timerProgressBar: true,
                            color: '#0f172a',
                            background: '#ffffff',
                            customClass: {
                                popup: 'shadow-lg border rounded-3',
                                title: 'fs-6 fw-bold text-dark',
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = '<?= BASE_URL ?>modules/leads/view.php?id=' + lead.id;
                            } else if (result.dismiss === Swal.DismissReason.cancel) {
                                window.location.href = 'tel:' + lead.phone;
                            }
                        });
                    }
                })
                .catch(err => console.error("Error checking leads:", err));
        }

        // Check every 5 seconds
        setInterval(checkNewLeads, 5000);
        <?php endif; ?>
    </script>
</body>
</html>
