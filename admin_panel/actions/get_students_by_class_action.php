<?php
include_once __DIR__ . '/../../partials/config.php'; // Path to admin_panel/partials/config.php

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Class ID not provided.'];
$students = [];

if (isset($_POST['class_id']) && !empty($_POST['class_id'])) {
    $class_id = intval($_POST['class_id']);

    if (isset($conn) && $conn) {
        // Adjust column names (s_no, fname, lname) to match your 'students' table
        // Also, the column in 'students' table that stores class ID (e.g., 'class_id' or 'current_class_s_no')
        // Assuming your students table has a column like `class_s_no` that stores the s_no from the classes table.
        $stmt = $conn->prepare("SELECT s_no, fname, lname FROM students WHERE class_s_no = ? ORDER BY fname, lname");
        if (!$stmt) {
            $response['message'] = "Prepare failed: " . $conn->error;
        } else {
            $stmt->bind_param("i", $class_id);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $students[] = $row;
                }
                $response = ['status' => 'success', 'students' => $students];
            } else {
                $response['message'] = "Execute failed: " . $stmt->error;
            }
            $stmt->close();
        }
        if (is_object($conn) && method_exists($conn, 'close')) { $conn->close(); }
    } else {
        $response['message'] = 'Database connection error for fetching students.';
    }
}
echo json_encode($response);
?>