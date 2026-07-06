<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Expose la liste des categories de produits.
 * Route publique : aucune authentification requise.
 */
#[Route('/api/categories', name: 'api_categories_')]
class CategoryController extends AbstractController
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
    ) {}

    /**
     * Retourne toutes les categories disponibles.
     * Utilisee par le frontend pour construire le menu de navigation.
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $categories = $this->categoryRepository->findAll();

        $data = array_map(fn($cat) => [
            'id'   => $cat->getId(),
            'name' => $cat->getName(),
            'slug' => $cat->getSlug(),
        ], $categories);

        return $this->json($data);
    }
}
