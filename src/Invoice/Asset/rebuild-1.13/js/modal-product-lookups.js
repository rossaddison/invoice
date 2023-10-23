function parsedata(data) {             
             if (!data) return {};
             if (typeof data === 'object') return data;
             if (typeof data === 'string') return JSON.parse(data);
             return {};
};

$(function () {
        $(".simple-select").select2();
        
        $("input[name='product_ids[]']").click(function() {
            var at_least_one_checked;
            if ($("input[name='product_ids[]']").is(':checked')) {at_least_one_checked = true; } else {at_least_one_checked = false;}
            if ((at_least_one_checked)){                
                $('.select-items-confirm-quote').removeAttr('disabled');
                $('.select-items-confirm-inv').removeAttr('disabled');
            } else {
                $('.select-items-confirm-quote').attr('disabled', true);
                $('.select-items-confirm-inv').attr('disabled', true);
            }
        });
        
        $(document).on('click', '.select-items-confirm-quote', function () {
            var absolute_url = new URL($(location).attr('href'));
            var btn = $('.select-items-confirm-quote');
            btn.html('<h2 class="text-center" ><i class="fa fa-spin fa-spinner"></i></h2>');
            var product_ids = [];
            quote_id = absolute_url.href.substring(absolute_url.href.lastIndexOf('/') + 1);
            $("input[name='product_ids[]']:checked").each(function () {
                product_ids.push(parseInt($(this).val()));
            });
            $.ajax({ type: 'GET',
                     contentType: "application/json; charset=utf-8",
                     data: {
                            product_ids: product_ids,
                            quote_id: quote_id
                           },
                     url: '/invoice/product/selection_quote',
                     cache: false,
                     dataType: 'json',
                     success: function(data){
                        //https://stackoverflow.com/questions/38380462/syntaxerror-unexpected-token-o-in-json-at-position-1
                        var products = parsedata(data);                        
                        for (var key in products) {
                            // Set default tax rate id if empty
                            if (!products[key].tax_rate_id) {products[key].tax_rate_id = $("#default_item_tax_rate").attr('value');}                            
                            var last_item_row = $('#item_table tbody:last');
                            last_item_row.find('input[name=item_name]').val(products[key].product_name);
                            last_item_row.find('textarea[name=item_description]').val(products[key].product_description);
                            last_item_row.find('input[name=item_price]').val(products[key].product_price);
                            last_item_row.find('input[name=item_quantity]').val('1');
                            last_item_row.find('select[name=item_tax_rate_id]').val(products[key].tax_rate_id);
                            //assign the modally selected entity product's id
                            //For relation purposes, remember product_id had to be changed to id
                            last_item_row.find('input[name=item_product_id]').val(products[key].id);
                            last_item_row.find('select[name=item_product_unit_id]').val(products[key].unit_id);
                            btn.html('<h2 class="text-center"><i class="fa fa-check"></i></h2>');
                        }
                        location.reload(true);
                    }
            });
        });
        
        $(document).on('click', '.select-items-confirm-inv', function () {
            var absolute_url = new URL($(location).attr('href'));
            var btn = $('.select-items-confirm-inv');
            btn.html('<h2 class="text-center" ><i class="fa fa-spin fa-spinner"></i></h2>');
            var product_ids = [];
            inv_id = absolute_url.href.substring(absolute_url.href.lastIndexOf('/') + 1);
            $("input[name='product_ids[]']:checked").each(function () {
                product_ids.push(parseInt($(this).val()));
            });
            $.ajax({ type: 'GET',
                     contentType: "application/json; charset=utf-8",
                     data: {
                            product_ids: product_ids,
                            inv_id: inv_id
                           },
                     url: '/invoice/product/selection_inv',
                     cache: false,
                     dataType: 'json',
                     success: function(data){
                        //https://stackoverflow.com/questions/38380462/syntaxerror-unexpected-token-o-in-json-at-position-1
                        var products = parsedata(data);                        
                        for (var key in products) {
                            // Set default tax rate id if empty
                            if (!products[key].tax_rate_id) {products[key].tax_rate_id = $("#default_item_tax_rate").attr('value');}                            
                            var last_item_row = $('#item_table tbody:last');
                            last_item_row.find('input[name=item_name]').val(products[key].product_name);
                            last_item_row.find('textarea[name=item_description]').val(products[key].product_description);
                            last_item_row.find('input[name=item_price]').val(products[key].product_price);
                            last_item_row.find('input[name=item_quantity]').val('1');
                            last_item_row.find('select[name=item_tax_rate_id]').val(products[key].tax_rate_id);
                            //assign the modally selected entity product's id
                            //For relation purposes, remember product_id had to be changed to id
                            last_item_row.find('input[name=item_product_id]').val(products[key].id);
                            last_item_row.find('select[name=item_product_unit_id]').val(products[key].unit_id);
                            btn.html('<h2 class="text-center"><i class="fa fa-check"></i></h2>');
                        }
                        location.reload(true);
                    }
            });
        });

        // Toggle checkbox when click on row
        $(document).on('click', '.product', function (event) {
            if (event.target.type !== 'checkbox') {
                $(':checkbox', this).trigger('click');
            }
        });
        
        $(document).on('click', '#product-reset-button-quote', function () {
            var product_table = $('#product-lookup-table');
            var lookup_url = $(location).attr('origin') + "/invoice/product/lookup";
            product_table.html('<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>');
            lookup_url += "?fp=''&ff=''&rt=true";
            // Reload modal with settings
            window.setTimeout(function () {
                product_table.load(lookup_url);               
            }, 50);
            if (product_table.contents() !== null) {
                 $('.select-items-confirm-quote').removeAttr('disabled');
                 $('#filter_product_quote').val("");
                 $('#filter_family_quote').val("");
            }            
        });
        
        $(document).on('click', '#product-reset-button-inv', function () {
            var product_table = $('#product-lookup-table');
            var lookup_url = $(location).attr('origin') + "/invoice/product/lookup";
            product_table.html('<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>');
            lookup_url += "?fp=''&ff=''&rt=true";
            // Reload modal with settings
            window.setTimeout(function () {
                product_table.load(lookup_url);               
            }, 50);
            if (product_table.contents() !== null) {
                 $('.select-items-confirm-inv').removeAttr('disabled');
                 $('#filter_product_inv').val("");
            }            
        });
        
        // Filter on search button click
        $(document).on('click', '#filter-button-quote', function () {
            var product_table = $('#product-lookup-table');
            fp =  $('#filter_product_quote').val();
            ff =  $('#filter_family_quote').val();
            //substitute spaces inbetween words in filter_product
            fp = window.escape(fp);            
            var lookup_url = $(location).attr('origin') + "/invoice/product/lookup";
            product_table.html('<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>');
            $('.select-items-confirm-quote').attr('disabled', true);
            if (fp && ff>0) {lookup_url += "?fp="+fp+"&ff="+ff;}
            if (fp && ff===0) {lookup_url += "?fp="+fp;}
            console.log(lookup_url);
            // Reload modal with settings
            window.setTimeout(function () {product_table.load(lookup_url);}, 50);
            if (product_table.contents() !== null) {$('.select-items-confirm-quote').removeAttr('disabled');}
        });
        
        // Filter on search button click
        $(document).on('click', '#filter-button-inv', function () {
            var product_table = $('#product-lookup-table');
            fp =  $('#filter_product_inv').val();
            ff =  $('#filter_family_inv').val();
            //substitute spaces inbetween words in filter_product
            fp = window.escape(fp);            
            var lookup_url = $(location).attr('origin') + "/invoice/product/lookup";
            product_table.html('<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>');
            $('.select-items-confirm-inv').attr('disabled', true);
            if (fp && ff>0) {lookup_url += "?fp="+fp+"&ff="+ff;}
            if (fp && ff===0) {lookup_url += "?fp="+fp;}
            console.log(lookup_url);
            // Reload modal with settings
            window.setTimeout(function () {product_table.load(lookup_url);}, 50);
            if (product_table.contents() !== null) {$('.select-items-confirm-inv').removeAttr('disabled');
            }
        });

        // Filter on family dropdown change
        $(document).on('change', '#filter_family_quote', function () {            
            var product_table = $('#product-lookup-table');
            var lookup_url = $(location).attr('origin') + "/invoice/product/lookup";
            var btn = $('.select-items-confirm-quote');
            btn.html('<h6 class="text-center" ><i class="fa fa-check"> Submit </i></h6>');
            btn.attr('disabled', true);
            product_table.html('<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>');
            ff =  $('#filter_family_quote').val();
            fp =  $('#filter_product_quote').val();
            fp = window.escape(fp);    
            if (ff>0) {                 
                lookup_url += "?ff="+ff;
            }
            if (fp && ff>0) {
                lookup_url += "&fp"+fp;
            }
            if (fp && ff===0) {
                lookup_url += "?fp"+fp+"&ff="+ff;
            }
            // Reload modal with settings
            window.setTimeout(function () {
                product_table.load(lookup_url);
            }, 250);
            if (product_table.contents() !== null) {
                 $('.select-items-confirm-quote').removeAttr('disabled');
            }
        });
        
        // Filter on family dropdown change
        $(document).on('change', '#filter_family_inv', function () {            
            var product_table = $('#product-lookup-table');
            var lookup_url = $(location).attr('origin') + "/invoice/product/lookup";
            var btn = $('.select-items-confirm-inv');
            btn.html('<h6 class="text-center" ><i class="fa fa-check"> Submit </i></h6>');
            btn.attr('disabled', true);
            product_table.html('<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>');
            ff =  $('#filter_family_inv').val();
            fp =  $('#filter_product_inv').val();
            fp = window.escape(fp);    
            if (ff>0) {                 
                lookup_url += "?ff="+ff;
            }
            if (fp && ff>0) {
                lookup_url += "&fp"+fp;
            }
            if (fp && ff===0) {
                lookup_url += "?fp"+fp+"&ff="+ff;
            }
            // Reload modal with settings
            window.setTimeout(function () {
                product_table.load(lookup_url);
            }, 250);
            if (product_table.contents() !== null) {
                 $('.select-items-confirm-inv').removeAttr('disabled');
            }
        });

        // Bind enter to product search if search field is focused
        $(document).keypress(function(e){
            if (e.which === 13 && $('#filter_product_quote').is(':focus')){
                $('#filter-button-quote').click();
                return false;
            }
            if (e.which === 13 && $('#filter_product_inv').is(':focus')){
                $('#filter-button-inv').click();
                return false;
            }
        });
});