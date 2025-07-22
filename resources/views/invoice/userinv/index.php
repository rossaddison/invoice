<?php

declare(strict_types=1);

use App\Invoice\Entity\UserInv;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H4;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Html\Tag\Td;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Yii\DataView\YiiRouter\UrlCreator;

/**
 * @var App\Invoice\Client\ClientRepository $cR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Invoice\UserClient\UserClientRepository $ucR
 * @var App\Widget\GridComponents $gridComponents
 * @var App\Widget\PageSizeLimiter $pageSizeLimiter
 * @var string $active
 * @var string $alert
 * @var string $csrf
 * @var bool $canEdit
 * @psalm-var positive-int $page
 * @var Yiisoft\Data\Cycle\Reader\EntityReader $userinvs
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Rbac\Manager $manager
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataFilterUserInvLoginDropDown
 */

echo $alert

?>
<?php
$header = Div::tag()
    ->addClass('row')
    ->content(
        H5::tag()
            ->addClass('bg-primary text-white p-3 rounded-top')
            ->content(
                I::tag()->addClass('bi bi-people')
                        ->content(' ' . Html::encode($translator->translate('users'))),
            ),
    )
    ->render();

$toolbarReset = A::tag()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'userinv/index'))
    ->id('btn-reset')
    ->render();

$toolbar = Div::tag();
?>
<br>
<div>
<?php
    echo A::tag()->content(H4::tag()->content($translator->translate('client.has.not.assigned')))->href($urlGenerator->generate('client/index'))->render();
echo '<table class="table table-responsive">';
echo '<thead>';
echo '<tr><th scope="row">' . $translator->translate('client.name') . ' ' .
                             $translator->translate('client.surname') .
     '</th><th scope="row">' . $translator->translate('phone') . '</th>' .
     '<th scope="row">' . $translator->translate('email.address') . '</th></tr>';
echo '</thead>';
echo '<tbody>';
?> 
<?php
    $unAssignedClientIds = $ucR->get_not_assigned_to_any_user($cR);
foreach ($unAssignedClientIds as $clientId) {
    echo '<tr>';
    $client = $cR->repoClientquery((string) $clientId);
    echo Td::tag()
    ->content($client->getClient_full_name())
    ->render();
    echo Td::tag()
    ->content($client->getClient_phone() ?? '')
    ->render();
    echo Td::tag()
    ->content($client->getClient_email())
    ->render();
    echo '</tr>';
}
echo '</tbody>';
echo '</table>';
echo '<br>'
?>    
    
</div>    
<div>
    <h5><?= $translator->translate('users'); ?></h5>
    <div class="btn-group index-options">
        <a href="<?= $urlGenerator->generate('userinv/index', ['page' => 1, 'active' => 2]); ?>"
           class="btn <?php echo $active == 2 ? 'btn-primary' : 'btn-default' ?>">
            <?= $translator->translate('all'); ?>
        </a>
        <a href="<?= $urlGenerator->generate('userinv/index', ['page' => 1, 'active' => 1]); ?>" style="text-decoration:none"
           class="btn  <?php echo $active == 1 ? 'btn-primary' : 'btn-default' ?>">
            <?= $translator->translate('active'); ?>
        </a>
        <a href="<?= $urlGenerator->generate('userinv/index', ['page' => 1, 'active' => 0]); ?>" style="text-decoration:none"
           class="btn  <?php echo $active == 0 ? 'btn-primary' : 'btn-default' ?>">
            <?= $translator->translate('inactive'); ?>
        </a>
        <?=
        Html::a(
            Html::tag('i', '', [
                'class' => 'fa fa-plus',
            ]),
            $urlGenerator->generate('userinv/add'),
            ['class' => 'btn btn-sm btn-primary'],
        )->render();
?>
    </div>
</div>
<br>
<div id="content" class="table-content">  
<div class="card shadow">
<?php
    $columns = [
        new DataColumn(
            'active',
            content: static function (UserInv $model) use ($translator): string {
                return $model->getActive() ? '✔️' : '❌';
            },
        ),
        new DataColumn(
            'all_clients',
            header: $translator->translate('user.all.clients'),
            content: static function (UserInv $model) use ($translator): string {
                return $model->getAll_clients() ? '✔️' : '❌';
            },
        ),
        new DataColumn(
            field: 'user_id',
            header: $translator->translate('gridview.login'),
            property: 'filterUser',
            content: static function (UserInv $model) use ($urlGenerator): string|A {
                $user = $model->getUser();
                if (null !== $user) {
                    if (!empty($user->getLogin())) {
                        return Html::a($user->getLogin(), $urlGenerator->generate('user/profile', ['login' => $user->getLogin()]), []);
                    }
                }
                return '';
            },
            filter: $optionsDataFilterUserInvLoginDropDown,
            withSorting: false,
        ),
        new DataColumn(
            'name',
            content: static function (UserInv $model): string {
                return (string) $model->getName();
            },
        ),
        new DataColumn(
            'type',
            header: $translator->translate('user.type'),
            content: static function (UserInv $model) use ($translator): string {
                $user_types = [
                    0 => '🧑‍⚖️',
                    1 => '🧑',
                ];
                // default is 'guest' which is an invoiceplane setting as denoted by the use of 'i.' and incorporates all users besides the administrator
                return $user_types[$model->getType() ?? 1];
            },
        ),
        new DataColumn(
            'user_id',
            header: $translator->translate('user.inv.role.observer'),
            content: static function (UserInv $model) use ($manager, $translator, $urlGenerator): string|Yiisoft\Html\Tag\CustomTag|A {
                if ($manager->getPermissionsByUserId($model->getUser_id())
                  === $manager->getPermissionsByRoleName('observer')) {
                    return Html::tag('span', $translator->translate('general.yes'), ['class' => 'label active']);
                } else {
                    return $model->getUser_id() !== '1' ? Html::a(
                        Html::tag(
                            'button',
                            Html::tag('span', $translator->translate('general.no'), ['class' => 'label inactive']),
                            [
                                'type' => 'submit',
                                'class' => 'dropdown-button',
                                'onclick' => "return confirm(" . "'" . $translator->translate('user.inv.role.warning.role') . "');",
                            ],
                        ),
                        $urlGenerator->generate('userinv/observer', ['user_id' => $model->getUser_id()], []),
                    ) : '';
                }
            },
        ),
        new DataColumn(
            'user_id',
            header: $translator->translate('user.inv.role.accountant'),
            content: static function (UserInv $model) use ($manager, $translator, $urlGenerator): Yiisoft\Html\Tag\CustomTag|A|string {
                if ($manager->getPermissionsByUserId($model->getUser_id())
                  === $manager->getPermissionsByRoleName('accountant')) {
                    return Html::tag('span', $translator->translate('general.yes'), ['class' => 'label active'])->render();
                } else {
                    return $model->getUser_id() !== '1' ? Html::a(
                        Html::tag(
                            'button',
                            Html::tag('span', $translator->translate('general.no'), ['class' => 'label inactive']),
                            [
                                'type' => 'submit',
                                'class' => 'dropdown-button',
                                'onclick' => "return confirm(" . "'" . $translator->translate('user.inv.role.warning.role') . "');",
                            ],
                        ),
                        $urlGenerator->generate('userinv/accountant', ['user_id' => $model->getUser_id()], []),
                    ) : '';
                }
            },
        ),
        new DataColumn(
            'user_id',
            header: $translator->translate('user.inv.role.administrator'),
            content: static function (UserInv $model) use ($manager, $translator, $urlGenerator): Yiisoft\Html\Tag\CustomTag|A|string {
                if ($manager->getPermissionsByUserId($model->getUser_id())
                  === $manager->getPermissionsByRoleName('admin')) {
                    return Html::tag('span', $translator->translate('general.yes'), ['class' => 'label active']);
                } else {
                    if (!$model->getUser_id() == '1') {
                        return Html::a(
                            Html::tag(
                                'button',
                                Html::tag('span', $translator->translate('general.no'), ['class' => 'label inactive']),
                                [
                                    'type' => 'submit',
                                    'class' => 'dropdown-button',
                                    'onclick' => "return confirm(" . "'" . $translator->translate('user.inv.role.warning.role') . "');",
                                ],
                            ),
                            $urlGenerator->generate('userinv/admin', ['user_id' => $model->getUser_id()], []),
                        );
                    } // not id == 1 => use AssignRole console command to assign the admin role
                    return '';
                } // else
            },
        ),

        new DataColumn(
            'user_id',
            header: $translator->translate('user.inv.role.revoke.all'),
            content: static function (UserInv $model) use ($manager, $translator, $urlGenerator): A|string {
                if (!empty($manager->getPermissionsByUserId($model->getUser_id())) && $model->getUser_id() !== '1') {
                    return Html::a(
                        Html::tag(
                            'button',
                            Html::tag('span', $translator->translate('user.inv.role.revoke.all'), ['class' => 'label inactive']),
                            [
                                'type' => 'submit',
                                'class' => 'dropdown-button',
                                'onclick' => "return confirm(" . "'" . $translator->translate('user.inv.role.warning.revoke.all') . "');",
                            ],
                        ),
                        $urlGenerator->generate('userinv/revoke', ['user_id' => $model->getUser_id()], []),
                    );
                } else {
                    return '';
                }
            },
        ),
        new DataColumn(
            'user',
            content: static function (UserInv $model): string {
                return (string) $model->getUser()?->getEmail();
            },
        ),
        new DataColumn(
            'type',
            header: $translator->translate('assigned.clients'),
            content: static function (UserInv $model) use ($ucR, $urlGenerator): A {
                return Html::a(
                    Html::tag(
                        'i',
                        str_repeat(' ', 1) . (string) count($ucR->get_assigned_to_user($model->getUser_id())),
                        ['class' => 'fa fa-list fa-margin'],
                    ),
                    $urlGenerator->generate('userinv/client', ['id' => $model->getId()]),
                    ['class' => count($ucR->get_assigned_to_user($model->getUser_id())) > 0
                            ? 'btn btn-success'
                            : 'btn btn-danger'],
                );
            },
        ),
        new DataColumn(
            'type',
            header: '🖉',
            content: static function (UserInv $model) use ($urlGenerator, $canEdit): string|A {
                return $canEdit ? Html::a(
                    '🖉',
                    $urlGenerator->generate('userinv/edit', ['id' => $model->getId()]),
                    ['style' => 'text-decoration:none'],
                ) : '';
            },
        ),
        new DataColumn(
            'type',
            header: '❌',
            content: static function (UserInv $model) use ($translator, $urlGenerator): string|A {
                return $model->getType() == 1 ? Html::a(
                    Html::tag(
                        'button',
                        '❌',
                        [
                            'type' => 'submit',
                            'class' => 'dropdown-button',
                            'onclick' => "return confirm(" . "'" . $translator->translate('delete.record.warning') . "');",
                        ],
                    ),
                    $urlGenerator->generate('userinv/delete', ['id' => $model->getId()]),
                    ['style' => 'text-decoration:none'],
                ) : '';
            },
        ),
    ];
?>
    <?php

$paginator = (new OffsetPaginator($userinvs))
    ->withPageSize($s->positiveListLimit())
    ->withCurrentPage($page)
    ->withToken(PageToken::next((string) $page));

$grid_summary = $s->grid_summary(
    $paginator,
    $translator,
    (int) $s->getSetting('default_list_limit'),
    $translator->translate('user.accounts'),
    '',
);
$toolbarString = Form::tag()->post($urlGenerator->generate('userinv/index'))->csrf($csrf)->open() .
        Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
        Form::tag()->close();
/**
 * Related logic: see vendor\yiisoft\yii-dataview\src\GridView.php for the sequence of functions which can effect rendering
 */
echo GridView::widget()
->bodyRowAttributes(['class' => 'align-middle'])
->tableAttributes(['class' => 'table table-striped text-center h-75','id' => 'table-user-inv'])
->columns(...$columns)
->dataReader($paginator)
->urlCreator(new UrlCreator($urlGenerator))
->multiSort(true)
->header($header)
->headerRowAttributes(['class' => 'card-header bg-info text-black'])
->id('w5-grid')
->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
->summaryTemplate($pageSizeLimiter::buttons($currentRoute, $s, $translator, $urlGenerator, 'userinv') . ' ' . $grid_summary)
->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
->emptyCell($translator->translate('no.records'), ['class' => 'card-header bg-warning text-black'])
->toolbar($toolbarString);
?> 
</div>
</div>