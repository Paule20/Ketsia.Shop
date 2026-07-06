<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\User;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Gere la logique metier de creation des commandes.
 * Centralise la verification du stock, le calcul du total
 * et le decrement du stock apres validation.
 * Separer cette logique du Controller permet de la tester de facon isolee.
 */
class OrderService
{
    /** Frais de livraison express, en euros (livraison standard = gratuite) */
    private const EXPRESS_SHIPPING_COST = 4.99;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProductRepository      $productRepository,
    ) {}

    /**
     * Cree une commande depuis un tableau de lignes (productId + quantity).
     * Le total est TOUJOURS calcule cote serveur a partir des prix en base
     * (+ frais de livraison selon le mode choisi). Le stock de chaque
     * produit est decremente atomiquement.
     *
     * @param User        $user            Utilisateur authentifie passant la commande
     * @param array       $items           Tableau de ['productId' => int, 'quantity' => int]
     * @param string      $shippingAddress Adresse de livraison saisie par l'utilisateur
     * @param string      $shippingMethod  'standard' ou 'express'
     * @param string|null $paymentIntentId Identifiant du PaymentIntent Stripe deja confirme
     *                                     (le paiement a lieu cote frontend AVANT l'appel a
     *                                     ce service ; on enregistre ici la commande correspondante
     *                                     et on la marque directement comme payee)
     * @return array{order: Order}|array{error: string} Commande creee ou message d'erreur
     */
    public function createOrder(
        User $user,
        array $items,
        string $shippingAddress,
        string $shippingMethod = 'standard',
        ?string $paymentIntentId = null,
    ): array {
        if (empty($items)) {
            return ['error' => 'Le panier est vide.'];
        }

        $order = new Order();
        $order->setUser($user);
        $order->setShippingAddress($shippingAddress);
        $order->setShippingMethod($shippingMethod);

        $total = '0.00';

        foreach ($items as $itemData) {
            $productId = (int) ($itemData['productId'] ?? 0);
            $quantity  = (int) ($itemData['quantity']  ?? 0);

            if ($quantity < 1) {
                return ['error' => "La quantite pour le produit {$productId} doit etre au moins 1."];
            }

            $product = $this->productRepository->find($productId);
            if (!$product) {
                return ['error' => "Produit {$productId} introuvable."];
            }

            // Verification du stock disponible avant de l'engager
            if ($product->getStock() < $quantity) {
                return [
                    'error' => "Stock insuffisant pour \"{$product->getName()}\" "
                        . "(disponible : {$product->getStock()}, demande : {$quantity})."
                ];
            }

            // Decrement du stock — effectif lors du flush final
            $product->setStock($product->getStock() - $quantity);

            // Capture du prix actuel : si le prix change demain, l'historique reste correct
            $orderItem = new OrderItem();
            $orderItem->setProduct($product);
            $orderItem->setQuantity($quantity);
            $orderItem->setUnitPrice($product->getPrice());

            $order->addOrderItem($orderItem);

            // Calcul du total avec des operations sur les chaines pour eviter les erreurs de virgule flottante
            $total = number_format(
                (float) $total + ((float) $product->getPrice() * $quantity),
                2,
                '.',
                ''
            );
        }

        // Ajout des frais de livraison au total, et memorisation du montant applique
        $shippingCost = $shippingMethod === 'express' ? self::EXPRESS_SHIPPING_COST : 0.0;
        $order->setShippingCost(number_format($shippingCost, 2, '.', ''));
        $total = number_format((float) $total + $shippingCost, 2, '.', '');

        $order->setTotal($total);

        // Le paiement Stripe a deja ete confirme cote frontend avant cet appel :
        // la commande est donc directement enregistree comme payee, avec une trace
        // du PaymentIntent pour pouvoir la retrouver depuis le dashboard Stripe.
        if ($paymentIntentId) {
            $order->setPaymentIntentId($paymentIntentId);
            $order->setStatus(Order::STATUS_PAID);
        }

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return ['order' => $order];
    }
}

