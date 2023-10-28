$(function () {
    function parsedata(data) {             
     if (!data) return {};
     if (typeof data === 'object') return data;
     if (typeof data === 'string') return JSON.parse(data);
     return {};
    };
    
    // https://www.w3schools.com/howto/howto_js_filter_table.asp
    function tableFunction() {
        var filter, table, tr, td, i, txtValue;
        input = $('#filter_product_sku').val();
        filter = input.toUpperCase();
        table = document.getElementById("table-product");
        tr = table.getElementsByTagName("tr");

        // Loop through all table rows, and hide those who don't match the search query
        for (i = 0; i < tr.length; i++) {
          // product_sku is 3rd column or index 2  
          //("td")[2] ... id => 0, family => 1, sku => 2  
          td = tr[i].getElementsByTagName("td")[2];
          if (td) {
            txtValue = td.textContent || td.innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
              tr[i].style.display = "";
            } else {
              tr[i].style.display = "none";
            }
          }
        }
    }
    
    // Using the filter button, located next to the reset button on the product/index.php, 
    // Use the value of the input box 'filter_prodoct_sku' to find a specific product sku
    
    $(document).on('click', '#product_filters_submit', function () {
    var url = $(location).attr('origin') + "/invoice/product/search";
    
    // Location: product/index $toolbarFilter
    var btn = $('.product_filters_submit');
    
    //var absolute_url = new URL($(location).attr('href'));
    btn.html('<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>');
    $.ajax({type: 'GET',
        contentType: "application/json; charset=utf-8",
        data: {
            product_sku: $('#filter_product_sku').val()
        },
        url: url,
        cache: false,
        dataType: 'json',
        success: function (data) {
            var response = parsedata(data);
            if (response.success === 1) {
               tableFunction();
               // hide the summary bar
               document.getElementsByClassName("mt-3 me-3 summary text-end")[0].style.visibility='hidden';
               btn.html('<h6 class="text-center"><i class="fa fa-check"></i></h6>');
            }
        },
        error: function(data) {
            var response = parsedata(data);
            if (response.success === 0) {
               btn.html('<h6 class="text-center"><i class="fa fa-error"></i></h6>');
               alert(response.message);
            }
        }
    });
    });
});

        
    


