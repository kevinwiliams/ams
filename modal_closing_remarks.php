<!-- Closing Remarks Modal - Combined View and Add -->
<div class="modal fade" id="closingRemarksModal" tabindex="-1" role="dialog" aria-labelledby="closingRemarksModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="closingRemarksModalLabel">
                    <i class="fas fa-clipboard-check"></i> Closing Remarks
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Assignment Info Summary -->
                <div class="alert alert-info" id="assignmentSummary" style="display: none;">
                    <strong>Assignment:</strong> <span id="viewAssignmentTitle"></span><br>
                    <strong>Date:</strong> <span id="viewAssignmentDate"></span>
                </div>

                <!-- Tabs for View/Add -->
                <ul class="nav nav-tabs" id="remarkTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="view-tab" data-toggle="tab" href="#view" role="tab" aria-controls="view" aria-selected="true">
                            <i class="fas fa-eye"></i> View Remarks
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="add-tab" data-toggle="tab" href="#add" role="tab" aria-controls="add" aria-selected="false">
                            <i class="fas fa-plus"></i> Add Remark
                        </a>
                    </li>
                </ul>
                
                <div class="tab-content mt-3" id="remarkTabsContent">
                    <!-- View Tab -->
                    <div class="tab-pane fade show active" id="view" role="tabpanel" aria-labelledby="view-tab">
                        <div id="remarksList">
                            <!-- Loading indicator -->
                            <div class="text-center" id="remarksLoading">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <p>Loading remarks...</p>
                            </div>
                            
                            <!-- Remarks will be loaded here -->
                            <div id="remarksContainer"></div>
                            
                            <!-- No remarks message -->
                            <div id="noRemarksMessage" class="alert alert-warning" style="display: none;">
                                <i class="fas fa-info-circle"></i> No closing remarks found for this assignment.
                            </div>
                        </div>
                    </div>
                    
                    <!-- Add Tab -->
                    <div class="tab-pane fade" id="add" role="tabpanel" aria-labelledby="add-tab">
                        <form id="closingRemarksForm">
                            <input type="hidden" id="assignment_id" name="assignment_id">
                            <input type="hidden" id="assignment_date" name="assignment_date">
                            <input type="hidden" id="assignment_title" name="assignment_title">
                            
                            <div class="form-group">
                                <label for="remark_status">Status <span class="text-danger">*</span></label>
                                <select class="form-control" id="remark_status" name="status" required>
                                    <option value="">Select Status</option>
                                    <option value="complete">Complete</option>
                                    <option value="incomplete">Incomplete</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="closing_remark">Closing Remarks <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="closing_remark" name="remark" rows="5" required placeholder="Enter your closing remarks..."></textarea>
                                <small class="form-text text-muted">Please provide detailed closing remarks for this assignment.</small>
                            </div>
                            
                            <div class="form-group">
                                <label>Submitted By</label>
                                <input type="text" class="form-control" value="<?php echo $_SESSION['login_firstname'] . ' ' . $_SESSION['login_lastname']; ?>" readonly>
                            </div>
                            
                            <button type="submit" class="btn btn-primary" id="saveClosingRemark">
                                <i class="fas fa-save"></i> Save Remarks
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Individual Remark View Modal (for clicking on a remark) -->
<div class="modal fade" id="viewRemarkModal" tabindex="-1" role="dialog" aria-labelledby="viewRemarkModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewRemarkModalLabel">Remark Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="card">
                    <div class="card-header" id="remarkDetailStatus"></div>
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted" id="remarkDetailUser"></h6>
                        <p class="card-text" id="remarkDetailText"></p>
                        <small class="text-muted" id="remarkDetailDate"></small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>