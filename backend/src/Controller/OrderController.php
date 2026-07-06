<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use App\Service\OrderService;
use App\Service\StripeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Gestion des commandes de l'utilisateur connecte.
 * Toutes les routes de ce controller necessitent une authentification JWT.
 * Un utilisateur ne peut voir que ses propres commandes (sauf ROLE_ADMIN).
 */
#[Route('/api/orders', name: 'api_orders_')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class OrderController extends AbstractController
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly OrderService    $orderService,
        private readonly StripeService   $stripeService,
    ) {}

    /**
     * Retourne toutes les commandes de l'utilisateur connecte.
     * Inclut les lignes et produits associes pour eviter des appels supplementaires.
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user   = $this->getUser();
        $orders = $this->orderRepository->findByUserWithItems($user);

        return $this->json(array_map([$this, 'serializeOrder'], $orders));
    }

    /**
     * Retourne le detail d'une commande specifique.
     * Un utilisateur standard ne peut acceder qu'a ses propres commandes.
     * Un administrateur peut acceder a toute commande.
     */
    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user  = $this->getUser();
        $order = $this->orderRepository->find($id);

        if (!$order) {
            return $this->json(['error' => 'Commande introuvable.'], 404);
        }

        // Verification d'appartenance : seul le proprietaire ou un admin peut voir cette commande
        if ($order->getUser()->getId() !== $user->getId() && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Acces refuse.'], 403);
        }

        return $this->json($this->serializeOrder($order));
    }

    /**
     * Cree une nouvelle commande depuis un panier JSON.
     * Le corps de la requete doit contenir :
     *   - items: [ { productId: int, quantity: int }, ... ]
     *   - shippingAddress: string
     *   - shippingMethod: string (optionnel, 'standard' par defaut)
     *   - paymentIntentId: string (optionnel, identifiant du paiement Stripe deja confirme)
     *
     * Le total (produits + frais de livraison) est calcule cote serveur, le stock
     * est verifie et decremente. Si un paymentIntentId est fourni, la commande est
     * directement enregistree comme payee.
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || empty($data['items']) || empty($data['shippingAddress'])) {
            return $this->json([
                'error' => 'Corps invalide. Attendu : { "items": [...], "shippingAddress": "..." }'
            ], 400);
        }

        /** @var \App\Entity\User $user */
        $user   = $this->getUser();
        $result = $this->orderService->createOrder(
            $user,
            $data['items'],
            $data['shippingAddress'],
            $data['shippingMethod'] ?? 'standard',
            $data['paymentIntentId'] ?? null,
        );

        if (isset($result['error'])) {
            return $this->json(['error' => $result['error']], 422);
        }

        return $this->json($this->serializeOrder($result['order']), 201);
    }

    /**
     * Crée une session de paiement Stripe pour une commande existante.
     * Retourne l'URL vers laquelle rediriger le client pour payer.
     */
    #[Route('/{id}/checkout', name: 'checkout', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function checkout(int $id): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user  = $this->getUser();
        $order = $this->orderRepository->find($id);

        if (!$order) {
            return $this->json(['error' => 'Commande introuvable.'], 404);
        }

        if ($order->getUser()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Acces refuse.'], 403);
        }

        if ($order->getStatus() !== 'pending') {
            return $this->json(['error' => 'Cette commande ne peut plus etre payee.'], 422);
        }

        try {
            $session = $this->stripeService->createCheckoutSession($order);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur Stripe : ' . $e->getMessage()], 500);
        }

        return $this->json([
            'checkoutUrl'       => $session->url,
            'stripeSessionId'   => $session->id,
        ]);
    }

    /** Transforme un Order en tableau JSON serialisable avec ses lignes */
    private function serializeOrder(\App\Entity\Order $order): array
    {
        $items = array_map(fn($item) => [
            'id'        => $item->getId(),
            'quantity'  => $item->getQuantity(),
            'unitPrice' => $item->getUnitPrice(),
            'subtotal'  => $item->getSubtotal(),
            'product'   => [
                'id'       => $item->getProduct()->getId(),
                'name'     => $item->getProduct()->getName(),
                'imageUrl' => $item->getProduct()->getImageUrl(),
            ],
        ], $order->getOrderItems()->toArray());

        return [
            'id'              => $order->getId(),
            'status'          => $order->getStatus(),
            'total'           => $order->getTotal(),
            'shippingAddress' => $order->getShippingAddress(),
            'shippingMethod'  => $order->getShippingMethod(),
            'shippingCost'    => $order->getShippingCost(),
            'paymentIntentId' => $order->getPaymentIntentId(),
            'createdAt'       => $order->getCreatedAt()?->format('c'),
            'items'           => $items,
        ];
    }
}

