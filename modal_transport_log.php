<!-- Update Transport Log Modal -->
<div class="modal fade" id="updateTransportLogModal" tabindex="-1" role="dialog" aria-labelledby="updateTransportLogModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateTransportLogModalLabel">Update Transport Log</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="updateTransportLogForm">
                    <input type="hidden" id="assignment_id" name="assignment_id">
                    <div class="form-group">
                        <label for="mileage">Mileage</label>
                        <input type="number" class="form-control" id="mileage" name="mileage" required>
                    </div>
                    <div class="form-group">
                        <label for="">Fuel Level</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="gas_level" id="gas_level_empty" value="empty">
                                <label class="form-check-label" for="gas_level_empty">Empty</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="gas_level" id="gas_level_qtr" value="qtr">
                                <label class="form-check-label" for="gas_level_qtr">1/4</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="gas_level" id="gas_level_half" value="half">
                                <label class="form-check-label" for="gas_level_half">1/2</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="gas_level" id="gas_level_3qtr" value="3/4">
                                <label class="form-check-label" for="gas_level_3qtr">3/4</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="gas_level" id="gas_level_full" value="full">
                                <label class="form-check-label" for="gas_level_full">Full</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="updateTransportLogBtn">Save changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Gate Pass Log Modal -->
<div class="modal fade" id="gatePassLogModal" tabindex="-1" role="dialog" aria-labelledby="gatePassLogModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="gatePassLogModalLabel">Add Gate Pass Log</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="gatePassLogForm">
                    <input type="hidden" id="gate_assignment_id" name="assignment_id">
                    <input type="hidden" id="gate_security_name" name="security_out" value="<?= $login_firstname . ' ' . $_SESSION['login_lastname'] ?>">
                    <div class="form-group">
                        <label for="security_out">Security</label>
                        <input type="text" class="form-control" id="security_out" name="security_out" required>
                    </div>
                    <div class="form-group d-none">
                        <label for="security_in">Security In Name</label>
                        <input type="text" class="form-control" id="security_in" name="security_in">
                    </div>
                    <div class="form-group">
                        <label for="security_out_time">Time</label>
                        <input type="datetime-local" class="form-control" id="security_out_time" name="security_out_time" required>
                    </div>
                    <div class="form-group">
                        <label for="security_notes">Security Notes</label>
                        <textarea class="form-control" id="security_notes" name="security_notes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveGatePassLogBtn">Save Log</button>
            </div>
        </div>
    </div>
</div>