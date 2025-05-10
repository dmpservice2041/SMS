<?php
include_once __DIR__ . '/partials/_header.php';
include_once __DIR__ . '/partials/_sidebar.php';
?>

<!-- Main Content -->
<div class="content">
    <?php include_once __DIR__ . "/partials/_navbar.php"; ?>

    <main>
        <div class="header">
            <div class="left">
                <h1>Fee Settings</h1>
            </div>
        </div>

        <div class="bottom-data">
            <!-- Payment Modes Section -->
            <div class="orders"> 
                <div class="header" style="display: flex; justify-content: space-between; align-items: center;">
                    <div> <!-- Wrapper for title and icon -->
                        <i class='bx bx-credit-card-alt'></i>
                        <h3>Manage Payment Modes</h3>
                    </div>
                    <div> <!-- Wrapper for button -->
                        <button type="button" class="btn btn-primary btn-sm d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#addPaymentModeModal">
                        <i class='bx bx-plus me-1'></i> Add New Mode
                        </button>
                    </div>
                </div>
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th style="width: 60px; text-align: center;">Sr. No.</th> <!-- Corrected & Styled -->
                            <th>Mode Name</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="paymentModesTableBody">
                        <tr><td colspan="4" class="text-center">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
            <!-- End Payment Modes Section -->

            <hr class="my-4">

            <!-- Receipt Settings Section -->
            <div class="orders">
                <div class="header">
                    <i class='bx bx-receipt'></i>
                    <h3>Receipt Settings</h3>
                </div>
                <form id="receiptSettingsForm" style="margin-top: 20px;">
                    <div class="row mb-3">
                        <label for="receiptPrefix" class="col-sm-3 col-form-label">Receipt Prefix</label>
                        <div class="col-sm-7">
                            <input type="text" class="form-control" id="receiptPrefix" name="receipt_prefix" placeholder="e.g., INV-, REC-">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="receiptNextNumber" class="col-sm-3 col-form-label">Next Number</label>
                        <div class="col-sm-7">
                            <input type="number" class="form-control" id="receiptNextNumber" name="next_receipt_number" placeholder="e.g., 1001" min="1">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="receiptSuffix" class="col-sm-3 col-form-label">Receipt Suffix <small>(Optional)</small></label>
                        <div class="col-sm-7">
                            <input type="text" class="form-control" id="receiptSuffix" name="receipt_suffix" placeholder="e.g., /<?php echo date('y') . '-' . (date('y') + 1); ?>">
                        </div>
                    </div>
                     <div id="receiptSettingsError" class="text-danger mb-3" style="display:none;"></div>
                     <div id="receiptSettingsSuccess" class="text-success mb-3" style="display:none;"></div>
                    <div class="row">
                        <div class="col-sm-7 offset-sm-3">
                            <button type="button" class="btn btn-primary" id="saveReceiptSettingsBtn">Save Receipt Settings</button>
                        </div>
                    </div>
                </form>
            </div>
            <!-- End Receipt Settings Section -->

        </div>
    </main>
</div>

<!-- Add Payment Mode Modal (Keep as is) -->
<div class="modal fade" id="addPaymentModeModal" tabindex="-1" aria-labelledby="addPaymentModeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPaymentModeModalLabel">Add New Payment Mode</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addPaymentModeForm">
                    <div class="mb-3">
                        <label for="newModeName" class="form-label">Mode Name</label>
                        <input type="text" class="form-control" id="newModeName" name="mode_name" required>
                    </div>
                    <div id="addPaymentModeError" class="text-danger" style="display:none;"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="savePaymentModeBtn">Save Mode</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Payment Mode Modal (Keep as is) -->
<div class="modal fade" id="editPaymentModeModal" tabindex="-1" aria-labelledby="editPaymentModeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPaymentModeModalLabel">Edit Payment Mode</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editPaymentModeForm">
                    <input type="hidden" id="editModeId" name="mode_id">
                    <div class="mb-3">
                        <label for="editModeName" class="form-label">Mode Name</label>
                        <input type="text" class="form-control" id="editModeName" name="mode_name" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="editModeStatus" name="is_active">
                        <label class="form-check-label" for="editModeStatus">Active</label>
                    </div>
                    <div id="editPaymentModeError" class="text-danger" style="display:none;"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="updatePaymentModeBtn">Update Mode</button>
            </div>
        </div>
    </div>
</div>


<?php include_once __DIR__ . '/partials/_footer.php'; ?>
<script src="../assets/js/fee_settings.js"></script> 