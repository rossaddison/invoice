$(function () {
    function parsedata(data) {             
     if (!data) return {};
     if (typeof data === 'object') return data;
     if (typeof data === 'string') return JSON.parse(data);
     return {};
    };
    
    // CHANGING THE FIRST DROPDOWN 'CATEGORY PRIMARY' TO LOAD THE SECOND DROPDOWN 'CATEGORY SECONDARY'
    
    $(document).on('change', '#family-category-primary-id', function () {
        // Get the selected primary category ID
        var primaryCategoryId = $('#family-category-primary-id').val();
        
        // Define the URL for the AJAX request
        var url = $(location).attr('origin') + "/invoice/family/secondaries/"+primaryCategoryId; 

        // Make the AJAX request to get the secondary categories
        $.ajax({
            type: 'GET',
            contentType: "application/json; charset=utf-8",
            url: url,
            data: { category_primary_id: primaryCategoryId },
            cache: false,
            dataType: 'json',
            success: function (data) {
                
                var response = parsedata(data);
                
                if (response.success === 1) {
                    
                    var secondaryCategories = response.secondary_categories;

                    // Find the secondary category dropdown element on family\_search.php
                    var secondaryDropdown = $('#family-category-secondary-id');
                    
                    // Clear the existing options
                    secondaryDropdown.empty();

                    // Add a prompt option
                    secondaryDropdown.append('<option value="">' + "None" + '</option>');

                    // Populate the secondary dropdown with new options
                    $.each(secondaryCategories, function(key, value) {
                        secondaryDropdown.append('<option value="' + key + '">' + value + '</option>');
                    });
                }
            }
        });    
    });

    // If the document loads
    $(document).ready(function() {
        $('#family-category-primary-id').change('#family-category-primary-id');
    });
    
    // CHANGING THE SECOND DROPDOWN TO LOAD THE THIRD DROPDOWN FAMILY NAMES
    
    $(document).on('change', '#family-category-secondary-id', function () {
        // Get the selected secondary category ID
        var secondaryCategoryId = $('#family-category-secondary-id').val();
        
        // Define the URL for the AJAX request
        var url = $(location).attr('origin') + "/invoice/family/names/"+secondaryCategoryId; 

        // Make the AJAX request to get the secondary categories
        $.ajax({
            type: 'GET',
            contentType: "application/json; charset=utf-8",
            url: url,
            data: { category_secondary_id: secondaryCategoryId },
            cache: false,
            dataType: 'json',
            success: function (data) {
                
                var response = parsedata(data);
                
                if (response.success === 1) {
                    
                    var familyNames = response.family_names;

                    // Find the family name dropdown element on family\_search.php
                    var familyNameDropdown = $('#family-name');
                    
                    // Clear the existing options
                    familyNameDropdown.empty();

                    // Add a prompt option
                    familyNameDropdown.append('<option value="">' + "None" + '</option>');

                    // Populate the family name dropdown with new options
                    $.each(familyNames, function(key, value) {
                        familyNameDropdown.append('<option value="' + key + '">' + value + '</option>');
                    });
                }
            }
        });    
    });

    // If the document loads
    $(document).ready(function() {
        $('#family-category-secondary-id').change('#family-category-secondary-id');
    });
});

        
    


