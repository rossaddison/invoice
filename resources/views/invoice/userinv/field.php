<?php
declare(strict_types=1);

use App\Invoice\Helpers\ClientHelper;
use Yiisoft\Html\Html;

$client_helper = new ClientHelper($s);
/**
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Yii\View\Csrf $csrf 
 */

echo $alert;
?>
<div id="headerbar">
    <h1 class="headerbar-title"><?= $s->trans('assigned_clients'); ?></h1>

    <div class="headerbar-item pull-right">
        <div class="btn-group btn-group-sm">
            <a class="btn btn-default" href="<?= $urlGenerator->generate('userinv/index'); ?>">
                <i class="fa fa-arrow-left"></i> <?= $s->trans('back'); ?>
            </a>
            <a class="btn btn-primary" href="<?= $urlGenerator->generate('userclient/new',['user_id'=>$user_id]); ?>">
                <i class="fa fa-plus"></i> <?= $s->trans('new'); ?>
            </a>
        </div>
    </div>
</div>

<div id="content">
    <div class="row">
        <div class="col-xs-12 col-md-6 col-md-offset-3">

            <div class="panel panel-default">
                <div class="panel-heading">
                    <?= $s->trans('user') . ': ' . Html::encode($userinv->getName()); ?>
                </div>

                <div class="panel-body table-content">
                    <div class="table-responsive no-margin">
                        <table class="table table-hover table-striped no-margin">

                            <thead>
                            <tr>
                                <th><?= $s->trans('client'); ?></th>
                                <th><?= $s->trans('options'); ?></th>
                            </tr>
                            </thead>

                            <tbody>
                            <?php foreach ($user_clients as $user_client) { ?>
                                <tr>
                                    <td>
                                        <a href="<?= $urlGenerator->generate('client/view',['id'=> $user_client->getClient_id()]); ?>" style="text-decoration:none">
                                            <?php
                                                $client = $cR->repoClientquery((string)$user_client->getClient_id());
                                                echo $client_helper->format_client($client); 
                                            ?>
                                        </a>
                                    </td>
                                    <td>
                                        <form
                                            action="<?= $urlGenerator->generate('userclient/delete', ['id'=>$user_client->getId()]); ?>"
                                            method="POST" enctype="multipart/form-data">
                                            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                            <button type="submit" class="btn btn-default btn-sm"
                                                    onclick="return confirm('<?= $s->trans('delete_user_client_warning'); ?>');">
                                                <i class="fa fa-trash fa-margin"></i> <?= $s->trans('remove'); ?>
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
