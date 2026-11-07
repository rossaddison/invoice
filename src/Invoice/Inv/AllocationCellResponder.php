<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\Inv\Widget\InvAllocationCell;
use App\Invoice\Inv\Widget\InvCrewToolbar;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Csrf\CsrfTokenInterface;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Answers the allocation forms' htmx posts on inv/guest. The lead allocates
 * houses in split seconds, so a tap must not reload the page: htmx sends the
 * same POST, and instead of the redirect the controller returns just that
 * invoice's freshly rendered allocation cell, which htmx swaps in place. A
 * request without the HX-Request header (no JavaScript, or htmx failed to
 * load) still gets the ordinary redirect from the controller.
 */
final readonly class AllocationCellResponder
{
    public function __construct(
        private ResponseFactoryInterface $responses,
        private InvGuestHomeCare $homeCare,
        private UrlGeneratorInterface $urlGenerator,
        private TranslatorInterface $translator,
        private CsrfTokenInterface $csrfToken,
    ) {
    }

    public function wants(ServerRequestInterface $request): bool
    {
        return 'true' === $request->getHeaderLine('HX-Request');
    }

    /**
     * The invoice's allocation cell as the viewer sees it now.
     */
    public function cell(Inv $inv, int $viewerWorkerId, bool $admin = false): ResponseInterface
    {
        return $this->html($this->build($viewerWorkerId, $admin)->render($inv));
    }

    /**
     * The cell for the admin's inv/index grid: every control, whoever holds the side.
     */
    public function adminCell(): InvAllocationCell
    {
        return $this->build(0, true);
    }

    /**
     * The admin's "today's crew" form for inv/index (the lead's own is on inv/guest).
     */
    public function adminCrewToolbar(): string
    {
        return new InvCrewToolbar(
            $this->urlGenerator,
            $this->translator,
            $this->csrfToken->getValue(),
            $this->homeCare->workerNames(),
            $this->homeCare->crewIds(),
            $this->homeCare->crewFrontBack(),
            true,
        )->render();
    }

    private function build(int $viewerWorkerId, bool $admin): InvAllocationCell
    {
        return new InvAllocationCell(
            $this->homeCare->allocations,
            $this->urlGenerator,
            $this->translator,
            $this->csrfToken->getValue(),
            $viewerWorkerId,
            $this->homeCare->leadWorkerId(),
            $this->homeCare->workerNames(),
            $this->homeCare->crewIds(),
            $this->homeCare->crewFrontBack(),
            $admin,
        );
    }

    /**
     * No content: htmx swaps nothing and the page script then refreshes the grid
     * (the crew form changes every row's chips, not one cell).
     */
    public function nothing(): ResponseInterface
    {
        return $this->responses->createResponse(204);
    }

    private function html(string $html): ResponseInterface
    {
        $response = $this->responses->createResponse(200)->withHeader('Content-Type', 'text/html; charset=utf-8');
        $response->getBody()->write($html);
        return $response;
    }
}
