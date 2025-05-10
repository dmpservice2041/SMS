<?php
include_once __DIR__ . '/../../partials/config.php'; 

// ... (session/auth checks if you have them) ...

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Invalid Request (initial)'];

if (!isset($conn) || !$conn) {
    $db_error = mysqli_connect_error();
    error_log("Database connection failed in manage_payment_modes.php. Error: " . ($db_error ? $db_error : '$conn not set or is false.'));
    $response['message'] = 'Database connection error. Details: ' . ($db_error ? $db_error : 'Connection object not initialized.');
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $response = ['status' => 'error', 'message' => "Invalid action: $action"]; // Default for this block

    // --- PAYMENT MODE ACTIONS --- (Keep your existing code for these)
    if ($action === 'add_mode') {
        // ... your existing add_mode logic ...
         if (!empty($_POST['mode_name'])) {
            $mode_name = trim($_POST['mode_name']);
            $stmt_check = $conn->prepare("SELECT id FROM payment_modes WHERE mode_name = ?");
            if (!$stmt_check) { $response['message'] = "Prepare failed (stmt_check): (" . $conn->errno . ") " . $conn->error; error_log($response['message']); }
            else { /* ... rest of add_mode ... */ 
                $stmt_check->bind_param("s", $mode_name);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();
                if ($result_check->num_rows > 0) {
                    $response['message'] = 'Payment mode already exists.';
                } else {
                    $stmt = $conn->prepare("INSERT INTO payment_modes (mode_name) VALUES (?)");
                    if (!$stmt) { $response['message'] = "Prepare failed (stmt_insert): (" . $conn->errno . ") " . $conn->error; error_log($response['message']); }
                    else {
                        $stmt->bind_param("s", $mode_name);
                        if ($stmt->execute()) {
                            $response = ['status' => 'success', 'message' => 'Payment mode added successfully.'];
                        } else {
                            $response['message'] = 'Error adding payment mode: ' . $stmt->error;
                            error_log('Execute failed (stmt_insert): ' . $stmt->error);
                        }
                        $stmt->close();
                    }
                }
                $stmt_check->close();
            }
        } else { $response['message'] = 'Mode name cannot be empty.'; }
    } elseif ($action === 'get_modes') {
        // ... your existing get_modes logic ...
        $modes = [];
        $sql = "SELECT id, mode_name, is_active FROM payment_modes ORDER BY mode_name ASC";
        $result = $conn->query($sql);
        if ($result) { 
            if ($result->num_rows > 0) { while ($row = $result->fetch_assoc()) { $modes[] = $row; } }
            $response = ['status' => 'success', 'modes' => $modes];
        } else { $response['message'] = 'Error fetching payment modes: ' . $conn->error; error_log('Query failed (get_modes): ' . $conn->error); }
    } elseif ($action === 'get_mode_details' && isset($_POST['mode_id'])) {
        // ... your existing get_mode_details logic ...
        $mode_id = intval($_POST['mode_id']);
        $stmt = $conn->prepare("SELECT id, mode_name, is_active FROM payment_modes WHERE id = ?");
        if(!$stmt){ $response['message'] = "Prepare failed (get_mode_details): " . $conn->error; error_log($response['message']); }
        else { /* ... rest of get_mode_details ... */
            $stmt->bind_param("i", $mode_id); $stmt->execute(); $result = $stmt->get_result();
            if ($modeData = $result->fetch_assoc()) { $response = ['status' => 'success', 'mode' => $modeData]; }
            else { $response['message'] = 'Payment mode not found.'; }
            $stmt->close();
        }
    } elseif ($action === 'update_mode' && isset($_POST['mode_id']) && !empty($_POST['mode_name'])) {
        // ... your existing update_mode logic ...
        $mode_id = intval($_POST['mode_id']); $mode_name = trim($_POST['mode_name']); $is_active = isset($_POST['is_active']) ? 1 : 0;
        $stmt_check = $conn->prepare("SELECT id FROM payment_modes WHERE mode_name = ? AND id != ?");
        if(!$stmt_check){ $response['message'] = "Prepare failed (update_mode check): " . $conn->error; error_log($response['message']); }
        else { /* ... rest of update_mode ... */
            $stmt_check->bind_param("si", $mode_name, $mode_id); $stmt_check->execute(); $result_check = $stmt_check->get_result();
            if ($result_check->num_rows > 0) { $response['message'] = 'Another payment mode with this name already exists.'; }
            else {
                $stmt = $conn->prepare("UPDATE payment_modes SET mode_name = ?, is_active = ? WHERE id = ?");
                if(!$stmt){ $response['message'] = "Prepare failed (update_mode update): " . $conn->error; error_log($response['message']); }
                else { /* ... rest of update logic ... */
                    $stmt->bind_param("sii", $mode_name, $is_active, $mode_id);
                    if ($stmt->execute()) { $response = ['status' => 'success', 'message' => 'Payment mode updated successfully.']; }
                    else { $response['message'] = 'Error updating payment mode: ' . $stmt->error; error_log($response['message']); }
                    $stmt->close();
                }
            }
            $stmt_check->close();
        }
    } elseif ($action === 'delete_mode' && isset($_POST['mode_id'])) {
        // ... your existing delete_mode logic ...
        $mode_id = intval($_POST['mode_id']);
        $stmt = $conn->prepare("UPDATE payment_modes SET is_active = 0 WHERE id = ?");
        if(!$stmt){ $response['message'] = "Prepare failed (delete_mode): " . $conn->error; error_log($response['message']); }
        else { /* ... rest of delete_mode ... */
            $stmt->bind_param("i", $mode_id);
            if ($stmt->execute()) { $response = ['status' => 'success', 'message' => 'Payment mode deactivated successfully.']; }
            else { $response['message'] = 'Error deactivating payment mode: ' . $stmt->error; error_log($response['message']); }
            $stmt->close();
        }
    }
    // --- END PAYMENT MODE ACTIONS ---

    // --- START RECEIPT SETTINGS ACTIONS ---
    elseif ($action === 'get_receipt_settings') {
        $settings = [];
        $setting_names = ['receipt_prefix', 'next_receipt_number', 'receipt_suffix'];
        $sql_in = "'" . implode("','", $setting_names) . "'";
        $sql = "SELECT setting_name, setting_value FROM fee_settings WHERE setting_name IN ($sql_in)";
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $settings[$row['setting_name']] = $row['setting_value'];
            }
            // Ensure all keys exist in the settings array, even if null
            foreach ($setting_names as $name) {
                if (!isset($settings[$name])) {
                    $settings[$name] = null;
                }
            }
            $response = ['status' => 'success', 'settings' => $settings];
        } else {
            $response['message'] = 'Error fetching receipt settings: ' . $conn->error;
            error_log('Query failed (get_receipt_settings): ' . $conn->error);
        }
    } elseif ($action === 'save_receipt_settings') {
        $settings_to_save = [
            'receipt_prefix' => $_POST['receipt_prefix'] ?? '',
            'next_receipt_number' => $_POST['next_receipt_number'] ?? '',
            'receipt_suffix' => $_POST['receipt_suffix'] ?? ''
        ];
        $all_successful = true;

        foreach ($settings_to_save as $name => $value) {
            // Use INSERT ... ON DUPLICATE KEY UPDATE to handle both new and existing settings
            $stmt = $conn->prepare("INSERT INTO fee_settings (setting_name, setting_value) VALUES (?, ?) 
                                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            if(!$stmt){
                $response['message'] = "Prepare failed for setting '$name': " . $conn->error;
                error_log($response['message']);
                $all_successful = false;
                break;
            }
            $stmt->bind_param("ss", $name, $value);
            if (!$stmt->execute()) {
                $response['message'] = "Error saving setting '$name': " . $stmt->error;
                error_log($response['message']);
                $all_successful = false;
                $stmt->close();
                break;
            }
            $stmt->close();
        }

        if ($all_successful) {
            $response = ['status' => 'success', 'message' => 'Receipt settings saved successfully.'];
        }
        // If not successful, $response['message'] will have the specific error.

    }
    // --- END RECEIPT SETTINGS ACTIONS ---
    else {
        $response['message'] = "Unknown action: {$action}"; // Catchall for unhandled actions
    }

} else { // If not POST or action not set
     $response['message'] = 'Invalid request method or action not specified.';
}


echo json_encode($response);
if (isset($conn) && $conn && !is_bool($conn) && method_exists($conn, 'close')) { 
    $conn->close();
}
?>