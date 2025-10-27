<?php
declare(strict_types=1);

/**
 * Simple modal for adding quote tax - rebuilt from scratch
 */
?>

<div id="add-quote-tax" class="modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
           <div class="modal-header">
               <h5 class="modal-title">Add Quote Tax</h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label for="tax_rate_id" class="form-label">Tax Rate</label>
                        <select name="tax_rate_id" id="tax_rate_id" class="form-control" required>
                            <option value="">None</option>
                            <option value="1">0% - Zero</option>
                            <option value="2">20% - Standard</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="include_item_tax" class="form-label">Tax Placement</label>
                        <select name="include_item_tax" id="include_item_tax" class="form-control">
                            <option value="0">Apply before item tax</option>
                            <option value="1">Apply after item tax</option>
                        </select>
                    </div>
                </form>    
            </div>
            <div class="modal-footer">
                <button id="quote_tax_submit" class="btn btn-success" type="button">
                    <i class="fa fa-check"></i> Submit
                </button>
                <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">
                    <i class="fa fa-times"></i> Cancel
                </button>
            </div>
        </div>
    </div>
</div>