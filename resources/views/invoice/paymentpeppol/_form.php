<?php

declare(strict_types=1);

use Yiisoft\Bootstrap5\Alert;
use Yiisoft\Bootstrap5\AlertVariant;
use Yiisoft\Html\Html;

/**
 * @var Yiisoft\DataResponse\DataResponse      $response
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface   $urlGenerator
 * @var array                                  $body
 * @var string                                 $csrf
 * @var string                                 $actionName
 * @var string                                 $title
 *
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
 */
if (!empty($errors)) {
    /**
     * @var string $field
     * @var string $error
     */
    foreach ($errors as $field => $error) {
        echo Alert::widget()
            ->variant(AlertVariant::DANGER)
            ->body($field.':'.$error, true)
            ->dismissable(true)
            ->render();
    }
}

?>
<?php echo Html::openTag('h1'); ?><?php echo Html::encode($title); ?><?php echo Html::closeTag('h1'); ?>
<form id="PaymentPeppolForm" method="POST" action="<?php echo $urlGenerator->generate($actionName, $actionArguments); ?>" enctype="multipart/form-data">
<input type="hidden" name="_csrf" value="<?php echo $csrf; ?>">
    <div id="headerbar">
    <h1 class="headerbar-title"><?php echo $translator->translate('paymentpeppols.form'); ?></h1>
    <?php echo (string) $response->getBody(); ?>
    <div id="content">
        <div class = 'row'>
           <div class="mb3 form-group">
             <input type="hidden" name="id" id="id" class="form-control"
           value="<?php echo Html::encode($body['id'] ?? ''); ?>">
           </div>
           <div class="mb3 form-group">
             <label for="auto_reference"><?php echo $translator->translate('auto.reference'); ?></label>
             <input type="text" name="auto_reference" id="auto_reference" class="form-control"
           value="<?php echo Html::encode($body['auto_reference'] ?? ''); ?>">
           </div>
           <div class="mb3 form-group">
             <label for="provider"><?php echo $translator->translate('provider'); ?></label>
             <input type="text" name="provider" id="provider" class="form-control"
           value="<?php echo Html::encode($body['provider'] ?? ''); ?>">
           </div>
       </div>
    </div>
</div>
</form>
