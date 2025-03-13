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
                        <label for="">Gas Level</label>
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