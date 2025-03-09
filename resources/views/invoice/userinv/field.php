<?php

declare(strict_types=1);

use App\Invoice\Helpers\ClientHelper;
use Yiisoft\Html\Html;

/**
 * @var App\Invoice\Entity\UserClient $userClient
 * @var App\Invoice\Entity\UserInv $userInv
 * @var App\Invoice\Client\ClientRepository $cR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Invoice\UserClient\UserClientRepository $ucR
 * @var Yiisoft\Data\Cycle\Reader\EntityReader $users;
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $csrf
 * @var string $alert
 */

echo $alert;
$client_helper = new ClientHelper($s);

?>
<div id="headerbar">
    <h1 class="headerbar-title"><?= $translator->translate('i.assigned_clients'); ?></h1>

    <div class="headerbar-item pull-right">
        <div class="btn-group btn-group-sm">
            <a class="btn btn-default" href="<?= $urlGenerator->generate('userinv/index'); ?>">
                <i class="fa fa-arrow-left"></i> <?= $translator->translate('i.back'); ?>
            </a>
            <a class="btn btn-primary" href="<?= $urlGenerator->generate('userclient/new', ['user_id' => $userInv->getUser_id()]); ?>">
                <i class="fa fa-plus"></i> <?= $translator->translate('i.new'); ?>
            </a>
        </div>
    </div>
</div>

<div id="content">
    <?= Html::openTag('div', ['class' => 'row']); ?>
        <div class="col-xs-12 col-md-6 col-md-offset-3">

            <div class="panel panel-default">
                <div class="panel-heading">
                    <?= $translator->translate('i.user') . ': ' . Html::encode($userInv->getName()); ?>
                </div>

                <div class="panel-body table-content">
                    <div class="table-responsive no-margin">
                        <table class="table table-hover table-striped no-margin">

                            <thead>
                            <tr>
                                <th><?= $translator->translate('i.client'); ?></th>
                                <th><?= $translator->translate('i.options'); ?></th>
                            </tr>
                            </thead>

                            <tbody>
                            <?php
                                /**
                                 * @var App\Invoice\Entity\UserClient $userClient
                                 */
                                foreach ($ucR->repoClientquery($userInv->getUser_id()) as $userClient) { ?>
                                <tr>
                                    <td>
                                        <a href="<?= $urlGenerator->generate('client/view', ['id' => $userClient->getClient_id()]); ?>" style="text-decoration:none">
                                            <?php
                                                $client = $cR->repoClientquery($userClient->getClient_id());
                                    echo $client_helper->format_client($client);
                                    ?>
                                        </a>
                                    </td>
                                    <td>
                                        <form
                                            action="<?= $urlGenerator->generate('userclient/delete', ['id' => $userClient->getId()]); ?>"
                                            method="POST" enctype="multipart/form-data">
                                            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                            <button type="submit" class="btn btn-default btn-sm"
                                                    onclick="return confirm('<?= $translator->translate('i.delete_user_client_warning'); ?>');">
                                                <i class="fa fa-trash fa-margin"></i> <?= $translator->translate('i.remove'); ?>
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

</div>
