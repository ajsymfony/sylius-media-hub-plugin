<?php

declare(strict_types=1);

namespace Ajay\SyliusMediaHubPlugin\Controller;

use Ajay\SyliusMediaHubPlugin\Application\Query\MediaHubCriteria;
use Ajay\SyliusMediaHubPlugin\Infrastructure\Repository\MediaHubReadRepositoryInterface;
use Pagerfanta\Pagerfanta;
use Sylius\Component\Grid\Parameters;
use Sylius\Component\Grid\Provider\GridProviderInterface;
use Sylius\Component\Grid\View\GridViewFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMINISTRATION_ACCESS')]
final class MediaHubController extends AbstractController
{
    /**
     * @param list<int> $allowedLimits
     */
    public function __construct(
        private readonly MediaHubReadRepositoryInterface $mediaHubReadRepository,
        private readonly GridProviderInterface $gridProvider,
        private readonly GridViewFactoryInterface $gridViewFactory,
        private readonly int $defaultLimit,
        private readonly array $allowedLimits,
        private readonly string $fallbackLocale,
    ) {
    }

    public function index(Request $request): Response
    {
        return $this->renderPage($request, MediaHubCriteria::SCOPE_ALL);
    }

    public function products(Request $request): Response
    {
        return $this->renderPage($request, MediaHubCriteria::SCOPE_PRODUCTS);
    }

    public function taxons(Request $request): Response
    {
        return $this->renderPage($request, MediaHubCriteria::SCOPE_TAXONS);
    }

    public function missing(Request $request): Response
    {
        return $this->renderPage($request, MediaHubCriteria::SCOPE_MISSING);
    }

    private function renderPage(Request $request, string $scope): Response
    {
        $criteria = MediaHubCriteria::fromRequest($request, $scope, $this->defaultLimit, $this->allowedLimits);
        $localeCode = $request->getLocale() ?: $this->fallbackLocale;

        $paginator = null;
        $missingGrid = null;
        if ($criteria->isMissingView()) {
            $grid = $this->gridProvider->get('ajay_sylius_media_hub_admin_missing');
            $missingGrid = $this->gridViewFactory->create($grid, new Parameters([
                'page' => $criteria->page,
                'limit' => $criteria->limit,
                'search' => $criteria->search,
                'sort' => $criteria->sort,
                'missingScope' => $criteria->missingScope,
            ]));
        } else {
            $paginator = $this->mediaHubReadRepository->paginateMedia($criteria, $localeCode);
        }

        return $this->render('@SyliusMediaHubPlugin/admin/media_hub/index.html.twig', [
            'criteria' => $criteria,
            'statistics' => $this->mediaHubReadRepository->getStatistics(),
            'paginator' => $paginator,
            'missingGrid' => $missingGrid,
            'allowed_limits' => $this->allowedLimits,
            'sort_options' => [
                MediaHubCriteria::SORT_NEWEST => 'ajay_sylius_media_hub.ui.sort.newest',
                MediaHubCriteria::SORT_OLDEST => 'ajay_sylius_media_hub.ui.sort.oldest',
                MediaHubCriteria::SORT_NAME_ASC => 'ajay_sylius_media_hub.ui.sort.name_asc',
                MediaHubCriteria::SORT_NAME_DESC => 'ajay_sylius_media_hub.ui.sort.name_desc',
            ],
        ]);
    }
}
