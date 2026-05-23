<?php

declare(strict_types=1);

namespace App\User\Widget;

use App\Infrastructure\Persistence\User\User;
use App\Invoice\Setting\SettingRepository as SR;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Widget\Widget;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView\GridView;
use Yiisoft\Yii\DataView\Pagination\OffsetPagination;
use Yiisoft\Yii\DataView\Pagination\PaginationWidgetInterface;
use Yiisoft\Yii\DataView\YiiRouter\UrlCreator;
use Yiisoft\Yii\DataView\YiiRouter\UrlParameterProvider;

final class UsersListWidget extends Widget
{
    private const string DOM_ID = 'UsersGridView';

    private ?OffsetPaginator $paginator = null;
    private ?SR $sR = null;

    public function __construct(
        private readonly CurrentRoute $currentRoute,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly TranslatorInterface $translator,
    ) {}

    public function withPaginator(OffsetPaginator $paginator): static
    {
        $new = clone $this;
        $new->paginator = $paginator;
        return $new;
    }

    public function withSR(SR $sR): static
    {
        $new = clone $this;
        $new->sR = $sR;
        return $new;
    }

    #[\Override]
    public function render(): string
    {
        if ($this->paginator === null) {
            return '';
        }

        $htmxAttrs = [
            'hx-indicator'   => '#' . self::DOM_ID,
            'hx-target'      => '#' . self::DOM_ID,
            'hx-replace-url' => 'true',
            'hx-swap'        => 'outerHTML',
        ];

        /** @var PaginationWidgetInterface<\Yiisoft\Data\Paginator\PaginatorInterface> */
        $pagination = OffsetPagination::widget()->addLinkAttributes([
            'hx-boost' => 'true',
            ...$htmxAttrs,
        ]);

        $gridView = GridView::widget()
            ->containerAttributes(['id' => self::DOM_ID, 'class' => 'mt-4 position-relative'])
            ->dataReader($this->paginator)
            ->urlParameterProvider(new UrlParameterProvider($this->currentRoute))
            ->urlCreator(new UrlCreator($this->urlGenerator))
            ->sortableLinkAttributes(['hx-boost' => 'true', ...$htmxAttrs])
            ->filterFormAttributes(['hx-boost' => 'true', ...$htmxAttrs])
            ->paginationWidget($pagination)
            ->columns(
                new DataColumn(
                    'id',
                    content: static fn(User $data): string => (string) $data->reqId(),
                ),
                new DataColumn(
                    'login',
                    content: fn(User $data): string => $data->getLogin(),
                    header: $this->translator->translate('gridview.login'),
                ),
                new DataColumn(
                    'create_at',
                    content: fn(User $data): string => $data->getCreatedAt()->format('r'),
                    header: $this->translator->translate('gridview.create.at'),
                ),
                new DataColumn(
                    'api',
                    content: fn(User $data): A =>
                        Html::a(
                            'API User Data',
                            $this->urlGenerator->generate('api/user/profile', ['login' => $data->getLogin()]),
                            ['target' => '_blank'],
                        ),
                    header: $this->translator->translate('gridview.api'),
                ),
                new DataColumn(
                    'profile',
                    content: fn(User $data): A =>
                        Html::a(
                            Html::tag('i', '', ['class' => 'bi bi-person-fill ms-1', 'style' => 'font-size: 1.5em;']),
                            $this->urlGenerator->generate('user/profile', ['login' => $data->getLogin()]),
                            ['class' => 'btn btn-link'],
                        ),
                    header: $this->translator->translate('gridview.profile'),
                ),
            );


        return $gridView->render();
    }
}
