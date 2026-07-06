<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/products', name: 'admin_products_')]
#[IsGranted('ROLE_ADMIN')]
class AdminProductController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ProductRepository $productRepository,
        private CategoryRepository $categoryRepository,
        private SerializerInterface $serializer
    ) {}

    // GET /api/admin/products — liste complète (y compris hors stock)
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $products = $this->productRepository->findAll();
        $json = $this->serializer->serialize($products, 'json', ['groups' => 'product:read']);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    // GET /api/admin/products/{id} — détail d'un produit
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $product = $this->productRepository->find($id);
        if (!$product) {
            return new JsonResponse(['error' => 'Produit introuvable'], Response::HTTP_NOT_FOUND);
        }

        $json = $this->serializer->serialize($product, 'json', ['groups' => 'product:read']);
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    // POST /api/admin/products — création
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $product = new Product();
        $product->setName($data['name'] ?? '');
        $product->setDescription($data['description'] ?? '');
        $product->setPrice($data['price'] ?? 0);
        $product->setStock($data['stock'] ?? 0);

        if (!empty($data['categoryId'])) {
            $category = $this->categoryRepository->find($data['categoryId']);
            if ($category) {
                $product->setCategory($category);
            }
        }

        $this->em->persist($product);
        $this->em->flush();

        $json = $this->serializer->serialize($product, 'json', ['groups' => 'product:read']);
        return new JsonResponse($json, Response::HTTP_CREATED, [], true);
    }

    // PUT /api/admin/products/{id} — modification
    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $product = $this->productRepository->find($id);
        if (!$product) {
            return new JsonResponse(['error' => 'Produit introuvable'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) $product->setName($data['name']);
        if (isset($data['description'])) $product->setDescription($data['description']);
        if (isset($data['price'])) $product->setPrice($data['price']);
        if (isset($data['stock'])) $product->setStock($data['stock']);
        if (!empty($data['categoryId'])) {
            $category = $this->categoryRepository->find($data['categoryId']);
            if ($category) $product->setCategory($category);
        }

        $this->em->flush();

        $json = $this->serializer->serialize($product, 'json', ['groups' => 'product:read']);
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    // DELETE /api/admin/products/{id} — suppression
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $product = $this->productRepository->find($id);
        if (!$product) {
            return new JsonResponse(['error' => 'Produit introuvable'], Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($product);
        $this->em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}