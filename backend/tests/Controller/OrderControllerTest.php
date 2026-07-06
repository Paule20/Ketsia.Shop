<?php

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests fonctionnels pour la creation de commandes (CP9).
 * Valide : cas nominal, stock insuffisant, utilisateur non authentifie.
 */
class OrderControllerTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        // Nettoyage complet avant chaque test
        $conn = $this->em->getConnection();
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        $conn->executeStatement('TRUNCATE TABLE order_item');
        $conn->executeStatement('TRUNCATE TABLE `order`');
        $conn->executeStatement('TRUNCATE TABLE product');
        $conn->executeStatement('TRUNCATE TABLE category');
        $conn->executeStatement('TRUNCATE TABLE `user`');
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=1');

        // Cree une categorie et un produit de test reutilisables
        $category = new Category();
        $category->setName('Test');
        $category->setSlug('test');
        $this->em->persist($category);

        $product = new Product();
        $product->setName('Produit Test');
        $product->setPrice('20.00');
        $product->setStock(5);
        $product->setCategory($category);
        $this->em->persist($product);

        $this->em->flush();
    }

    /** Recupere un token JWT pour un utilisateur donne (helper interne) */
    private function getToken(string $email, string $password): string
    {
        // Inscription
        $this->client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'     => $email,
            'password'  => $password,
            'firstName' => 'Test',
            'lastName'  => 'User',
        ]));

        // Connexion
        $this->client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => $email, 'password' => $password]));

        return json_decode($this->client->getResponse()->getContent(), true)['token'];
    }

    /** Recupere l'ID du premier produit en base */
    private function getProductId(): int
    {
        return $this->em->getRepository(Product::class)->findOneBy([])->getId();
    }

    // -------------------------------------------------------------------------
    // POST /api/orders
    // -------------------------------------------------------------------------

    /** Cas nominal : creation d'une commande valide par un utilisateur connecte */
    public function testCreateOrderSuccess(): void
    {
        $token     = $this->getToken('order@example.com', 'Password123!');
        $productId = $this->getProductId();

        $this->client->request('POST', '/api/orders', [], [], [
            'CONTENT_TYPE'      => 'application/json',
            'HTTP_AUTHORIZATION' => "Bearer $token",
        ], json_encode([
            'items'           => [['productId' => $productId, 'quantity' => 2]],
            'shippingAddress' => '1 rue de la Paix, Paris',
        ]));

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('pending', $data['status']);
        $this->assertEquals('40.00', $data['total']);
        $this->assertCount(1, $data['items']);

        // Verifie que le stock a ete decremente
        $this->em->clear();
        $product = $this->em->getRepository(Product::class)->find($productId);
        $this->assertEquals(3, $product->getStock());
    }

    /** Stock insuffisant doit retourner 422 avec un message explicite */
    public function testCreateOrderInsufficientStock(): void
    {
        $token     = $this->getToken('stock@example.com', 'Password123!');
        $productId = $this->getProductId();

        $this->client->request('POST', '/api/orders', [], [], [
            'CONTENT_TYPE'      => 'application/json',
            'HTTP_AUTHORIZATION' => "Bearer $token",
        ], json_encode([
            'items'           => [['productId' => $productId, 'quantity' => 100]],
            'shippingAddress' => '1 rue Test',
        ]));

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('Stock insuffisant', $data['error']);
    }

    /** Un utilisateur non authentifie doit recevoir un 401 */
    public function testCreateOrderUnauthenticated(): void
    {
        $this->client->request('POST', '/api/orders', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'items'           => [['productId' => 1, 'quantity' => 1]],
            'shippingAddress' => '1 rue Test',
        ]));

        $this->assertResponseStatusCodeSame(401);
    }
}
