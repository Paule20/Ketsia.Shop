<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/products', name: 'api_products_')]
class ProductController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository      $productRepository,
        private readonly CategoryRepository     $categoryRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface     $validator,
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $products = $this->productRepository->findWithFilters(
            $request->query->get('category'),
            $request->query->get('subCategory'),
            $request->query->get('minPrice') !== null ? (float) $request->query->get('minPrice') : null,
            $request->query->get('maxPrice') !== null ? (float) $request->query->get('maxPrice') : null,
        );

        return $this->json(array_map([$this, 'serializeProduct'], $products));
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        $product = $this->productRepository->find($id);
        if (!$product) {
            return $this->json(['error' => 'Produit introuvable.'], 404);
        }

        return $this->json($this->serializeProduct($product));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['error' => 'JSON invalide.'], 400);
        }

        $result = $this->buildProduct(new Product(), $data);
        if (isset($result['errors'])) {
            return $this->json(['errors' => $result['errors']], 422);
        }

        $this->entityManager->persist($result['product']);
        $this->entityManager->flush();

        return $this->json($this->serializeProduct($result['product']), 201);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(int $id, Request $request): JsonResponse
    {
        $product = $this->productRepository->find($id);
        if (!$product) {
            return $this->json(['error' => 'Produit introuvable.'], 404);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['error' => 'JSON invalide.'], 400);
        }

        $result = $this->buildProduct($product, $data);
        if (isset($result['errors'])) {
            return $this->json(['errors' => $result['errors']], 422);
        }

        $this->entityManager->flush();

        return $this->json($this->serializeProduct($result['product']));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id): JsonResponse
    {
        $product = $this->productRepository->find($id);
        if (!$product) {
            return $this->json(['error' => 'Produit introuvable.'], 404);
        }

        $this->entityManager->remove($product);
        $this->entityManager->flush();

        return $this->json(null, 204);
    }

    private function buildProduct(Product $product, array $data): array
    {
        if (isset($data['name'])) {
            $product->setName(trim($data['name']));
        }
        if (isset($data['description'])) {
            $product->setDescription($data['description']);
        }
        if (isset($data['price'])) {
            $product->setPrice((string) $data['price']);
        }
        if (isset($data['stock'])) {
            $product->setStock((int) $data['stock']);
        }
        if (isset($data['subCategory'])) {
            $product->setSubCategory($data['subCategory']);
        }
        if (isset($data['imageUrl'])) {
            $product->setImageUrl($data['imageUrl']);
        }
        if (isset($data['sizes'])) {
            $product->setSizes((array) $data['sizes']);
        }
        if (isset($data['categoryId'])) {
            $category = $this->categoryRepository->find((int) $data['categoryId']);
            if (!$category) {
                return ['errors' => ['categoryId' => 'Categorie introuvable.']];
            }
            $product->setCategory($category);
        }

        $errors = $this->validator->validate($product);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return ['errors' => $errorMessages];
        }

        return ['product' => $product];
    }

    private function serializeProduct(Product $product): array
    {
        return [
            'id'          => $product->getId(),
            'name'        => $product->getName(),
            'description' => $product->getDescription(),
            'price'       => $product->getPrice(),
            'stock'       => $product->getStock(),
            'subCategory' => $product->getSubCategory(),
            'imageUrl'    => $product->getImageUrl(),
            'sizes'       => $product->getSizes(),
            'createdAt'   => $product->getCreatedAt()?->format('c'),
            'category'    => $product->getCategory() ? [
                'id'   => $product->getCategory()->getId(),
                'name' => $product->getCategory()->getName(),
                'slug' => $product->getCategory()->getSlug(),
            ] : null,
        ];
    }
}