<?php

namespace App\Controller;

use App\Entity\Wishlist;
use App\Repository\ProductRepository;
use App\Repository\WishlistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/wishlist', name: 'api_wishlist_')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class WishlistController extends AbstractController
{
    public function __construct(
        private readonly WishlistRepository      $wishlistRepository,
        private readonly ProductRepository       $productRepository,
        private readonly EntityManagerInterface  $entityManager,
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user  = $this->getUser();
        $items = $this->wishlistRepository->findBy(['user' => $user], ['addedAt' => 'DESC']);

        return $this->json(array_map([$this, 'serializeItem'], $items));
    }

    #[Route('', name: 'add', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['productId'])) {
            return $this->json(['error' => 'productId requis.'], 400);
        }

        $product = $this->productRepository->find((int) $data['productId']);
        if (!$product) {
            return $this->json(['error' => 'Produit introuvable.'], 404);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $existing = $this->wishlistRepository->findOneBy(['user' => $user, 'product' => $product]);
        if ($existing) {
            return $this->json(['error' => 'Produit deja dans la wishlist.'], 409);
        }

        $item = new Wishlist();
        $item->setUser($user);
        $item->setProduct($product);

        $this->entityManager->persist($item);
        $this->entityManager->flush();

        return $this->json($this->serializeItem($item), 201);
    }

    #[Route('/{productId}', name: 'remove', methods: ['DELETE'], requirements: ['productId' => '\d+'])]
    public function remove(int $productId): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user    = $this->getUser();
        $product = $this->productRepository->find($productId);

        if (!$product) {
            return $this->json(['error' => 'Produit introuvable.'], 404);
        }

        $item = $this->wishlistRepository->findOneBy(['user' => $user, 'product' => $product]);
        if (!$item) {
            return $this->json(['error' => 'Produit absent de la wishlist.'], 404);
        }

        $this->entityManager->remove($item);
        $this->entityManager->flush();

        return $this->json(null, 204);
    }

    private function serializeItem(Wishlist $item): array
    {
        $product = $item->getProduct();

        return [
            'id'       => $item->getId(),
            'addedAt'  => $item->getAddedAt()?->format('c'),
            'product'  => [
                'id'          => $product->getId(),
                'name'        => $product->getName(),
                'price'       => $product->getPrice(),
                'imageUrl'    => $product->getImageUrl(),
                'subCategory' => $product->getSubCategory(),
                'sizes'       => $product->getSizes(),
                'category'    => $product->getCategory() ? [
                    'id'   => $product->getCategory()->getId(),
                    'name' => $product->getCategory()->getName(),
                    'slug' => $product->getCategory()->getSlug(),
                ] : null,
            ],
        ];
    }
}
