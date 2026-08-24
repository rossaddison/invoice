<?php

declare(strict_types=1);

?>

<p class="panel d-block p-2 text bg-warning">Files uploaded here are saved directly to the public assets folder.

    The code that is being used to add the image is located within <code>ProductAttachmentController</code> (extracted from <code>ProductController</code> to satisfy SonarQube's S1448 method-count-per-class rule), specifically the <code>imageAttachment</code> and <code>imageAttachmentMoveTo</code> functions.

    The path aliases that are used in the attachment process are located in <code>src/Invoice/Setting/Trait/SettingFileFolderTrait</code> (used by <code>SettingRepository</code>) and the <code>getProductimagesFilesFolderAliases()</code> function is being used in this regard.

    The specific alias that is being used to save the image to the <code>public/products</code> folder is <code>'@public_product_images'</code>

    This alias is also used in the <code>ProductImageService</code> function <code>deleteProductImage</code> to delete the image from the asset/products folder.
</p>
