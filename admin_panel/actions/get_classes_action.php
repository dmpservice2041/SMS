<?php
// --- FORCE ERROR DISPLAY FOR AJAX DEBUG ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- END FORCE ERROR DISPLAY ---

// Path to your main config file that establishes $conn
// If get_classes_action.php is in SMS/admin_panel/actions/
// and config.php is in SMS/admin_panel/partials/
include_once __DIR__ . '/../partials/config.php'; 

header('Content-Type: application/json'); // Set this early
$response = ['status' => 'error', 'message' => 'Initial error in get_classes_action.php'];
$classes_array = [];

// Check DB Connection
if (!isset($conn) || !$conn) { // mysqli_connect returns false on failure
    $db_error_message = mysqli_connect_error(); // Get the actual connection error if available
    error_log("get_classes_action.php: DB Connection FAILED. Error: " . ($db_error_message ?: '$conn not set or config include failed.'));
    
    $response['message'] = 'Server database configuration error.';
    // $response['debug_db_error'] = $db_error_message ?: '$conn was not properly set.'; // Optional: send detailed error back for dev
    echo json_encode($response);
    exit;
}
// error_log("get_classes_action.php: DB Connection SUCCESS.");


// Adjust s_no, class, section to match your 'classes' table columns
// The CAST is to help sort numerically if class names are like "1", "2", "10", "Nursery"
$sql = "SELECT s_no, class, section FROM classes ORDER BY CAST(class AS UNSIGNED), class, section ASC"; 
// error_log("get_classes_action.php: SQL Query: " . $sql);

$result = $conn->query($sql);

if ($result) {
    // error_log("get_classes_action.php: Query successful. Num rows: " . $result->num_rows);
    while ($row = $result->fetch_assoc()) {
        $classes_array[] = [
            's_no' => $row['s_no'], 
            'class_name_with_section' => htmlspecialchars($row['class'] . (!empty($row['section']) ? ' - ' . $row['section'] : ''))
        ];
    }
    $response = ['status' => 'success', 'classes' => $classes_array, 'message' => 'Classes fetched.'];
} else {
    $response['message'] = 'Error querying classes: ' . $conn->error;
    error_log("Error in get_classes_action.php query: " . $conn->error . " | SQL: " . $sql);
}

if (is_object($conn) && method_exists($conn, 'close')) { 
    $conn->close(); 
}

// error_log("get_classes_action.php: Final response: " . json_encode($response));
echo json_encode($response);
?>