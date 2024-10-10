// NOTIFICATIONS
document.addEventListener('DOMContentLoaded', function() {
    const notifIcon = document.querySelector('.notification-icon');
    const notifDropdown = document.getElementById('notificationDropdown');

    notifIcon.addEventListener('click', function(event) {
        event.stopPropagation(); // Prevent the click event from bubbling up
        notifDropdown.classList.toggle('show');
    });

    // Close the dropdown if the user clicks outside of it
    window.addEventListener('click', function(event) {
        if (!notifIcon.contains(event.target) && !notifDropdown.contains(event.target)) {
            notifDropdown.classList.remove('show');
            }
        });
});