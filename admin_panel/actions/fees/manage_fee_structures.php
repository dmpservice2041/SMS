<?php
// Path to admin_panel/partials/config.php
include_once __DIR__ . '/../../partials/config.php'; 

// --- Optional: Force error display for development ---
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
// --- End Optional Error Display ---

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Invalid Request (initial default)'];

if (!isset($conn) || !$conn) {
    $db_error_message = mysqli_connect_error();
    error_log("DB connection failed in manage_fee_structures.php. Error: " . ($db_error_message ?: '$conn not set or is false.'));
    $response['message'] = 'Database connection error.';
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    // Default response if action doesn't match or if a matched action doesn't explicitly set $response
    $response = ['status' => 'error', 'message' => "Action [{$action}] was received but not successfully processed by default."]; 

    if ($action === 'add_structure') {
        if (!empty($_POST['fee_head_name']) && isset($_POST['amount']) && $_POST['amount'] !== '' && !empty($_POST['academic_year']) && isset($_POST['applicability_type'])) {
            
            $fee_head_name = trim($_POST['fee_head_name']);
            $amount = trim($_POST['amount']);
            $academic_year = trim($_POST['academic_year']);
            $applicability_type = $_POST['applicability_type'];
            // For 'specific_students', student_ids will be an array
            $student_ids = isset($_POST['student_ids']) && is_array($_POST['student_ids']) ? $_POST['student_ids'] : [];
            // The class ID used to filter students for selection
            $filter_class_id = isset($_POST['filter_class_id_add']) ? intval($_POST['filter_class_id_add']) : null;


            if (!is_numeric($amount) || floatval($amount) < 0) {
                $response = ['status' => 'error', 'message' => 'Invalid amount. Amount must be a non-negative number.'];
            } elseif ($applicability_type === 'specific_students' && (empty($student_ids) || empty($filter_class_id))) {
                $response = ['status' => 'error', 'message' => 'Please select a class and at least one student for specific student applicability.'];
            } else {
                $conn->begin_transaction();
                try {
                    $stmt_check = $conn->prepare("SELECT id FROM fee_structures WHERE fee_head_name = ? AND academic_year = ?");
                    if (!$stmt_check) throw new Exception("Prepare failed (check duplicate): " . $conn->error);
                    
                    $stmt_check->bind_param("ss", $fee_head_name, $academic_year);
                    $stmt_check->execute();
                    $result_check = $stmt_check->get_result();

                    if ($result_check->num_rows > 0) {
                        throw new Exception('This fee structure (head name and academic year combination) already exists.');
                    }
                    $stmt_check->close();

                    $stmt_insert_structure = $conn->prepare("INSERT INTO fee_structures (fee_head_name, amount, academic_year) VALUES (?, ?, ?)");
                    if (!$stmt_insert_structure) throw new Exception("Prepare failed (insert structure): " . $conn->error);
                    
                    $stmt_insert_structure->bind_param("sds", $fee_head_name, $amount, $academic_year);
                    if (!$stmt_insert_structure->execute()) {
                        throw new Exception('Error adding fee structure: ' . $stmt_insert_structure->error);
                    }
                    $new_structure_id = $stmt_insert_structure->insert_id;
                    $stmt_insert_structure->close();

                    // Handle applicability
                    if ($applicability_type === 'specific_students') {
                        $stmt_app = $conn->prepare("INSERT INTO fee_structure_applicability (fee_structure_id, class_id, student_id) VALUES (?, ?, ?)");
                        if(!$stmt_app) throw new Exception("Prepare applicability failed: " . $conn->error);
                        
                        foreach ($student_ids as $student_id_str) {
                            $student_id_int = intval($student_id_str);
                            if ($student_id_int > 0) {
                                // Use $filter_class_id as the class_id context for this student application
                                $stmt_app->bind_param("iii", $new_structure_id, $filter_class_id, $student_id_int);
                                if(!$stmt_app->execute()) throw new Exception("Execute applicability for student ID $student_id_int failed: " . $stmt_app->error);
                            }
                        }
                        $stmt_app->close();
                    } elseif ($applicability_type === 'general') {
                        $stmt_gen_app = $conn->prepare("INSERT INTO fee_structure_applicability (fee_structure_id, is_all_classes) VALUES (?, 1)");
                        if(!$stmt_gen_app) throw new Exception("Prepare general applicability failed: " . $conn->error);
                        $stmt_gen_app->bind_param("i", $new_structure_id);
                        if(!$stmt_gen_app->execute()) throw new Exception("Execute general applicability failed: " . $stmt_gen_app->error);
                        $stmt_gen_app->close();
                    }
                    
                    $conn->commit();
                    $response = ['status' => 'success', 'message' => 'Fee structure and applicability rules saved.'];
                
                } catch (Exception $e) {
                    $conn->rollback();
                    $response = ['status' => 'error', 'message' => $e->getMessage()];
                    error_log('Add structure transaction failed: ' . $e->getMessage());
                }
            }
        } else {
            $response = ['status' => 'error', 'message' => 'Required fields missing or invalid for adding structure.'];
        }

    } elseif ($action === 'get_structures') {
        $structures_data = [];
        $sql = "SELECT id, fee_head_name, amount, academic_year FROM fee_structures ORDER BY academic_year DESC, fee_head_name ASC";
        $result_structures = $conn->query($sql);

        if ($result_structures) {
            while ($structure_row = $result_structures->fetch_assoc()) {
                $applicability_display = "General"; // Default
                
                // Check for specific student applicability first
                $stmt_students_app = $conn->prepare("SELECT COUNT(DISTINCT student_id) as student_count 
                                                     FROM fee_structure_applicability 
                                                     WHERE fee_structure_id = ? AND student_id IS NOT NULL AND is_all_classes = 0");
                if ($stmt_students_app) {
                    $stmt_students_app->bind_param("i", $structure_row['id']);
                    $stmt_students_app->execute();
                    $app_result_students = $stmt_students_app->get_result()->fetch_assoc();
                    $stmt_students_app->close();
                    if ($app_result_students && $app_result_students['student_count'] > 0) {
                        $applicability_display = $app_result_students['student_count'] . " Specific Student(s)";
                    } else {
                        // If no specific students, check if it's explicitly marked as 'general' (is_all_classes = 1)
                        $stmt_gen = $conn->prepare("SELECT fee_structure_id FROM fee_structure_applicability 
                                                    WHERE fee_structure_id = ? AND is_all_classes = 1 LIMIT 1");
                        if ($stmt_gen) {
                            $stmt_gen->bind_param("i", $structure_row['id']);
                            $stmt_gen->execute();
                            if ($stmt_gen->get_result()->num_rows > 0) {
                                $applicability_display = "General (All Students)";
                            }
                            $stmt_gen->close();
                        }
                    }
                } else {
                    error_log("Prepare failed for student applicability check: " . $conn->error);
                }
                $structure_row['applicability_display'] = $applicability_display;
                $structures_data[] = $structure_row;
            }
            $response = ['status' => 'success', 'structures' => $structures_data, 'message' => 'Fee structures fetched successfully.'];
        } else {
            $response = ['status' => 'error', 'message' => 'Error fetching fee structures: ' . $conn->error];
            error_log('Query failed (get_structures): ' . $conn->error);
        }

    } elseif ($action === 'delete_structure' && isset($_POST['structure_id'])) {
        $structure_id = intval($_POST['structure_id']);
        if ($structure_id > 0) {
            $conn->begin_transaction();
            try {
                // ON DELETE CASCADE from fee_structures will handle student_fee_allocations
                // Manually delete from fee_structure_applicability first if not relying on CASCADE or for explicit control
                $stmt_del_app = $conn->prepare("DELETE FROM fee_structure_applicability WHERE fee_structure_id = ?");
                if (!$stmt_del_app) throw new Exception("Prepare failed (delete_app): " . $conn->error);
                $stmt_del_app->bind_param("i", $structure_id);
                $stmt_del_app->execute(); // No need to check affected_rows, it's okay if none existed
                $stmt_del_app->close();

                $stmt = $conn->prepare("DELETE FROM fee_structures WHERE id = ?");
                if (!$stmt) throw new Exception("Prepare failed (delete_structure): " . $conn->error);
                
                $stmt->bind_param("i", $structure_id);
                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        $conn->commit();
                        $response = ['status' => 'success', 'message' => 'Fee structure and its applicability deleted successfully.'];
                    } else {
                        $conn->rollback(); // Rollback if structure itself wasn't found
                        $response = ['status' => 'error', 'message' => 'Fee structure not found or already deleted.'];
                    }
                } else {
                    throw new Exception('Error deleting fee structure: ' . $stmt->error);
                }
                $stmt->close();
            } catch (Exception $e) {
                $conn->rollback();
                $response = ['status' => 'error', 'message' => $e->getMessage()];
                error_log('Delete structure transaction failed: ' . $e->getMessage());
            }
        } else { 
            $response = ['status' => 'error', 'message' => 'Invalid Fee Structure ID for deletion.']; 
        }

    } elseif ($action === 'get_structure_details' && isset($_POST['structure_id'])) {
        $structure_id = intval($_POST['structure_id']);
        $structure_data = null;
        if ($structure_id > 0) {
            $stmt = $conn->prepare("SELECT id, fee_head_name, amount, academic_year FROM fee_structures WHERE id = ?");
            if (!$stmt) { $response['message'] = "Prepare failed (get_details): (" . $conn->errno . ") " . $conn->error; }
            else {
                $stmt->bind_param("i", $structure_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $structure_data = $result->fetch_assoc();
                $stmt->close();
            }

            if ($structure_data) {
                $applicability = ['type' => 'general', 'student_ids' => [], 'filter_class_id' => null];
                
                $stmt_app = $conn->prepare("SELECT student_id, class_id, is_all_classes FROM fee_structure_applicability WHERE fee_structure_id = ?");
                if($stmt_app){
                    $stmt_app->bind_param("i", $structure_id);
                    $stmt_app->execute();
                    $app_result = $stmt_app->get_result();
                    $student_ids_found = [];
                    $class_id_context = null;
                    $is_general_db = false;

                    while($app_row = $app_result->fetch_assoc()){
                        if($app_row['is_all_classes'] == 1){
                            $is_general_db = true;
                            break; 
                        }
                        if(!is_null($app_row['student_id'])){
                            $student_ids_found[] = $app_row['student_id'];
                            // Get the class_id from the first student found as a context for the dropdown
                            if(is_null($class_id_context) && !is_null($app_row['class_id'])){
                                 $class_id_context = $app_row['class_id'];
                            }
                        }
                    }
                    $stmt_app->close();

                    if($is_general_db){
                        $applicability['type'] = 'general';
                    } elseif(!empty($student_ids_found)){
                        $applicability['type'] = 'specific_students';
                        $applicability['student_ids'] = array_unique($student_ids_found); // Ensure unique student IDs
                        $applicability['filter_class_id'] = $class_id_context;
                    }
                    // If neither general nor specific students, it defaults to general type from initialization
                } else { error_log("Prepare failed for applicability check in get_details: " . $conn->error); }
                
                $structure_data['applicability'] = $applicability;
                // For display in main table (though JS handles this based on structure.applicability_display)
                // We can build a display string here too if needed by JS for the edit modal's "Currently Applied To"
                $current_app_display = "General";
                 if ($applicability['type'] === 'specific_students' && !empty($applicability['student_ids'])) {
                    $current_app_display = count($applicability['student_ids']) . " Specific Student(s)";
                 } elseif ($applicability['type'] === 'general') {
                    $current_app_display = "General (All Students)";
                 }
                $structure_data['applicability_display'] = $current_app_display;


                $response = ['status' => 'success', 'structure' => $structure_data];
            } else { $response['message'] = 'Fee structure not found.'; }
        } else { $response['message'] = 'Invalid Fee Structure ID for get_details.'; }

    } elseif ($action === 'update_structure' && isset($_POST['structure_id'])) {
        if (!empty($_POST['fee_head_name']) && isset($_POST['amount']) && $_POST['amount'] !== '' && !empty($_POST['academic_year']) && isset($_POST['applicability_type'])) {
            
            $structure_id = intval($_POST['structure_id']);
            $fee_head_name = trim($_POST['fee_head_name']);
            $amount = trim($_POST['amount']);
            $academic_year = trim($_POST['academic_year']);
            $applicability_type = $_POST['applicability_type'];
            $student_ids = isset($_POST['student_ids']) && is_array($_POST['student_ids']) ? $_POST['student_ids'] : [];
            $filter_class_id = isset($_POST['filter_class_id_edit']) ? intval($_POST['filter_class_id_edit']) : null;


            if ($structure_id <= 0) { $response = ['status' => 'error', 'message' => 'Invalid Structure ID for update.']; }
            elseif (!is_numeric($amount) || floatval($amount) < 0) { $response = ['status' => 'error', 'message' => 'Invalid amount for update.']; }
            elseif ($applicability_type === 'specific_students' && (empty($student_ids) || empty($filter_class_id))) { $response = ['status' => 'error', 'message' => 'Please select a class and at least one student for specific student applicability during update.'];}
            else {
                $conn->begin_transaction();
                try {
                    $stmt_check = $conn->prepare("SELECT id FROM fee_structures WHERE fee_head_name = ? AND academic_year = ? AND id != ?");
                    if (!$stmt_check) throw new Exception("Prepare failed (update check): " . $conn->error);
                    $stmt_check->bind_param("ssi", $fee_head_name, $academic_year, $structure_id);
                    $stmt_check->execute();
                    if ($stmt_check->get_result()->num_rows > 0) {
                        throw new Exception('Another fee structure with this head name and academic year already exists.');
                    }
                    $stmt_check->close();

                    $stmt_update_structure = $conn->prepare("UPDATE fee_structures SET fee_head_name = ?, amount = ?, academic_year = ? WHERE id = ?");
                    if (!$stmt_update_structure) throw new Exception("Prepare failed (update structure): " . $conn->error);
                    $stmt_update_structure->bind_param("sdsi", $fee_head_name, $amount, $academic_year, $structure_id);
                    if (!$stmt_update_structure->execute()) {
                        throw new Exception('Error updating fee structure details: ' . $stmt_update_structure->error);
                    }
                    $stmt_update_structure->close();

                    $stmt_del_app = $conn->prepare("DELETE FROM fee_structure_applicability WHERE fee_structure_id = ?");
                    if(!$stmt_del_app) throw new Exception("Prepare delete old applicability failed: " . $conn->error);
                    $stmt_del_app->bind_param("i", $structure_id);
                    if(!$stmt_del_app->execute()) throw new Exception("Execute delete old applicability failed: " . $stmt_del_app->error);
                    $stmt_del_app->close();

                    if ($applicability_type === 'specific_students') {
                        $stmt_app = $conn->prepare("INSERT INTO fee_structure_applicability (fee_structure_id, class_id, student_id) VALUES (?, ?, ?)");
                        if(!$stmt_app) throw new Exception("Prepare new applicability failed: " . $conn->error);
                        foreach ($student_ids as $student_id_str) {
                            $student_id_int = intval($student_id_str);
                            if($student_id_int > 0){
                                $stmt_app->bind_param("iii", $structure_id, $filter_class_id, $student_id_int);
                                if(!$stmt_app->execute()) throw new Exception("Execute new applicability for student ID $student_id_int failed: " . $stmt_app->error);
                            }
                        }
                        $stmt_app->close();
                    } elseif ($applicability_type === 'general') {
                        $stmt_gen_app = $conn->prepare("INSERT INTO fee_structure_applicability (fee_structure_id, is_all_classes) VALUES (?, 1)");
                        if(!$stmt_gen_app) throw new Exception("Prepare new general applicability failed: " . $conn->error);
                        $stmt_gen_app->bind_param("i", $structure_id);
                        if(!$stmt_gen_app->execute()) throw new Exception("Execute new general applicability failed: " . $stmt_gen_app->error);
                        $stmt_gen_app->close();
                    }
                    
                    $conn->commit();
                    $response = ['status' => 'success', 'message' => 'Fee structure and applicability updated.'];

                } catch (Exception $e) {
                    $conn->rollback();
                    $response = ['status' => 'error', 'message' => $e->getMessage()];
                    error_log('Update structure transaction failed: ' . $e->getMessage());
                }
            }
        } else {
            $response = ['status' => 'error', 'message' => 'All required fields are missing for update.'];
        }
    } else {
        $response = ['status' => 'error', 'message' => "Unknown action: [{$action}] specified."];
    }
} else {
    $response = ['status' => 'error', 'message' => 'Invalid request method or core action parameter missing. Method: ' . $_SERVER['REQUEST_METHOD'] . " POST Data: " . print_r($_POST, true)];
}

echo json_encode($response);

if (isset($conn) && $conn && !is_bool($conn) && method_exists($conn, 'close')) { 
    $conn->close();
}
?>