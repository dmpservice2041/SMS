<?php
// This ensures config.php is loaded, which is usually done by _header.php or noSessionRedirect.php
// If you find you need $conn and it's not available, uncomment the next line.
// include_once __DIR__ . '/../config.php'; // Goes one level up to root, then to config.php

include_once __DIR__ . '/partials/_header.php';
include_once __DIR__ . '/partials/_sidebar.php';
?>

<!-- Main Content -->
<div class="content"> <!-- Or your main content wrapper class -->
    <?php include_once __DIR__ . "/partials/_navbar.php"; ?>

    <main>
        <div class="header"> <!-- Your existing header structure -->
            <div class="left">
                <h1>Fee Structures</h1> <!-- <<<<< CHANGE THIS TITLE FOR EACH FILE -->
            </div>
        </div>

        <div class="bottom-data"> <!-- Your existing content area class -->
            <div class="orders"> <!-- Or panel/card class -->
                <div class="header">
                    <i class='bx bx-money'></i> <!-- Example Icon -->
                    <h3>Manage Fee Structures</h3> <!-- <<<<< CHANGE THIS SUB-TITLE -->
                </div>
                <p>
                    Page content for Fee Structures will go here.
                </p>
                <!-- We will add forms, tables, etc. here in later steps -->
            </div>
        </div>
    </main>
</div>

<?php include_once __DIR__ . '/partials/_footer.php'; ?>

<!-- Specific JS for this page will be added later if needed -->
<!-- <script src="assets/js/fee_structures.js"></script> -->