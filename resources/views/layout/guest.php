<?php
declare(strict_types=1);

use App\Invoice\Asset\InvoiceAsset;
use App\Invoice\Asset\MonospaceAsset;
// DatePicker Assets available for dropdown locale/cldr selection
use App\Invoice\Asset\i18nAsset\af_Asset;
use App\Invoice\Asset\i18nAsset\ar_Asset;
use App\Invoice\Asset\i18nAsset\az_Asset;
use App\Invoice\Asset\i18nAsset\de_DE_Asset;
use App\Invoice\Asset\i18nAsset\en_GB_Asset;
use App\Invoice\Asset\i18nAsset\es_ES_Asset;
use App\Invoice\Asset\i18nAsset\fr_FR_Asset;
use App\Invoice\Asset\i18nAsset\id_Asset;
use App\Invoice\Asset\i18nAsset\ja_Asset;
use App\Invoice\Asset\i18nAsset\nl_Asset;
use App\Invoice\Asset\i18nAsset\ru_Asset;
use App\Invoice\Asset\i18nAsset\sk_Asset;
use App\Invoice\Asset\i18nAsset\uk_UA_Asset;
use App\Invoice\Asset\i18nAsset\uz_UZ_Asset;
use App\Invoice\Asset\i18nAsset\zh_CN_Asset;

// PCI Compliant Payment Gateway Assets
use App\Invoice\Asset\pciAsset\stripe_v10_Asset;
use App\Invoice\Asset\pciAsset\amazon_pay_v2_4_Asset;
use App\Invoice\Asset\pciAsset\braintree_dropin_1_33_7_Asset;

use App\Asset\AppAsset;
use App\Widget\PerformanceMetrics;

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Button;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\Meta;

use Yiisoft\Yii\Bootstrap5\Nav;
use Yiisoft\Yii\Bootstrap5\NavBar;

/**
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var \Yiisoft\Router\CurrentRoute $currentRoute
 * @var \Yiisoft\Assets\AssetManager $assetManager
 * @var \Yiisoft\Translator\TranslatorInterface $translator
 * @var \App\Invoice\Setting\SettingRepository $s
 * @var \App\Invoice\Helpers\DateHelper $datehelper
 * @var \App\User\User|null $user
 * @var string $csrf 
 * @var string $content
 * @var string $brandLabel
 */

$assetManager->register(AppAsset::class);
$assetManager->register(InvoiceAsset::class);
$assetManager->register(Yiisoft\Yii\Bootstrap5\Assets\BootstrapAsset::class);
$s->get_setting('monospace_amounts') == 1 ? $assetManager->register(MonospaceAsset::class) : '';
// '0' => PCI Compliant version 
$s->get_setting('gateway_stripe_version') == '0' ? $assetManager->register(stripe_v10_Asset::class) : '';
$s->get_setting('gateway_amazon_pay_version') == '0' ? $assetManager->register(amazon_pay_v2_4_Asset::class) : '';
$s->get_setting('gateway_braintree_version') == '0' ? $assetManager->register(braintree_dropin_1_33_7_Asset::class) : '';
// The InvoiceController/index receives the $session->get('_language') or 'drop-down' locale user selection and saves it into a setting called 'cldr'
// The $s value is configured for the layout in config/params.php yii-soft/view Reference::to and NOT by means of the InvoiceController

switch ($session->get('_language')) {
    case 'af' : $assetManager->register(af_Asset::class); $locale = 'Afrikaans'; break;
    case 'ar' : $assetManager->register(ar_Asset::class); $locale = 'Arabic'; break;
    case 'az' : $assetManager->register(az_Asset::class); $locale = 'Azerbaijani'; break;
    case 'de' : $assetManager->register(de_DE_Asset::class); $locale = 'German'; break;
    case 'en' : $assetManager->register(en_GB_Asset::class); $locale = 'English'; break;
    case 'fr' : $assetManager->register(fr_FR_Asset::class); $locale = 'French'; break;
    case 'id' : $assetManager->register(id_Asset::class); $locale = 'Indonesian'; break;
    case 'ja' : $assetManager->register(ja_Asset::class); $locale = 'Japanese'; break;
    case 'nl' : $assetManager->register(nl_Asset::class); $locale = 'Dutch'; break;
    case 'ru' : $assetManager->register(ru_Asset::class); $locale = 'Russian'; break;
    case 'sk' : $assetManager->register(sk_Asset::class); $locale = 'Slovensky'; break;    
    case 'es' : $assetManager->register(es_ES_Asset::class); $locale = 'Spanish'; break;
    case 'uk' : $assetManager->register(uk_UA_Asset::class); $locale = 'Ukrainian'; break;
    case 'uz' : $assetManager->register(uz_UZ_Asset::class); $locale = 'Uzbek'; break;
    case 'zh' : $assetManager->register(zh_CN_Asset::class); $locale = 'Chinese Simplified'; break;
    default   : $assetManager->register(en_GB_Asset::class); $locale = 'English'; break;
}

// If the dropdown locale has not been set on login => use the cldr setting value. If the cldr does not exist => use the 'en' value
$s->save_session_locale_to_cldr($session->get('_language') ?? ($s->get_setting('cldr') ?: 'en'));

$this->addCssFiles($assetManager->getCssFiles());
$this->addCssStrings($assetManager->getCssStrings());
$this->addJsFiles($assetManager->getJsFiles());

$this->addJsStrings($assetManager->getJsStrings());
$this->addJsVars($assetManager->getJsVars());

$currentRouteName = $currentRoute->getName() ?? '';

$isGuest = $user === null || $user->getId() === null;

$this->beginPage();
?>

<!DOCTYPE html>
<html lang="<?= $s->get_setting('cldr') !== $session->get('_language')  ? $session->get('_language') : $s->get_setting('cldr'); ?>">
<head>
    <?= Meta::documentEncoding('utf-8')?>
    <?= Meta::pragmaDirective('X-UA-Compatible', 'IE=edge,chrome=1') ?>
    <?= Meta::data('viewport', 'width=device-width, initial-scale=1') ?>
    <?= Meta::data('robots', 'NOINDEX,NOFOLLOW') ?>
    <title>
        <?= $s->get_setting('custom_title') ?: 'Yii-Invoice'; ?>
    </title>
    <?php $this->head() ?>
</head>
<body>
<?php
    Html::tag('Noscript',Html::tag('Div',$s->trans('please_enable_js'),['class'=>'alert alert-danger no-margin']));
?>
<?php
$this->beginBody();

echo NavBar::widget()
->brandText($brandLabel)
->brandUrl($urlGenerator->generate('site/index'))
->begin();

echo Nav::widget()
->currentPath($currentRoute->getUri()->getPath())        
->options(['class'=>'navbar fs-4']) 
->items( 
    $isGuest
        ? [
        ['label' => $translator->translate('invoice.invoice'), 'url' => $urlGenerator->generate('invoice/index'),],
    ] :
    [               
         ['label' => $translator->translate('invoice.client'), 
          'items' => [
                     ['options'=>['class'=>'nav fs-4'],'label' => $translator->translate('invoice.view'),
                      'url'=>$urlGenerator->generate('client/guest')],
                    ],
         ],
         ['label' => $translator->translate('invoice.quote'), 
          'items' => [
                     ['options'=>['class'=>'nav fs-4'],'label' => $translator->translate('invoice.view'),
                      'url'=>$urlGenerator->generate('quote/guest')],
                    ],
         ],
         ['label' => $translator->translate('invoice.salesorder'), 
          'items' => [
                     ['options'=>['class'=>'nav fs-4'], 'label' => $translator->translate('invoice.view'),
                      'url'=>$urlGenerator->generate('salesorder/guest')],
                    ],
         ],
         ['label' => $translator->translate('invoice.invoice'), 
          'items' => [
                     ['options'=>['class'=>'nav fs-4'], 'label' => $translator->translate('invoice.view'),
                      'url'=>$urlGenerator->generate('inv/guest')],
                    ],
         ],
         ['label' => $translator->translate('invoice.payment'), 
          'items' => [
                     ['options'=>['class'=>'nav fs-4'],'label' => $translator->translate('invoice.view'),
                      'url'=>$urlGenerator->generate('payment/guest')],
                     ['options'=>['class'=>'nav fs-4'],'label' => $translator->translate('invoice.online.log'),
                      'url'=>$urlGenerator->generate('payment/guest_online_log')] 
                    ],
         ],
         ['label' => $translator->translate('invoice.setting'), 
          'items' => [
                     ['options'=>['class'=>'nav fs-4'],
                      'label' => $translator->translate('invoice.view'),
                      'url'=>$urlGenerator->generate('userinv/guest')],
                     ['options' => ['class' => 'nav fs-4'], 
                      'label' => $translator->translate('reset'), 
                      'url' => $urlGenerator->generate('auth/reset')],
                    ],
         ],
                 
    ]       
);

echo Nav::widget()
->currentPath($currentRoute
    ->getUri()
    ->getPath())
->options(['class' => 'navbar-nav'])
->items(
    [
        [
            'label' => $translator->translate('menu.language'),
            'url' => '#',
            'items' => [
                [
                    'label' => 'Afrikaans',
                    'url' => $urlGenerator->generateFromCurrent(['_language' => 'af'], fallbackRouteName: 'site/index'),
                ],
                [
                    'label' => 'Arabic / عربي',
                    'url' => $urlGenerator->generateFromCurrent(['_language' => 'ar'], fallbackRouteName: 'site/index'),
                ],
                [
                    'label' => 'Azerbaijani / Azərbaycan',
                    'url' => $urlGenerator->generateFromCurrent(['_language' => 'az'], fallbackRouteName: 'site/index'),
                ], 
                [
                    'label' => 'Chinese Simplified / 简体中文',
                    'url' => $urlGenerator->generateFromCurrent(['_language' => 'zh'], fallbackRouteName: 'site/index'),
                ],
                [
                    'label' => 'English',
                    'url' => $urlGenerator->generateFromCurrent(['_language' => 'en'], fallbackRouteName: 'site/index'),
                ],
                [
                    'label' => 'French / Français',
                    'url' => $urlGenerator->generateFromCurrent(['_language' => 'fr'], fallbackRouteName: 'site/index'),
                ],
                [
                    'label' => 'Dutch / Nederlands',
                    'url' => $urlGenerator->generateFromCurrent(['_language' => 'nl'], fallbackRouteName: 'site/index'),
                ],
                [
                    'label' => 'German / Deutsch',
                    'url' => $urlGenerator->generateFromCurrent(['_language' => 'de'], fallbackRouteName: 'site/index'),
                ],
                [
                    'label' => 'Indonesian / bahasa Indonesia',
                    'url' => $urlGenerator->generateFromCurrent(['_language' => 'id'], fallbackRouteName: 'site/index'),
                ],
                [
                    'label' => 'Japanese / 日本',
                    'url' => $urlGenerator->generateFromCurrent(['_language' => 'ja'], fallbackRouteName: 'site/index'),
                ],
                [
                    'label' => 'Russian / Русский',
                    'url' => $urlGenerator->generateFromCurrent(['_language' => 'ru'], fallbackRouteName: 'site/index'),
                ],
                [
                    'label' => 'Slovakian / Slovenský',
                    'url' => $urlGenerator->generateFromCurrent(['_language' => 'sk'], fallbackRouteName: 'site/index'),
                ],
                [
                    'label' => 'Spanish / Española x',
                    'url' => $urlGenerator->generateFromCurrent(['_language' => 'es'], fallbackRouteName: 'site/index'),
                ],
                [
                    'label' => 'Ukrainian / українська',
                    'url' => $urlGenerator->generateFromCurrent(['_language' => 'uk'], fallbackRouteName: 'site/index'),
                ],
                [
                    'label' => 'Uzbek / o'."'".'zbek',
                    'url' => $urlGenerator->generateFromCurrent(['_language' => 'uz'], fallbackRouteName: 'site/index'),
                ],
                [
                    'label' => 'Vietnamese / Tiếng Việt',
                    'url' => $urlGenerator->generateFromCurrent(['_language' => 'vi'], fallbackRouteName: 'site/index'),
                ],
        ],
        ],
    ],
);        

echo Nav::widget()
->currentPath($currentRoute->getUri()->getPath())
->options(['class' => 'navbar-nav'])
->items(
    [
        [
            'label' => $translator->translate('menu.login'),
            'url' => $urlGenerator->generate('auth/login'),
            'visible' => $isGuest,
        ],
        [
            'label' => $translator->translate('menu.signup'),
            'url' => $urlGenerator->generate('auth/signup'),
            'visible' => $isGuest,
        ],
        $isGuest ? '' : Form::tag()
                ->post($urlGenerator->generate('auth/logout'))
                ->csrf($csrf)
                ->open()
            . '<div class="mb-1">'
            . Button::submit(
                $translator->translate('menu.logout', ['login' => Html::encode($user->getLogin())])
            )
                ->class('btn btn-primary')
            . '</div>'
            . Form::tag()->close()
    ],
);

echo NavBar::end();

?>

<div id="main-area">
    <main class="container py-4">
        <?php echo $content; ?>
        <div id="fullpage-loader" style="display: none">
            <div class="loader-content">
                <i id="loader-icon" class="fa fa-cog fa-spin"></i>
                <div id="loader-error" style="display: none">
                   <br/>
                    <a href="" class="btn btn-primary btn-sm" target="_blank">
                        <i class="fa fa-support"></i>
                    </a>
                </div>
            </div>
            <div class="text-right">
                <button type="button" class="fullpage-loader-close btn btn-link tip" aria-label="<?php $s->trans('close'); ?>"
                        title="<?= $s->trans('close'); ?>" data-placement="left">
                    <span aria-hidden="true"><i class="fa fa-close"></i></span>
                </button>
            </div>
        </div>
    </main>
</div>
<footer class="container py-4">
    <?= PerformanceMetrics::widget() ?>
</footer> 
    <?php
        $this->endBody();
    ?>
</body>
</html>
 
<?php 
    $js1 = "$(function () {".
       '$(".form-control.input-sm.datepicker").datepicker({dateFormat:"'.$datehelper->datepicker_dateFormat()
                                                        .'", firstDay:'.$datehelper->datepicker_firstDay()
                                                        .', changeMonth: true'
                                                        .', changeYear: true'
                                                        .', yearRange: "-50:+10"'
                                                        .', clickInput: true'
                                                        .', constrainInput: false'
                                                        .', highlightWeek: true'
                                                        .' });'.
    '});';
    echo Html::script($js1)->type('module');
?>
<?php
   $this->endPage(true);     
?>
