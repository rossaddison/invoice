<?php

declare(strict_types=1);

use App\Invoice\Entity\Client;
use App\Invoice\Entity\UserInv;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Reader\Iterable\IterableDataReader;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Br;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Html\Tag\Span;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView\GridView;
use Yiisoft\Yii\DataView\YiiRouter\UrlCreator;

/**
 * @var App\Invoice\Client\ClientRepository $cR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Invoice\UserClient\UserClientRepository $ucR
 * @var App\Widget\Button $button
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
 * @psalm-var array<
 *     array-key,
 *     array<array-key, string>|string
 * > $optionsDataFilterUserInvLoginDropDown
 */

echo $s->getSetting('disable_flash_messages') == '0'
    ? $alert
    : '';

$toolbarReset = new A()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content(new I()->addClass('bi bi-bootstrap-reboot'))
    ->href(
        $urlGenerator->generate(
            $currentRoute->getName() ?? 'userinv/index'
        )
    )
    ->id('btn-reset')
    ->render();

$textDecorationNone = 'text-decoration:none';
$unAssignedClientIds = $ucR->getNotAssignedToAnyUser($cR);

/** @var Client[] $unAssignedClients */
$unAssignedClients = array_values(array_filter(
    array_map(
        fn (int|null $id): ?Client =>
            $id !== null
                ? $cR->repoClientquery((string) $id)
                : null,
        $unAssignedClientIds
    ),
    fn (?Client $c): bool => $c !== null,
));

$unAssignedClientReader = new IterableDataReader($unAssignedClients);

$clientColumns = [
    new DataColumn(
        header: $translator->translate('client.name')
            . ' '
            . $translator->translate('client.surname'),
        content: static function (Client $model): string {
            return $model->getClientFullName();
        },
        withSorting: false,
    ),
    new DataColumn(
        header: $translator->translate('phone'),
        content: static function (Client $model): string {
            return $model->getClientPhone() ?? '';
        },
        withSorting: false,
    ),
    new DataColumn(
        header: $translator->translate('email.address'),
        content: static function (Client $model): string {
            return $model->getClientEmail();
        },
        withSorting: false,
    ),
    new DataColumn(
        header: $translator->translate('client.has.user.account'),
        content: static function (
            Client $model
        ) use (
            $canEdit,
            $ucR,
            $button,
            $translator,
            $urlGenerator
        ): Span {
            return (
                $ucR->repoUserqueryCount(
                    (string) $model->getClientId()
                ) !== 0 && $canEdit
            )
                ? $button::activeLabel($translator)
                : $button::inactiveWithAddUserAccount(
                    $urlGenerator,
                    $translator
                );
        },
        encodeContent: false,
        withSorting: false,
    ),
];

echo GridView::widget()
    ->bodyRowAttributes(['class' => 'align-middle'])
    ->tableAttributes([
        'class' => 'table table-striped text-center h-75',
        'id' => 'table-unassigned-clients',
    ])
    ->columns(...$clientColumns)
    ->dataReader($unAssignedClientReader)
    ->header($translator->translate('client.has.not.assigned'))
    ->headerRowAttributes([
        'class' => 'card-header bg-warning text-black',
    ])
    ->id('w6-grid')
    ->emptyCell(
        $translator->translate('no.records'),
        ['class' => 'card-header bg-warning text-black']
    )
    ->summaryTemplate('');
?>
<?= Html::openTag('div'); ?>
    <?= new H5()
        ->content($translator->translate('users'))
        ->render(); ?>
    <?= Html::openTag('div', [
        'class' => 'btn-group index-options',
    ]); ?>
        <?= new A()
            ->href($urlGenerator->generate(
                'userinv/index',
                ['page' => 1, 'active' => 2]
            ))
            ->addClass(
                'btn ' . ($active == 2 ? 'btn-primary' : 'btn-default')
            )
            ->addAttributes(['style' => $textDecorationNone])
            ->content($translator->translate('all'))
            ->render(); ?>
        <?= new A()
            ->href($urlGenerator->generate(
                'userinv/index',
                ['page' => 1, 'active' => 1]
            ))
            ->addClass(
                'btn ' . ($active == 1 ? 'btn-primary' : 'btn-default')
            )
            ->addAttributes(['style' => $textDecorationNone])
            ->content($translator->translate('active'))
            ->render(); ?>
        <?= new A()
            ->href($urlGenerator->generate(
                'userinv/index',
                ['page' => 1, 'active' => 0]
            ))
            ->addClass(
                'btn ' . ($active == 0 ? 'btn-primary' : 'btn-default')
            )
            ->addAttributes(['style' => $textDecorationNone])
            ->content($translator->translate('inactive'))
            ->render(); ?>
        <?= Html::a(
            Html::tag('i', '', ['class' => 'bi bi-plus-lg']),
            $urlGenerator->generate('userinv/add'),
            ['class' => 'btn btn-sm btn-primary'],
        )->render(); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= new Br(); ?>
<?= Html::openTag('div', [
    'id' => 'content',
    'class' => 'table-content',
]); ?>
<?= Html::openTag('div', ['class' => 'card-shadow']); ?>
<?php
$columns = [
    new DataColumn(
        'active',
        content: static function (UserInv $model): string {
            return $model->getActive() ? '✔️' : '❌';
        },
    ),
    new DataColumn(
        'all_clients',
        header: $translator->translate('user.all.clients'),
        content: static function (UserInv $model): string {
            return $model->getAllClients() ? '✔️' : '❌';
        },
    ),
    new DataColumn(
        header: $translator->translate('gridview.login'),
        property: 'filterUser',
        content: static function (
            UserInv $model
        ) use ($urlGenerator): string|A {
            $user = $model->getUser();
            if (null !== $user) {
                if (!empty($user->getLogin())) {
                    return Html::a(
                        $user->getLogin(),
                        $urlGenerator->generate(
                            'user/profile',
                            ['login' => $user->getLogin()]
                        ),
                        []
                    );
                }
            }
            return '';
        },
        encodeContent: false,
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
        content: static function (UserInv $model): string {
            $user_types = [
                0 => '‍⚖️',
                1 => '',
            ];
            return $user_types[$model->getType() ?? 1];
        },
    ),
    new DataColumn(
        'user_id',
        header: $translator->translate('user.inv.role.observer'),
        content: static function (
            UserInv $model
        ) use (
            $manager,
            $translator,
            $urlGenerator
        ): string|Yiisoft\Html\Tag\CustomTag|A {
            if (
                $manager->getPermissionsByUserId($model->getUserId())
                === $manager->getPermissionsByRoleName('observer')
            ) {
                return Html::tag(
                    'span',
                    $translator->translate('general.yes'),
                    [
                     'class' => 'badge text-bg-success',
                     'data-bs-toggle' => 'tooltip',
                     'title' => $translator->translate('')
                    ]
                );
            } else {
                return $model->getUserId() !== '1'
                    ? Html::a(
                        Html::tag(
                            'button',
                            Html::tag(
                                'span',
                                $translator->translate('general.no'),
                                ['class' => 'badge text-bg-secondary']
                            ),
                            [
                                'type' => 'submit',
                                'class' => 'dropdown-button',
                                'onclick' => "return confirm("
                                    . "'"
                                    . $translator->translate(
                                        'user.inv.role.warning.role'
                                    )
                                    . "');",
                            ],
                        ),
                        $urlGenerator->generate(
                            'userinv/observer',
                            ['user_id' => $model->getUserId()]
                        ),
                    )
                    : '';
            }
        },
        encodeContent: false,
    ),
    new DataColumn(
        'user_id',
        header: $translator->translate('user.inv.role.accountant'),
        content: static function (
            UserInv $model
        ) use (
            $manager,
            $translator,
            $urlGenerator
        ): Yiisoft\Html\Tag\CustomTag|A|string {
            if (
                $manager->getPermissionsByUserId($model->getUserId())
                === $manager->getPermissionsByRoleName('accountant')
            ) {
                return Html::tag(
                    'span',
                    $translator->translate('general.yes'),
                    ['class' => 'badge text-bg-success']
                )->render();
            } else {
                return $model->getUserId() !== '1'
                    ? Html::a(
                        Html::tag(
                            'button',
                            Html::tag(
                                'span',
                                $translator->translate('general.no'),
                                ['class' => 'badge text-bg-secondary']
                            ),
                            [
                                'type' => 'submit',
                                'class' => 'dropdown-button',
                                'onclick' => "return confirm("
                                    . "'"
                                    . $translator->translate(
                                        'user.inv.role.warning.role'
                                    )
                                    . "');",
                            ],
                        ),
                        $urlGenerator->generate(
                            'userinv/accountant',
                            ['user_id' => $model->getUserId()]
                        ),
                    )
                    : '';
            }
        },
        encodeContent: false,
    ),
    new DataColumn(
        'user_id',
        header: $translator->translate('user.inv.role.administrator'),
        content: static function (
            UserInv $model
        ) use (
            $manager,
            $translator,
            $urlGenerator
        ): Yiisoft\Html\Tag\CustomTag|A|string {
            if (
                $manager->getPermissionsByUserId($model->getUserId())
                === $manager->getPermissionsByRoleName('admin')
            ) {
                return Html::tag(
                    'span',
                    $translator->translate('general.yes'),
                    ['class' => 'badge text-bg-success']
                );
            } else {
                if (!$model->getUserId() == '1') {
                    return Html::a(
                        Html::tag(
                            'button',
                            Html::tag(
                                'span',
                                $translator->translate('general.no'),
                                ['class' => 'badge text-bg-secondary']
                            ),
                            [
                                'type' => 'submit',
                                'class' => 'dropdown-button',
                                'onclick' => "return confirm("
                                    . "'"
                                    . $translator->translate(
                                        'user.inv.role.warning.role'
                                    )
                                    . "');",
                            ],
                        ),
                        $urlGenerator->generate(
                            'userinv/admin',
                            ['user_id' => $model->getUserId()]
                        ),
                    );
                }
                return '';
            }
        },
        encodeContent: false,
    ),
    new DataColumn(
        'user_id',
        header: $translator->translate('user.inv.role.revoke.all'),
        content: static function (
            UserInv $model
        ) use (
            $manager,
            $translator,
            $urlGenerator
        ): A|string {
            if (
                !empty($manager->getPermissionsByUserId(
                    $model->getUserId()
                ))
                && $model->getUserId() !== '1'
            ) {
                return Html::a(
                    Html::tag(
                        'button',
                        Html::tag(
                            'span',
                            $translator->translate(
                                'user.inv.role.revoke.all'
                            ),
                            ['class' => 'badge text-bg-danger']
                        ),
                        [
                            'type' => 'submit',
                            'class' => 'dropdown-button',
                            'onclick' => "return confirm("
                                . "'"
                                . $translator->translate(
                                    'user.inv.role.warning.revoke.all'
                                )
                                . "');",
                        ],
                    ),
                    $urlGenerator->generate(
                        'userinv/revoke',
                        ['user_id' => $model->getUserId()]
                    ),
                );
            } else {
                return '';
            }
        },
        encodeContent: false,
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
        content: static function (
            UserInv $model
        ) use ($ucR, $urlGenerator): A {
            $assignedCount = count(
                $ucR->getAssignedToUser($model->getUserId())
            );
            return Html::a(
                Html::tag(
                    'i',
                    str_repeat(' ', 1) . (string) $assignedCount,
                    ['class' => 'bi bi-list-ul'],
                ),
                $urlGenerator->generate(
                    'userinv/client',
                    ['id' => $model->getId()]
                ),
                [
                    'class' => $assignedCount > 0
                        ? 'btn btn-success'
                        : 'btn btn-danger',
                ],
            );
        },
        encodeContent: false,
    ),
    new DataColumn(
        'type',
        header: '🖉',
        content: static function (
            UserInv $model
        ) use (
            $urlGenerator,
            $canEdit,
            $textDecorationNone
        ): string|A {
            return $canEdit
                ? Html::a(
                    '🖉',
                    $urlGenerator->generate(
                        'userinv/edit',
                        ['id' => $model->getId()]
                    ),
                    ['style' => $textDecorationNone],
                )
                : '';
        },
        encodeContent: false,
    ),
    new DataColumn(
        'type',
        header: '❌',
        content: static function (
            UserInv $model
        ) use (
            $translator,
            $urlGenerator,
            $textDecorationNone
        ): string|A {
            return $model->getType() == 1
                ? Html::a(
                    Html::tag(
                        'button',
                        '❌',
                        [
                            'type' => 'submit',
                            'class' => 'dropdown-button',
                            'onclick' => "return confirm("
                                . "'"
                                . $translator->translate(
                                    'delete.record.warning'
                                )
                                . "');",
                        ],
                    ),
                    $urlGenerator->generate(
                        'userinv/delete',
                        ['id' => $model->getId()]
                    ),
                    ['style' => $textDecorationNone],
                )
                : '';
        },
        encodeContent: false,
    ),
];

$paginator = (new OffsetPaginator($userinvs))
    ->withPageSize($s->positiveListLimit())
    ->withCurrentPage($page)
    ->withToken(PageToken::next((string) $page));

$gridSummary = $s->gridSummary(
    $paginator,
    $translator,
    (int) $s->getSetting('default_list_limit'),
    $translator->translate('user.accounts'),
    '',
);

$toolbarString = new Form()
        ->post($urlGenerator->generate('userinv/index'))
        ->csrf($csrf)
        ->open()
    . new Div()
        ->addClass('float-end m-3')
        ->content($toolbarReset)
        ->encode(false)
        ->render()
    . new Form()->close();

/**
 * Related logic: see
 * vendor\yiisoft\yii-dataview\src\GridView.php
 * for the sequence of functions which can effect rendering
 */
echo GridView::widget()
    ->bodyRowAttributes(['class' => 'align-middle'])
    ->tableAttributes([
        'class' => 'table table-striped text-center h-75',
        'id' => 'table-user-inv',
    ])
    ->columns(...$columns)
    ->dataReader($paginator)
    ->urlCreator(new UrlCreator($urlGenerator))
    ->multiSort(true)
    ->header($translator->translate('users'))
    ->headerRowAttributes([
        'class' => 'card-header bg-info text-black',
    ])
    ->id('w5-grid')
    ->summaryTemplate(
        $pageSizeLimiter::buttons(
            $currentRoute,
            $s,
            $translator,
            $urlGenerator,
            'userinv'
        ) . ' ' . $gridSummary
    )
    ->summaryAttributes([
        'class' => 'mt-3 me-3 summary text-end',
    ])
    ->emptyCell(
        $translator->translate('no.records'),
        ['class' => 'card-header bg-warning text-black']
    )
    ->toolbar($toolbarString);
    