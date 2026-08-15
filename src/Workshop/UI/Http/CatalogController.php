<?php

declare(strict_types=1);

namespace App\Workshop\UI\Http;

use App\Shared\Domain\Enum\SupportedLocale;
use App\Workshop\Domain\Enum\WorkshopCategory;
use App\Workshop\Domain\Enum\WorkshopLevel;
use App\Workshop\Domain\Enum\WorkshopMode;
use App\Workshop\Domain\Repository\WorkshopRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CatalogController extends AbstractController
{
    #[Route(path: ['fr' => '/fr', 'en' => '/en'], name: 'home', methods: ['GET'])]
    public function home(WorkshopRepositoryInterface $workshops): Response
    {
        return $this->render('catalog/home.html.twig', [
            'workshops' => $workshops->searchPublished([], 6),
            'seo_localized' => true,
        ]);
    }

    #[Route(path: ['fr' => '/fr/ateliers', 'en' => '/en/workshops'], name: 'workshop_catalog', methods: ['GET'])]
    public function catalog(Request $request, WorkshopRepositoryInterface $workshops): Response
    {
        $filters = array_filter([
            'category' => $request->query->getString('category'),
            'level' => $request->query->getString('level'),
            'mode' => $request->query->getString('mode'),
            'date' => $request->query->getString('date'),
        ]);

        return $this->render('catalog/index.html.twig', [
            'workshops' => $workshops->searchPublished($filters),
            'filters' => $filters,
            'categories' => WorkshopCategory::cases(),
            'levels' => WorkshopLevel::cases(),
            'modes' => WorkshopMode::cases(),
            'seo_localized' => true,
        ]);
    }

    #[Route(path: ['fr' => '/fr/ateliers/{slug}', 'en' => '/en/workshops/{slug}'], name: 'workshop_show', methods: ['GET'])]
    public function show(string $slug, Request $request, WorkshopRepositoryInterface $workshops): Response
    {
        $locale = SupportedLocale::fromString($request->getLocale());
        $workshop = $workshops->findPublishedBySlug($locale, $slug);
        if (!$workshop) {
            $workshop = $workshops->findPublishedByAnySlug($slug);
            if ($workshop) {
                return $this->redirectToRoute('workshop_show', [...$request->query->all(),
                    '_locale' => $locale->value,
                    'slug' => $workshop->translation($locale)->getSlug(),
                ], RedirectResponse::HTTP_MOVED_PERMANENTLY);
            }

            throw $this->createNotFoundException('workshop.error.not_found');
        }

        return $this->render('catalog/show.html.twig', ['workshop' => $workshop, 'seo_localized' => true]);
    }
}
