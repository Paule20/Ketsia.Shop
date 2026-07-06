<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Routes d'administration pour la gestion des commandes.
 * Toutes les routes de ce controller necessitent ROLE_ADMIN.
 * Le controle d'acces est double : via security.yaml (access_control)
 * et via l'attribut #[IsGranted] sur le controller (defense en profondeur).
 */
#[Route('/api/admin/orders', name: 'api_admin_orders_')]
#[IsGranted('ROLE_ADMIN')]
class AdminOrderController extends AbstractController
{
    public function __construct(
        private readonly OrderRepository        $orderRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    /**
     * Liste toutes les commandes de tous les utilisateurs avec leur detail.
     * Vue globale pour le back-office.
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $orders = $this->orderRepository->findAllWithDetails();

        return $this->json(array_map([$this, 'serializeOrder'], $orders));
    }

    /**
     * Modifie le statut d'une commande.
     * Valeurs acceptees : pending, paid, shipped, delivered, cancelled.
     * Corps attendu : { "status": "shipped" }
     */
    #[Route('/{id}/status', name: 'update_status', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    public function updateStatus(int $id, Request $request): JsonResponse
    {
        $order = $this->orderRepository->find($id);
        if (!$order) {
            return $this->json(['error' => 'Commande introuvable.'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $newStatus = $data['status'] ?? null;

        if (!$newStatus || !in_array($newStatus, Order::VALID_STATUSES, true)) {
            return $this->json([
                'error'          => 'Statut invalide.',
                'valeurs_valides' => Order::VALID_STATUSES,
            ], 422);
        }

        $order->setStatus($newStatus);
        $this->entityManager->flush();

        return $this->json($this->serializeOrder($order));
    }

    /** Serialise une commande avec les informations utilisateur pour le back-office */
    private function serializeOrder(Order $order): array
    {
        $items = array_map(fn($item) => [
            'id'        => $item->getId(),
            'quantity'  => $item->getQuantity(),
            'unitPrice' => $item->getUnitPrice(),
            'product'   => [
                'id'   => $item->getProduct()->getId(),
                'name' => $item->getProduct()->getName(),
                'imageUrl' => $item->getProduct()->getImageUrl(),
            ],
        ], $order->getOrderItems()->toArray());

        return [
            'id'              => $order->getId(),
            'status'          => $order->getStatus(),
            'total'           => $order->getTotal(),
            'shippingAddress' => $order->getShippingAddress(),
            'createdAt'       => $order->getCreatedAt()?->format('c'),
            'user'            => [
                'id'        => $order->getUser()->getId(),
                'email'     => $order->getUser()->getEmail(),
                'firstName' => $order->getUser()->getFirstName(),
                'lastName'  => $order->getUser()->getLastName(),
            ],
            'items'           => $items,
        ];
    }
}
