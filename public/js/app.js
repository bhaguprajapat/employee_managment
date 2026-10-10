// Small, dependency-free helpers. Bootstrap's own JS handles dropdowns and alerts.
(function () {
    'use strict';

    // Mobile sidebar toggle
    var sidebar = document.getElementById('appSidebar');
    var toggle = document.getElementById('sidebarToggle');

    if (sidebar && toggle) {
        toggle.addEventListener('click', function () {
            sidebar.classList.toggle('is-open');
        });

        // Close the drawer when a link is tapped on small screens
        sidebar.querySelectorAll('.nav-link').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.innerWidth < 992) {
                    sidebar.classList.remove('is-open');
                }
            });
        });
    }

    // Prevent double submission of check-in / check-out forms
    document.querySelectorAll('form[data-once]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (form.dataset.submitted === '1') {
                event.preventDefault();
                return;
            }
            form.dataset.submitted = '1';
            var button = form.querySelector('button[type="submit"]');
            if (button) {
                button.disabled = true;
            }
        });
    });

    // Confirm dangerous actions (deactivate employee)
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (event) {
            if (!window.confirm(el.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });
})();
