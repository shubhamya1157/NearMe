// frontend/js/dashboard.js
// Auto-submits filter form when distance dropdown is changed for quick filtering

document.addEventListener('DOMContentLoaded', function () {
    const distanceSelect = document.getElementById('distance');
    if (distanceSelect && distanceSelect.form) {
        distanceSelect.addEventListener('change', function () {
            distanceSelect.form.submit();
        });
    }
});
