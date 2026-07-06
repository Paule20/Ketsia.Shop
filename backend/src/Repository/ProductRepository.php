<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour l'entité Product.
 * Contient la logique de filtrage pour le catalogue public.
 *
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * Recherche des produits avec filtres optionnels.
     * Tous les paramètres sont optionnels — si aucun filtre, retourne tout le catalogue.
     *
     * @param string|null $categorySlug  Filtre par slug de catégorie (ex: "femme")
     * @param string|null $subCategory   Filtre par sous-catégorie (ex: "Robes")
     * @param float|null  $minPrice      Prix minimum
     * @param float|null  $maxPrice      Prix maximum
     * @return Product[]
     */
    public function findWithFilters(
        ?string $categorySlug = null,
        ?string $subCategory = null,
        ?float $minPrice = null,
        ?float $maxPrice = null
    ): array {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->addSelect('c');

        if ($categorySlug !== null) {
            $qb->andWhere('c.slug = :slug')
               ->setParameter('slug', $categorySlug);
        }

        if ($subCategory !== null) {
            $qb->andWhere('p.subCategory = :subCategory')
               ->setParameter('subCategory', $subCategory);
        }

        if ($minPrice !== null) {
            $qb->andWhere('p.price >= :minPrice')
               ->setParameter('minPrice', $minPrice);
        }

        if ($maxPrice !== null) {
            $qb->andWhere('p.price <= :maxPrice')
               ->setParameter('maxPrice', $maxPrice);
        }

        return $qb->orderBy('p.createdAt', 'DESC')->getQuery()->getResult();
    }
}
