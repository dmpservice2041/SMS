$(document).ready(function() {
    // Sidebar submenu toggle
    $('.main-side-board .submenu-toggle').on('click', function(e) {
        e.preventDefault(); // Prevent the default anchor action

        // Find the next submenu and toggle its display
        var $submenu = $(this).next('.submenu');
        $submenu.slideToggle(); // jQuery's slideToggle for smooth animation

        // Optional: Toggle an 'active' or 'open' class on the parent li
        $(this).parent('li.has-submenu').toggleClass('open');

        // Optional: Rotate the arrow icon
        $(this).find('.submenu-arrow').toggleClass('bx-chevron-down bx-chevron-up');

        // Optional: Close other open submenus (if you want only one open at a time)
        $('.main-side-board .submenu').not($submenu).slideUp();
        $('.main-side-board li.has-submenu').not($(this).parent('li.has-submenu')).removeClass('open');
        $('.main-side-board .submenu-toggle').not($(this)).find('.submenu-arrow').removeClass('bx-chevron-up').addClass('bx-chevron-down');
    });

    // Highlight active link based on current page
    // (You might already have similar logic, adapt if necessary)
    var currentPath = window.location.pathname.split("/").pop();
    if (currentPath === '') {
        currentPath = 'dashboard.php'; // Default to dashboard if path is empty
    }
    $('.main-side-board li a').each(function() {
        var linkPath = $(this).attr('href');
        if (linkPath === currentPath) {
            $(this).addClass('active'); // Add active class to the link
            // If it's in a submenu, also open the parent and mark it active
            if ($(this).closest('.submenu').length) {
                $(this).closest('.submenu').show().parent('li.has-submenu').addClass('open active');
                 $(this).closest('.submenu').parent('li.has-submenu').find('.submenu-toggle').addClass('active');
                 $(this).closest('.submenu').parent('li.has-submenu').find('.submenu-arrow').removeClass('bx-chevron-down').addClass('bx-chevron-up');
            }
        } else {
            $(this).removeClass('active');
        }
    });
     // Remove active class from dashboard if another link is active and not in a submenu of dashboard
    if (currentPath !== 'dashboard.php' && $('.main-side-board li a.active').length > 0 && !$('.main-side-board li a[href="dashboard.php"]').parent().hasClass('has-submenu') ) {
       // $('.main-side-board li a[href="dashboard.php"]').removeClass('active');
    }


});