<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Cree les PaymentIntent Stripe necessaires au paiement par carte (Stripe Elements).
 */
#[Route('/api/stripe', name: 'api_stripe_')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class StripeController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        #[Autowire(env: 'STRIPE_SECRET_KEY')] private readonly string $stripeSecretKey,
    ) {
        Stripe::setApiKey($this->stripeSecretKey);
    }

    /**
     * Cree un PaymentIntent a partir du panier.
     * Le montant est recalcule cote serveur depuis les prix reels en base
     * (on ne fait jamais confiance a un total envoye par le client).
     */
    #[Route('/create-payment-intent', name: 'create_payment_intent', methods: ['POST'])]
    public function createPaymentIntent(Request $request): JsonResponse
    {
        $data  = json_decode($request->getContent(), true);
        $items = $data['items'] ?? [];

        if (empty($items)) {
            return $this->json(['error' => 'Panier vide.'], 400);
        }

        $total = 0.0;
        foreach ($items as $item) {
            $product = $this->productRepository->find((int) ($item['productId'] ?? 0));
            if (!$product) {
                return $this->json(['error' => 'Produit introuvable dans le panier.'], 400);
            }
            $total += (float) $product->getPrice() * max(1, (int) ($item['quantity'] ?? 1));
        }

        if (($data['shippingMethod'] ?? 'standard') === 'express') {
            $total += 4.99;
        }

        if ($total <= 0) {
            return $this->json(['error' => 'Montant invalide.'], 400);
        }

        $paymentIntent = PaymentIntent::create([
            'amount'                    => (int) round($total * 100), // Stripe attend des centimes
            'currency'                  => 'eur',
            'automatic_payment_methods' => ['enabled' => true],
        ]);

        return $this->json([
            'clientSecret' => $paymentIntent->client_secret,
            'amount'       => $total,
        ]);
    }
}