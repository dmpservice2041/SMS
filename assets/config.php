<?php
    $server = "localhost";
    $user = "root";
    $password = ""; // ENSURE THIS IS CORRECT
    $db = "_sms";    // ENSURE THIS IS CORRECT
    
    $conn = mysqli_connect($server, $user, $password, $db);

    if (!$conn) {
        // For AJAX debugging, let's output the error directly for now
        // This will make the JSON parse fail, but we'll see the DB error in the Network response
        $error_message = "Database Connection Failed in assets/config.php: " . mysqli_connect_error() . " (errno: " . mysqli_connect_errno() . ")";
        error_log($error_message); // Log it for sure
        
        // Temporarily echo for direct AJAX response viewing, then remove/comment out
        // header('Content-Type: text/plain'); // To prevent JSON parse error
        // die($error_message); 
    } else {
        mysqli_set_charset($conn, "utf8mb4");
    }
?>