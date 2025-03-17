<!-- Equipment Request Modal -->
<div class="modal fade" id="equipmentModal" tabindex="-1" aria-labelledby="equipmentModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="equipmentModalLabel">Request Equipment</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" aria-hidden="true">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        
                    </div>
                    <div class="modal-body">
                        <textarea id="equipment_details" class="form-control" rows="4" placeholder="Enter equipment request details..."></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id="submitEquipmentRequest" class="btn btn-sm btn-danger"><i class="fa fa-mail"></i> Submit Request</button>
                    </div>
                </div>
            </div>
        </div>