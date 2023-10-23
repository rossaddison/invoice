<?php
declare(strict_types=1);

?>

<p class="panel d-block p-2 text bg-info">Files uploaded here are saved directly to the public assets folder. 
 
    The code that is being used to add the image is located within the <code>ProductImageController</code>, specifically the <code>add</code> function. 
 
    The path aliases that are used in the attachment process are located in <code>src/Invoice/Setting/SettingsRepository</code> and the <code>get_productimages_files_folder_aliases()</code> function is being used in this regard. 
 
    The specific alias that is being used to save the image to the <code>public/products</code> folder is <code>'@public_product_images'</code>

    This alias is also used in the <code>ProductImageService</code> function <code>deleteProductImage</code> to delete the image from the asset/products folder.
</p>
