<?php
include_once __DIR__ . '/partials/_header.php';
include_once __DIR__ . '/partials/_sidebar.php';
// Ensure $conn is available, typically from config.php included in _header.php
?>

<!-- Main Content -->
<div class="content">
    <?php include_once __DIR__ . "/partials/_navbar.php"; ?>

    <main>
        <div class="header">
            <div class="left">
                <h1>Fee Structures</h1>
            </div>
        </div>

        <div class="bottom-data">
            <div class="orders">
                <div class="header" style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; align-items: center;">
                        <i class='bx bxs-spreadsheet me-2'></i>
                        <h3 style="margin-bottom: 0;">Manage Fee Structures</h3>
                    </div>
                    <div>
                        <button type="button" class="btn btn-primary btn-sm d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#addFeeStructureModal">
                            <i class='bx bx-plus me-1'></i> Add New Structure
                        </button>
                    </div>
                </div>
                <table class="table table-striped table-hover mt-3">
                    <thead>
                        <tr>
                            <th style="width: 60px; text-align: center;">Sr. No.</th>
                            <th>Fee Head Name</th>
                            <th>Amount</th>
                            <th>Academic Year</th>
                            <th>Applicability</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="feeStructuresTableBody">
                        <!-- Fee structures will be loaded here by JavaScript -->
                        <tr><td colspan="6" class="text-center">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Add Fee Structure Modal -->
<div class="modal fade" id="addFeeStructureModal" tabindex="-1" aria-labelledby="addFeeStructureModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addFeeStructureModalLabel">Add New Fee Structure</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addFeeStructureForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="feeHeadName" class="form-label">Fee Head Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="feeHeadName" name="fee_head_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="feeAmount" class="form-label">Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="feeAmount" name="amount" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="academicYear" class="form-label">Academic Year <span class="text-danger">*</span></label>
                            <select class="form-select" id="academicYear" name="academic_year" required>
                                <option value="">Select Year</option>
                                <?php
                                $current_year_for_dropdown = date('Y');
                                for ($i = $current_year_for_dropdown - 2; $i <= $current_year_for_dropdown + 2; $i++) {
                                    $year_range = $i . '-' . substr($i + 1, -2);
                                    echo "<option value=\"$year_range\">$year_range</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Inside <form id="editFeeStructureForm"> in #editFeeStructureModal -->
<hr>
<h5>Applicability <span class="text-danger">*</span></h5>
<div class="mb-3">
    <label class="form-label">Currently Applied To:</label>
    <div id="currentApplicabilityDisplayEdit" class="mb-2 p-2 border rounded bg-light" style="min-height: 30px;">
        Loading applicability...
    </div>
    <label class="form-label">Change/Set Applicability:</label>
    <div>
        <div class="form-check form-check-inline">
            <input class="form-check-input edit-applicability-type" type="radio" name="edit_applicability_type" id="editApplyGeneral" value="general">
            <label class="form-check-label" for="editApplyGeneral">General (All Students)</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input edit-applicability-type" type="radio" name="edit_applicability_type" id="editApplySpecificStudents" value="specific_students">
            <label class="form-check-label" for="editApplySpecificStudents">Specific Student(s)</label>
        </div>
    </div>
</div>

<div id="specificStudentContainerEdit" class="mb-3" style="display:none;">
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="selectClassForStudentEdit" class="form-label">Select Class to Filter Students <span class="text-danger">*</span></label>
            <select class="form-select" id="selectClassForStudentEdit" name="filter_class_id_edit">
                <option value="">-- Select Class --</option>
                <!-- Options will be populated by JavaScript -->
            </select>
        </div>
    </div>
    <div class="mb-3">
        <label for="selectStudentsEdit" class="form-label">Select Student(s) from Class <span class="text-danger">*</span></label>
        <select class="form-select" id="selectStudentsEdit" name="edit_student_ids[]" multiple disabled>
            <option value="">-- Select a class first --</option>
            <!-- Options will be populated by JavaScript -->
        </select>
        <small class="form-text text-muted">Hold Ctrl/Cmd to select multiple students.</small>
    </div>
</div>
<div class="form-text text-muted mb-2">Note: Changing applicability might regenerate pending fee records for students.</div>

                    <div id="addFeeStructureError" class="text-danger mt-3" style="display:none;"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveFeeStructureBtn">Save Structure</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Fee Structure Modal -->
<div class="modal fade" id="editFeeStructureModal" tabindex="-1" aria-labelledby="editFeeStructureModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editFeeStructureModalLabel">Edit Fee Structure</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editFeeStructureForm">
                    <input type="hidden" id="editStructureId" name="structure_id">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editFeeHeadName" class="form-label">Fee Head Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editFeeHeadName" name="fee_head_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editFeeAmount" class="form-label">Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="editFeeAmount" name="amount" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editAcademicYear" class="form-label">Academic Year <span class="text-danger">*</span></label>
                            <select class="form-select" id="editAcademicYear" name="academic_year" required>
                                <option value="">Select Year</option>
                                <?php
                                // Renamed variable to avoid conflict if this file is included multiple times or in a loop
                                $current_year_for_edit_dropdown = date('Y');
                                for ($i = $current_year_for_edit_dropdown - 2; $i <= $current_year_for_edit_dropdown + 2; $i++) {
                                    $year_range_edit = $i . '-' . substr($i + 1, -2);
                                    echo "<option value=\"$year_range_edit\">$year_range_edit</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <hr>
                    <h5>Applicability <span class="text-danger">*</span></h5>
                    <div class="mb-3">
                        <label class="form-label">Currently Applied To:</label>
                        <div id="currentApplicabilityDisplay" class="mb-2 p-2 border rounded bg-light" style="min-height: 30px;"> 
                            <!-- Min-height for better visual before loading -->
                            Loading applicability...
                        </div>
                        <label class="form-label">Change/Set Applicability:</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input edit-applicability-type" type="radio" name="edit_applicability_type" id="editApplyGeneral" value="general">
                                <label class="form-check-label" for="editApplyGeneral">General (All Students)</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input edit-applicability-type" type="radio" name="edit_applicability_type" id="editApplySpecificClass" value="specific_class">
                                <label class="form-check-label" for="editApplySpecificClass">Specific Class(es)</label>
                            </div>
                        </div>
                    </div>

                    <div id="specificClassContainerEdit" class="mb-3" style="display:none;">
                        <label for="selectClassesEdit" class="form-label">Select Class(es) <span class="text-danger">*</span></label>
                        <select class="form-select" id="selectClassesEdit" name="edit_class_ids[]" multiple>
                            <!-- Options will be populated by JavaScript -->
                             <option value="">Loading Classes...</option>
                        </select>
                        <small class="form-text text-muted">Hold Ctrl/Cmd to select multiple classes. This fee will apply to ALL students in the selected class(es).</small>
                    </div>
                    <div class="form-text text-muted mb-2">Note: Changing applicability might regenerate pending fee records for students.</div>

                    <div id="editFeeStructureError" class="text-danger mt-3" style="display:none;"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="updateFeeStructureBtn">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/partials/_footer.php'; ?>
<script src="../assets/js/fee_structures.js"></script>