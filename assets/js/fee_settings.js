$(document).ready(function() {
    const actionUrl = 'actions/fees/manage_payment_modes.php'; // Path relative to fee_settings.php

    // Function to load payment modes into the table
    function loadPaymentModes() {
        $('#paymentModesTableBody').html('<tr><td colspan="4" class="text-center">Loading...</td></tr>');
        $.ajax({
            url: actionUrl,
            type: 'POST',
            data: { action: 'get_modes' },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    let rows = '';
                    if (response.modes.length > 0) {
                        response.modes.forEach(function(mode, index) {
                            rows += `<tr>
            <td>${index + 1}</td>
            <td>${escapeHtml(mode.mode_name)}</td>
            <td><span class="badge bg-${mode.is_active == 1 ? 'success' : 'danger'}">${mode.is_active == 1 ? 'Active' : 'Inactive'}</span></td>
            <td>
                <button class="btn btn-sm btn-info edit-mode-btn d-inline-flex align-items-center" data-id="${mode.id}"><i class='bx bxs-edit me-1'></i> <span style="line-height: 1;">Edit</span></button> 
                <button class="btn btn-sm btn-${mode.is_active == 1 ? 'warning' : 'success'} toggle-status-btn d-inline-flex align-items-center" data-id="${mode.id}" data-status="${mode.is_active}">
                    <i class='bx bx-power-off me-1'></i> <span style="line-height: 1;">${mode.is_active == 1 ? 'Deactivate' : 'Activate'}</span>
                    <button class="btn btn-sm btn-danger delete-mode-btn" data-id="${mode.id}"><i class='bx bxs-trash'></i></button>
                </button>
            </td>
        </tr>`;
                        });
                    } else {
                        rows = '<tr><td colspan="4" class="text-center">No payment modes found.</td></tr>';
                    }
                    $('#paymentModesTableBody').html(rows);
                } else {
                    $('#paymentModesTableBody').html('<tr><td colspan="4" class="text-center">Error loading modes.</td></tr>');
                    console.error("Error loading modes:", response.message);
                }
            },
            error: function(xhr, status, error) {
                $('#paymentModesTableBody').html('<tr><td colspan="4" class="text-center">Error loading modes.</td></tr>');
                console.error("AJAX Error:", status, error);
            }
        });
    }

    // Load modes on page load
    loadPaymentModes();

    // Add new payment mode
    $('#savePaymentModeBtn').on('click', function() {
        let modeName = $('#newModeName').val().trim();
        if (modeName === '') {
            $('#addPaymentModeError').text('Mode name cannot be empty.').show();
            return;
        }
        $('#addPaymentModeError').hide();

        $.ajax({
            url: actionUrl,
            type: 'POST',
            data: {
                action: 'add_mode',
                mode_name: modeName
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#addPaymentModeModal').modal('hide');
                    $('#addPaymentModeForm')[0].reset();
                    showToast(response.message, 'success');
                    loadPaymentModes();
                } else {
                    $('#addPaymentModeError').text(response.message || 'Error saving mode.').show();
                    showToast(response.message || 'Error saving mode.', 'error');
                }
            },
            error: function() {
                $('#addPaymentModeError').text('An unexpected error occurred.').show();
                showToast('An unexpected error occurred.', 'error');
            }
        });
    });

    // Open Edit Modal and fetch details
    $('#paymentModesTableBody').on('click', '.edit-mode-btn', function() {
        let modeId = $(this).data('id');
        $.ajax({
            url: actionUrl,
            type: 'POST',
            data: { action: 'get_mode_details', mode_id: modeId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#editModeId').val(response.mode.id);
                    $('#editModeName').val(response.mode.mode_name);
                    $('#editModeStatus').prop('checked', response.mode.is_active == 1);
                    $('#editPaymentModeError').hide();
                    $('#editPaymentModeModal').modal('show');
                } else {
                    showToast(response.message || 'Error fetching mode details.', 'error');
                }
            },
            error: function() {
                showToast('An unexpected error occurred.', 'error');
            }
        });
    });

    // Update payment mode
    $('#updatePaymentModeBtn').on('click', function() {
        let modeId = $('#editModeId').val();
        let modeName = $('#editModeName').val().trim();
        let isActive = $('#editModeStatus').is(':checked') ? 1 : 0;

        if (modeName === '') {
            $('#editPaymentModeError').text('Mode name cannot be empty.').show();
            return;
        }
        $('#editPaymentModeError').hide();

        $.ajax({
            url: actionUrl,
            type: 'POST',
            data: {
                action: 'update_mode',
                mode_id: modeId,
                mode_name: modeName,
                is_active: isActive
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#editPaymentModeModal').modal('hide');
                    showToast(response.message, 'success');
                    loadPaymentModes();
                } else {
                    $('#editPaymentModeError').text(response.message || 'Error updating mode.').show();
                    showToast(response.message || 'Error updating mode.', 'error');
                }
            },
            error: function() {
                $('#editPaymentModeError').text('An unexpected error occurred.').show();
                showToast('An unexpected error occurred.', 'error');
            }
        });
    });
    
    // Toggle status (Activate/Deactivate)
    $('#paymentModesTableBody').on('click', '.toggle-status-btn', function() {
        let modeId = $(this).data('id');
        // For deactivation (soft delete), we use the 'delete_mode' action in PHP
        // For activation, we'll use the 'update_mode' action with is_active = 1
        let currentStatus = $(this).data('status');
        let newStatus = currentStatus == 1 ? 0 : 1;
        let actionToTake = (newStatus == 0) ? 'delete_mode' : 'update_mode'; // 'delete_mode' in PHP sets is_active to 0
        let confirmMsg = (newStatus == 0) ? "Are you sure you want to deactivate this payment mode?" : "Are you sure you want to activate this payment mode?";
         
        if (confirm(confirmMsg)) {
             let ajaxData = {
                 action: actionToTake,
                 mode_id: modeId
             };
             // If activating, we need to send mode_name and is_active
             if(actionToTake === 'update_mode' && newStatus === 1){
                 // We need the current mode name to pass to update_mode
                 // This is a bit clunky; ideally, update_mode for status change only needs id and new status
                 // For now, let's fetch it or make a dedicated 'activate_mode' action
                 let modeName = $(this).closest('tr').find('td:nth-child(2)').text(); // Get name from table
                 ajaxData.mode_name = modeName;
                 ajaxData.is_active = 1;
             }

            $.ajax({
                url: actionUrl,
                type: 'POST',
                data: ajaxData,
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        showToast(response.message, 'success');
                        loadPaymentModes();
                    } else {
                        showToast(response.message || 'Error updating status.', 'error');
                    }
                },
                error: function() {
                    showToast('An unexpected error occurred.', 'error');
                }
            });
        }
    });

    // Utility to escape HTML for display
    function escapeHtml(text) {
        var map = {
            '&': '&',  // Ampersand
            '<': '<',   // Less than
            '>': '>',   // Greater than
            '"': '"', // Double quote
             // Single quote 
        };
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // Toast function (you might have this already in your global script.js)
    function showToast(message, type = 'success') {
        // Assuming you have a toast element with id 'liveToast' and a body with id 'toast-alert-message'
        // as per your _header.php
        var liveToast = new bootstrap.Toast(document.getElementById('liveToast'));
        var toastElement = document.getElementById('liveToast');
        var toastBody = document.getElementById('toast-alert-message');

        toastBody.textContent = message;
        if (type === 'success') {
            toastElement.style.backgroundColor = "var(--light-success)"; // Or your success color variable
            toastElement.style.color = "var(--success)";
        } else {
            toastElement.style.backgroundColor = "var(--light-danger)"; // Or your error color variable
            toastElement.style.color = "var(--danger)";
        }
        liveToast.show();
    }

    // Clear modal errors when shown
    $('#addPaymentModeModal, #editPaymentModeModal').on('show.bs.modal', function () {
        $(this).find('.text-danger').hide().text('');
        if ($(this).attr('id') === 'addPaymentModeModal') {
            $('#addPaymentModeForm')[0].reset();
        }
    });
        // ... (all your existing payment modes JS code from before) ...

    // --- Receipt Settings Logic ---
    const receiptSettingsForm = $('#receiptSettingsForm');
    const receiptSettingsError = $('#receiptSettingsError');
    const receiptSettingsSuccess = $('#receiptSettingsSuccess'); // For success messages

    // Function to load existing receipt settings
    function loadReceiptSettings() {
        receiptSettingsError.hide();
        receiptSettingsSuccess.hide();
        // We need a new PHP action to get these settings
        $.ajax({
            url: actionUrl, // We can reuse the same actionUrl if we add a new action in PHP
            type: 'POST',
            data: { action: 'get_receipt_settings' },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success' && response.settings) {
                    $('#receiptPrefix').val(response.settings.receipt_prefix || '');
                    $('#receiptNextNumber').val(response.settings.next_receipt_number || '');
                    $('#receiptSuffix').val(response.settings.receipt_suffix || '');
                } else if (response.message) {
                    // Might be okay if no settings are found yet, don't show as an error unless server says so
                    if(response.status !== 'success') {
                        receiptSettingsError.text(response.message).show();
                    }
                    console.log("Receipt settings note:", response.message);
                }
            },
            error: function(xhr, status, error) {
                receiptSettingsError.text('Error loading receipt settings.').show();
                console.error("AJAX Error loading receipt settings:", xhr, status, error);
            }
        });
    }

    // Load receipt settings on page load
    loadReceiptSettings();

    // Save Receipt Settings
    $('#saveReceiptSettingsBtn').on('click', function() {
        receiptSettingsError.hide();
        receiptSettingsSuccess.hide();

        let prefix = $('#receiptPrefix').val().trim();
        let nextNumber = $('#receiptNextNumber').val().trim();
        let suffix = $('#receiptSuffix').val().trim();

        if (nextNumber !== '' && (isNaN(parseInt(nextNumber)) || parseInt(nextNumber) < 1)) {
            receiptSettingsError.text('Next Receipt Number must be a valid positive number.').show();
            return;
        }
        
        $.ajax({
            url: actionUrl, // Reusing actionUrl
            type: 'POST',
            data: {
                action: 'save_receipt_settings',
                receipt_prefix: prefix,
                next_receipt_number: nextNumber,
                receipt_suffix: suffix
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    receiptSettingsSuccess.text(response.message).show();
                    showToast(response.message, 'success'); // Use your global toast
                    // Optionally, reload settings if needed, but usually not necessary after save
                    // loadReceiptSettings(); 
                } else {
                    receiptSettingsError.text(response.message || 'Error saving receipt settings.').show();
                    showToast(response.message || 'Error saving receipt settings.', 'error');
                }
            },
            error: function(xhr, status, error) {
                receiptSettingsError.text('An unexpected error occurred. Check console.').show();
                console.error("AJAX Error saving receipt settings:", xhr, status, error);
                showToast('An unexpected error occurred saving settings.', 'error');
            }
        });
    });
    // --- End Receipt Settings Logic ---

});