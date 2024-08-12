function parsedata(data) {             
             if (!data) return {};
             if (typeof data === 'object') return data;
             if (typeof data === 'string') return JSON.parse(data);
             return {};
};

$(function () {

        var selectedTasks = [];
        $('.item-task-id').each(function () {
            var currentVal = $(this).val();
            if (currentVal.length) {
                selectedTasks.push(parseInt(currentVal));
            }
        });

        var hiddenTasks = 0;
        $('.modal-task-id').each(function () {
            var currentId = parseInt($(this).attr('id').replace('task-id-', ''));
            if (selectedTasks.indexOf(currentId) !== -1) {
            //  $('#task-id-' + currentId).prop('disabled', true);
                $('#task-id-' + currentId).parent().parent().hide();
                hiddenTasks++;
            }
        });

        if (hiddenTasks >= $('.task-row').length) {
            $('#task-modal-submit').hide();
        }
    
        $('#tasks_table tr').click(function (event) {
            if (event.target.type !== 'checkbox') {
                $(':checkbox', this).trigger('click');
            }
        });
        
        $(document).on('click', '.select-items-confirm-task', function () {
            var absolute_url = new URL($(location).attr('href'));
            var btn = $('.select-items-confirm-task');
            btn.html('<h2 class="text-center" ><i class="fa fa-spin fa-spinner"></i></h2>');
            var task_ids = [];
            inv_id = absolute_url.href.substring(absolute_url.href.lastIndexOf('/') + 1);
            $("input[name='task_ids[]']:checked").each(function () {
                task_ids.push(parseInt($(this).val()));
            });
            $.ajax({ type: 'GET',
                     contentType: "application/json; charset=utf-8",
                     data: {
                            task_ids: task_ids,
                            inv_id: inv_id
                           },
                     url: '/invoice/task/selection_inv',
                     cache: false,
                     dataType: 'json',
                     success: function(data){
                        var tasks = parsedata(data);
                        var taskDefaultTaxRateId = new Object(null);
                        var currentTaxRateId = new Object(null);
                        for (var key in tasks) {
                            /**
                             * @see resources\views\invoice\task\modal_task_lookups_inv.php
                             * Only Completed Tasks (not in-progress Tasks) are only included on invoices and not quotes
                             * @see App\Invoice\Entity\Task 
                             * #[Column(type: 'integer(11)', nullable: false)]
                             * private ?int $tax_rate_id = null;
                             * Every task must have a tax rate id even if it represents a 0 percentage
                             * Previously:
                             * if (!tasks[key].tax_rate_id) {tasks[key].tax_rate_id = $("#default_item_tax_rate").attr('value');}                            
                             * 
                             * @see https://cwe.mitre.org/data/definitions/1321.html
                             * Prevent 'prototype pollution' by setting  the prototype of the created object 
                             * directly via the first argument passed to Object.create(). If we pass null, the
                             * created object will not have a prototype and therefore cannot be polluted. i.e. new Object(null)
                             * Courtesy of Snyk
                             */
                            currentTaxRateId = tasks[key].tax_rate_id;
                            if (!currentTaxRateId) {
                                taskDefaultTaxRateId = $("#default_item_tax_rate").attr('value');
                            } else {
                                taskDefaultTaxRateId = currentTaxRateId;
                            }
                            var last_item_row = $('#item_table tbody:last');                           
                            var last_item_row = $('#item_table tbody:last');
                            last_item_row.find('input[name=item_name]').val(tasks[key].name);
                            last_item_row.find('textarea[name=item_description]').val(tasks[key].description);
                            last_item_row.find('input[name=item_price]').val(tasks[key].price);
                            last_item_row.find('input[name=item_quantity]').val('1');
                            
                            last_item_row.find('select[name=item_tax_rate_id]').val(taskDefaultTaxRateId);
                            
                            last_item_row.find('input[name=item_task_id]').val(tasks[key].id);
                            btn.html('<h2 class="text-center"><i class="fa fa-check"></i></h2>');
                        }
                        location.reload(true);
                    }
            });
        });
});