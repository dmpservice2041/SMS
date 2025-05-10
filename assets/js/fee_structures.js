$(document).ready(function() {
    const actionUrl = 'actions/fees/manage_fee_structures.php';
    const getClassesUrl = 'actions/get_classes_action.php';
    const getStudentsByClassUrl = 'actions/get_students_by_class_action.php';

    // --- Helper function to populate class dropdowns ---
    function populateClassDropdown(selectElementId, callback, selectedValue = null) {
        const $select = $('#' + selectElementId);
        const originalValue = selectedValue || $select.val(); // Preserve current single selection if any
        $select.html('<option value="">Loading classes...</option>').prop('disabled', true);
        
        $.ajax({
            url: getClassesUrl, type: 'GET', dataType: 'json',
            success: function(response) {
                $select.empty().append(new Option('-- Select Class --', ''));
                if (response.status === 'success' && response.classes && response.classes.length > 0) {
                    response.classes.forEach(function(classItem) {
                        $select.append(new Option(escapeHtml(classItem.class_name_with_section), classItem.s_no));
                    });
                    if (originalValue) { // Re-select if a value was intended
                        $select.val(originalValue);
                    }
                } else {
                    $select.append(new Option('No classes found', ''));
                }
                $select.prop('disabled', false);
                if (typeof callback === 'function') callback();
            },
            error: function() { 
                $select.empty().append(new Option('Error loading classes', '')).prop('disabled', false);
                if (typeof callback === 'function') callback();
            }
        });
    }

    // --- Helper function to populate student multi-select dropdowns based on class ---
    function populateStudentDropdown(classSelectElementId, studentSelectElementId, selectedStudentIds = []) {
        const classId = $('#' + classSelectElementId).val();
        const $studentSelect = $('#' + studentSelectElementId);

        if (!classId) {
            $studentSelect.html('<option value="">-- Select a class first --</option>').prop('disabled', true);
            return;
        }
        $studentSelect.html('<option value="">Loading students...</option>').prop('disabled', true);

        $.ajax({
            url: getStudentsByClassUrl, type: 'POST', data: { class_id: classId }, dataType: 'json',
            success: function(response) {
                $studentSelect.empty();
                if (response.status === 'success' && response.students && response.students.length > 0) {
                    response.students.forEach(function(student) {
                        let studentName = escapeHtml(student.fname + (student.lname ? ' ' + student.lname : '')) + ' (ID: ' + student.s_no + ')';
                        let option = new Option(studentName, student.s_no);
                        // Check if student.s_no (which is an INT from PHP) is in selectedStudentIds (which might be array of strings from .val())
                        if (selectedStudentIds.map(String).includes(String(student.s_no))) {
                            $(option).prop('selected', true);
                        }
                        $studentSelect.append(option);
                    });
                    $studentSelect.prop('disabled', false);
                } else {
                    $studentSelect.html('<option value="">No students found for this class</option>').prop('disabled', true);
                }
            },
            error: function() {
                $studentSelect.html('<option value="">Error loading students</option>').prop('disabled', true);
            }
        });
    }

    // --- Applicability UI toggle for ADD modal ---
    $('input[name="applicability_type"]').on('change', function() {
        if (this.value === 'specific_students') {
            $('#specificStudentContainerAdd').slideDown();
            populateClassDropdown('selectClassForStudentAdd');
            $('#selectStudentsAdd').html('<option value="">-- Select a class first --</option>').prop('disabled', true);
        } else {
            $('#specificStudentContainerAdd').slideUp();
        }
    });
    $('#selectClassForStudentAdd').on('change', function() {
        populateStudentDropdown('selectClassForStudentAdd', 'selectStudentsAdd');
    });

    // --- Applicability UI toggle for EDIT modal ---
    $('input[name="edit_applicability_type"]').on('change', function() {
        if (this.value === 'specific_students') {
            $('#specificStudentContainerEdit').slideDown();
            // Class dropdown population is handled when edit modal is opened (in get_structure_details success)
        } else {
            $('#specificStudentContainerEdit').slideUp();
        }
    });
    $('#selectClassForStudentEdit').on('change', function() {
        // When class filter changes in edit, repopulate students but don't pre-select old ones
        // unless you want to try and merge. For now, new class selection means new student selection.
        populateStudentDropdown('selectClassForStudentEdit', 'selectStudentsEdit'); 
    });

    // --- Function to load fee structures into the table ---
    function loadFeeStructures() {
        $('#feeStructuresTableBody').html('<tr><td colspan="6" class="text-center">Loading...</td></tr>');
        $.ajax({
            url: actionUrl, type: 'POST', data: { action: 'get_structures' }, dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    let rows = '';
                    if (response.structures && response.structures.length > 0) {
                        response.structures.forEach(function(structure, index) {
                            let applicabilityText = structure.applicability_display || 'General'; // PHP should provide this
                            rows += `<tr>
                                        <td style="text-align: center;">${index + 1}</td>
                                        <td>${escapeHtml(structure.fee_head_name)}</td>
                                        <td>${escapeHtml(structure.amount)}</td>
                                        <td>${escapeHtml(structure.academic_year)}</td>
                                        <td>${escapeHtml(applicabilityText)}</td>
                                        <td>
                                            <button class="btn btn-sm btn-info edit-structure-btn" data-id="${structure.id}"><i class='bx bxs-edit'></i> Edit</button>
                                            <button class="btn btn-sm btn-danger delete-structure-btn" data-id="${structure.id}"><i class='bx bxs-trash'></i> Delete</button>
                                        </td>
                                    </tr>`;
                        });
                    } else { rows = '<tr><td colspan="6" class="text-center">No fee structures found.</td></tr>'; }
                    $('#feeStructuresTableBody').html(rows);
                } else { /* ... error handling ... */ }
            },
            error: function(xhr, status, error) { /* ... error handling ... */ }
        });
    }
    loadFeeStructures();

    // --- Handle "Add New Fee Structure" form submission ---
    $('#saveFeeStructureBtn').on('click', function() {
        let form = $('#addFeeStructureForm');
        let errorDiv = $('#addFeeStructureError');
        errorDiv.hide().text('');

        if (form[0].checkValidity() === false && !form[0].noValidate) { // check noValidate in case you set it
            form.addClass('was-validated');
            return;
        }
        form.removeClass('was-validated');

        let formData = {
            action: 'add_structure',
            fee_head_name: $('#feeHeadName').val().trim(),
            amount: $('#feeAmount').val().trim(),
            academic_year: $('#academicYear').val(),
            applicability_type: $('input[name="applicability_type"]:checked').val(),
            filter_class_id: null, // For context if specific_students
            student_ids: [] 
        };

        if (formData.applicability_type === 'specific_students') {
            formData.filter_class_id = $('#selectClassForStudentAdd').val();
            formData.student_ids = $('#selectStudentsAdd').val() || []; 
            if (!formData.filter_class_id) {
                errorDiv.text('Please select a class before selecting students.').show();
                return;
            }
            if (formData.student_ids.length === 0) {
                errorDiv.text('Please select at least one student if "Specific Student(s)" is chosen.').show();
                return;
            }
        }
        
        if (!formData.fee_head_name || !formData.amount || !formData.academic_year) {
            errorDiv.text('Please fill all required basic fields (Fee Head, Amount, Year).').show(); return;
        }
        if (isNaN(parseFloat(formData.amount)) || parseFloat(formData.amount) < 0) {
            errorDiv.text('Please enter a valid positive amount.').show(); return;
        }
        
        $.ajax({
            url: actionUrl, type: 'POST', data: formData, dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#addFeeStructureModal').modal('hide');
                    form[0].reset();
                    $('#applyGeneralAdd').prop('checked', true); // Default radio
                    $('#specificStudentContainerAdd').hide();
                    $('#selectClassForStudentAdd').val('');
                    $('#selectStudentsAdd').empty().html('<option value="">-- Select a class first --</option>').prop('disabled', true);
                    showToast(response.message, 'success');
                    loadFeeStructures();
                } else { errorDiv.text(response.message || 'Error saving.').show(); }
            },
            error: function(xhr) { errorDiv.text('AJAX error: ' + (xhr.responseJSON ? xhr.responseJSON.message : xhr.statusText )).show(); }
        });
    });

    // --- Handle "Edit" button click ---
    $('#feeStructuresTableBody').on('click', '.edit-structure-btn', function() {
        let structureId = $(this).data('id');
        let errorDiv = $('#editFeeStructureError');
        errorDiv.hide().text('');

        $.ajax({
            url: actionUrl, type: 'POST', data: { action: 'get_structure_details', structure_id: structureId }, dataType: 'json',
            success: function(response) {
                if (response.status === 'success' && response.structure) {
                    $('#editStructureId').val(response.structure.id);
                    $('#editFeeHeadName').val(response.structure.fee_head_name);
                    $('#editFeeAmount').val(response.structure.amount);
                    $('#editAcademicYear').val(response.structure.academic_year);
                    
                    $('#currentApplicabilityDisplayEdit').text(response.structure.applicability_display || 'General');

                    // Reset and hide student specific section for edit
                    $('#specificStudentContainerEdit').hide();
                    $('input[name="edit_applicability_type"]').prop('checked', false);
                    $('#selectClassForStudentEdit').val('');
                    $('#selectStudentsEdit').empty().html('<option value="">-- Select a class first --</option>').prop('disabled', true);

                    if (response.structure.applicability && response.structure.applicability.type === 'specific_students') {
                        $('#editApplySpecificStudents').prop('checked', true);
                        $('#specificStudentContainerEdit').show();
                        populateClassDropdown('selectClassForStudentEdit', function() {
                            // This callback runs after classes are loaded for the edit modal
                            if (response.structure.applicability.filter_class_id) {
                                $('#selectClassForStudentEdit').val(response.structure.applicability.filter_class_id);
                                // Trigger change to load students if class is pre-selected
                                $('#selectClassForStudentEdit').trigger('change'); 
                                // Now populate students for this class and pre-select them (needs a slight delay for student list to load)
                                setTimeout(function() {
                                    populateStudentDropdown('selectClassForStudentEdit', 'selectStudentsEdit', response.structure.applicability.student_ids || []);
                                }, 600); // Increased delay slightly
                            } else {
                                // If no filter_class_id, but type is specific_students (unlikely scenario, but handle)
                                populateStudentDropdown('selectClassForStudentEdit', 'selectStudentsEdit', response.structure.applicability.student_ids || []);
                            }
                        }, response.structure.applicability.filter_class_id); // Pass filter_class_id to preselect class dropdown
                    } else { 
                        $('#editApplyGeneral').prop('checked', true);
                    }
                    
                    $('#editFeeStructureError').hide();
                    $('#editFeeStructureModal').modal('show');
                } else { showToast(response.message || 'Error fetching details.', 'error'); }
            },
            error: function(xhr) { /* ... error handling ... */ }
        });
    });

    // --- Handle "Update Fee Structure" form submission ---
    $('#updateFeeStructureBtn').on('click', function() {
        let form = $('#editFeeStructureForm');
        let errorDiv = $('#editFeeStructureError');
        errorDiv.hide().text('');

        if (form[0].checkValidity() === false && !form[0].noValidate) {
            form.addClass('was-validated');
            return;
        }
        form.removeClass('was-validated');

        let formData = {
            action: 'update_structure',
            structure_id: $('#editStructureId').val(),
            fee_head_name: $('#editFeeHeadName').val().trim(),
            amount: $('#editFeeAmount').val().trim(),
            academic_year: $('#editAcademicYear').val(),
            applicability_type: $('input[name="edit_applicability_type"]:checked').val(),
            filter_class_id: null, // For context if specific_students
            student_ids: []
        };

        if (formData.applicability_type === 'specific_students') {
            formData.filter_class_id = $('#selectClassForStudentEdit').val();
            formData.student_ids = $('#selectStudentsEdit').val() || [];
            if (!formData.filter_class_id) {
                errorDiv.text('Please select a class before selecting students for update.').show();
                return;
            }
            if (formData.student_ids.length === 0) {
                errorDiv.text('Please select at least one student if "Specific Student(s)" is chosen for update.').show();
                return;
            }
        }
        
        if (!formData.structure_id || !formData.fee_head_name || !formData.amount || !formData.academic_year) {
            errorDiv.text('Please fill all required basic fields.').show(); return;
        }
        if (isNaN(parseFloat(formData.amount)) || parseFloat(formData.amount) < 0) {
            errorDiv.text('Please enter a valid positive amount.').show(); return;
        }
        
        $.ajax({
            url: actionUrl, type: 'POST', data: formData, dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#editFeeStructureModal').modal('hide');
                    showToast(response.message, 'success');
                    loadFeeStructures();
                } else { errorDiv.text(response.message || 'Error updating.').show(); }
            },
            error: function(xhr) { errorDiv.text('AJAX error: ' + (xhr.responseJSON ? xhr.responseJSON.message : xhr.statusText)).show(); }
        });
    });

    // --- Delete Handler ---
    $('#feeStructuresTableBody').on('click', '.delete-structure-btn', function() {
        // ... (your existing delete logic, which is fine) ...
    });

    // --- Utility functions (escapeHtml, showToast) ---
    function escapeHtml(text) { /* ... (as corrected before) ... */ }
    function showToast(message, type = 'success') { /* ... (as before) ... */ }
    
    // --- Modal Reset Logic ---
    $('#addFeeStructureModal').on('show.bs.modal', function () {
        $(this).find('.text-danger').hide().text('');
        $('#addFeeStructureForm')[0].reset();
        $('#addFeeStructureForm').removeClass('was-validated');
        $('#applyGeneralAdd').prop('checked', true);
        $('#specificStudentContainerAdd').hide();
        $('#selectClassForStudentAdd').val('');
        $('#selectStudentsAdd').empty().html('<option value="">-- Select a class first --</option>').prop('disabled', true);
    });
    $('#editFeeStructureModal').on('show.bs.modal', function () {
        $(this).find('.text-danger').hide().text('');
        $('#editFeeStructureForm').removeClass('was-validated');
    });
});