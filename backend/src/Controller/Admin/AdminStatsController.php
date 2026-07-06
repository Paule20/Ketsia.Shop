<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Statistiques agregees pour le tableau de bord admin.
 */
#[Route('/api/admin/stats', name: 'api_admin_stats_')]
#[IsGranted('ROLE_ADMIN')]
class AdminStatsController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $now            = new \DateTimeImmutable('first day of this month 00:00:00');
        $lastMonthStart = $now->modify('-1 month');

        $orderRepo   = $this->entityManager->getRepository(Order::class);
        $userRepo    = $this->entityManager->getRepository(User::class);
        $productRepo = $this->entityManager->getRepository(Product::class);

        $ordersThisMonth = (int) $orderRepo->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.createdAt >= :start')
            ->setParameter('start', $now)
            ->getQuery()->getSingleScalarResult();

        $ordersLastMonth = (int) $orderRepo->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.createdAt >= :start AND o.createdAt < :end')
            ->setParameter('start', $lastMonthStart)
            ->setParameter('end', $now)
            ->getQuery()->getSingleScalarResult();

        $revenueThisMonth = (float) $orderRepo->createQueryBuilder('o')
            ->select('COALESCE(SUM(o.total), 0)')
            ->where('o.createdAt >= :start AND o.status != :cancelled')
            ->setParameter('start', $now)
            ->setParameter('cancelled', Order::STATUS_CANCELLED)
            ->getQuery()->getSingleScalarResult();

        $revenueLastMonth = (float) $orderRepo->createQueryBuilder('o')
            ->select('COALESCE(SUM(o.total), 0)')
            ->where('o.createdAt >= :start AND o.createdAt < :end AND o.status != :cancelled')
            ->setParameter('start', $lastMonthStart)
            ->setParameter('end', $now)
            ->setParameter('cancelled', Order::STATUS_CANCELLED)
            ->getQuery()->getSingleScalarResult();

        $activeUsers = (int) $userRepo->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->getQuery()->getSingleScalarResult();

        $newUsers = (int) $userRepo->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.createdAt >= :start')
            ->setParameter('start', $now)
            ->getQuery()->getSingleScalarResult();

        $totalStock = (int) $productRepo->createQueryBuilder('p')
            ->select('COALESCE(SUM(p.stock), 0)')
            ->getQuery()->getSingleScalarResult();

        $outOfStockCount = (int) $productRepo->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.stock = 0')
            ->getQuery()->getSingleScalarResult();

        return $this->json([
            'ordersThisMonth' => $ordersThisMonth,
            'ordersDelta'     => $this->formatDelta($ordersThisMonth, $ordersLastMonth),
            'revenue'         => number_format($revenueThisMonth, 2, '.', ''),
            'revenueDelta'    => $this->formatDelta($revenueThisMonth, $revenueLastMonth),
            'activeUsers'     => $activeUsers,
            'newUsers'        => $newUsers,
            'totalStock'      => $totalStock,
            'outOfStockCount' => $outOfStockCount,
        ]);
    }

    private function formatDelta(float $current, float $previous): string
    {
        if ($previous <= 0) {
            return $current > 0 ? 'Nouveau' : '0%';
        }
        return sprintf('%+.0f%%', (($current - $previous) / $previous) * 100);
    }
}