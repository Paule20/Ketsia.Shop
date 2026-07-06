<?php

namespace App\Service;

use App\Entity\Order;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class StripeService
{
    public function __construct(
        private readonly string $stripeSecretKey,
        private readonly string $frontendUrl,
    ) {
        Stripe::setApiKey($this->stripeSecretKey);
    }

    /**
     * Crée une Checkout Session Stripe pour une commande donnée.
     * Retourne l'URL de paiement vers laquelle rediriger le client.
     */
    public function createCheckoutSession(Order $order): Session
    {
        $lineItems = [];

        foreach ($order->getOrderItems() as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency'     => 'eur',
                    'unit_amount'  => (int) round((float) $item->getUnitPrice() * 100),
                    'product_data' => [
                        'name' => $item->getProduct()->getName(),
                    ],
                ],
                'quantity' => $item->getQuantity(),
            ];
        }

        return Session::create([
            'payment_method_types' => ['card'],
            'line_items'           => $lineItems,
            'mode'                 => 'payment',
            'success_url'          => $this->frontendUrl . '/order/' . $order->getId() . '/success',
            'cancel_url'           => $this->frontendUrl . '/order/' . $order->getId() . '/cancel',
            'metadata'             => ['order_id' => $order->getId()],
        ]);
    }
}
