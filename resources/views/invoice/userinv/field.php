<?php

declare(strict_types=1);

use App\Invoice\Helpers\ClientHelper;
use Yiisoft\Html\Html;

/**
 * @var App\Infrastructure\Persistence\UserClient\UserClient $userClient
 * @var App\Infrastructure\Persistence\UserInv\UserInv $userInv
 * @var App\Invoice\Client\ClientRepository $cR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Invoice\UserClient\UserClientRepository $ucR
 * @var Yiisoft\Data\Cycle\Reader\EntityReader $users;
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $csrf
 * @var string $alert
 */

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';
$client_helper = new ClientHelper($s);

?>
<div id="headerbar">
    <h1 class="headerbar-title">
        <?= $translator->translate('assigned.clients'); ?>
    </h1>
    <div class="headerbar-item float-end">
        <div class="btn-group btn-group-sm">
            <a class="btn btn-secondary" href="<?= $urlGenerator->generate(
                                                            'userinv/index'); ?>">
                <i class="bi bi-arrow-left"></i> <?= $translator->translate(
                                                                     'back'); ?>
            </a>
            <a class="btn btn-primary" href="<?= $urlGenerator->generate(
                   'userclient/new', ['user_id' => $userInv->reqUserId()]); ?>">
                <i class="bi bi-plus-lg"></i> <?= $translator->translate('new'); ?>
            </a>
        </div>
    </div>
</div>

<div id="content">
    <?= Html::openTag('div', ['class' => 'row']); ?>
    <div class="col-12 col-md-6 offset-md-3">
        <div class="card">
            <div class="card-header">
                <?= $translator->translate('user')
                    . ': '
                    . Html::encode($userInv->getName()); ?>
            </div>
            <div class="card-body table-content">
                <div class="table-responsive m-0">
                    <table class="table table-hover table-striped m-0">
                        <thead>
                        <tr>
                            <th><?= $translator->translate('client'); ?></th>
                            <th><?= $translator->translate('options'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
<?php
    /**
     * @var App\Infrastructure\Persistence\UserClient\UserClient $userClient
     */
    foreach ($ucR->repoClientquery($userInv->reqUserId()) as $userClient) { ?>
                            <tr>
                                <td>
                                    <a href="<?=
                                        $urlGenerator->generate(
                                        'client/view',
                                        ['id' => $userClient->reqClientId()]); ?>"
                                       style="text-decoration:none">
                                        <?php
                                            $client = $cR->repoClientquery( 
                                                    $userClient->reqClientId());
                                    echo $client_helper->formatClient($client);
                                ?>
                                    </a>
                                </td>
                                <td>
                                    <form
                                        action="<?=
                                                    $urlGenerator->generate(
                                                    'userclient/delete',
                                                ['id' => $userClient->reqId()]); ?>"
                                        method="POST"
                                        enctype="multipart/form-data"
                                        data-bs-toggle="tooltip"
                                        title="userclient/delete">
                                        <input type="hidden"
                                               name="_csrf"
                                               value="<?= $csrf ?>">
                                        <button type="submit"
                                                class="btn btn-secondary btn-sm"
                                                onclick="return confirm('<?=
                                                $translator->translate(
                                                'delete.user.client.warning'); ?>');">
                                            <i class="bi-trash">
                                            </i><?= $translator->translate('remove'); ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
