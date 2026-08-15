<?php

declare(strict_types=1);

namespace App\Workshop\UI\Http;

use App\Workshop\Domain\Enum\WorkshopCategory;
use App\Workshop\Domain\Enum\WorkshopLevel;
use App\Workshop\Domain\Enum\WorkshopMode;
use App\Workshop\Domain\Repository\WorkshopRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CatalogController extends AbstractController
{
    #[Route('/', name: 'home', methods: ['GET'])]
    public function home(WorkshopRepositoryInterface $workshops): Response
    {
        return $this->render('catalog/home.html.twig', ['workshops' => $workshops->searchPublished([], 6)]);
    }

    #[Route('/ateliers', name: 'workshop_catalog', methods: ['GET'])]
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
        ]);
    }

    #[Route('/ateliers/{slug}', name: 'workshop_show', methods: ['GET'])]
    public function show(string $slug, WorkshopRepositoryInterface $workshops): Response
    {
        $workshop = $workshops->findPublishedBySlug($slug);
        if (!$workshop) {
            throw $this->createNotFoundException('Atelier introuvable.');
        }

        return $this->render('catalog/show.html.twig', ['workshop' => $workshop]);
    }
}
