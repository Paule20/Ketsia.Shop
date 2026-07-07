<?php

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
use App\Entity\Wishlist;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Tests fonctionnels pour WishlistController (CP9).
 * Valide : authentification requise, ajout/suppression, doublons, 404, isolation entre comptes.
 */
class WishlistControllerTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $em;
    private string $tokenA = '';
    private string $tokenB = '';
    private int $productId;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em     = static::getContainer()->get(EntityManagerInterface::class);
        $hasher       = static::getContainer()->get(UserPasswordHasherInterface::class);

        $conn = $this->em->getConnection();
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        $conn->executeStatement('TRUNCATE TABLE wishlist');
        $conn->executeStatement('TRUNCATE TABLE order_item');
        $conn->executeStatement('TRUNCATE TABLE `order`');
        $conn->executeStatement('TRUNCATE TABLE product');
        $conn->executeStatement('TRUNCATE TABLE category');
        $conn->executeStatement('TRUNCATE TABLE `user`');
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=1');

        // Deux comptes distincts, pour verifier l'isolation des wishlists
        $userA = new User();
        $userA->setEmail('wishlist_a@example.com');
        $userA->setFirstName('User');
        $userA->setLastName('A');
        $userA->setPassword($hasher->hashPassword($userA, 'Password123!'));
        $this->em->persist($userA);

        $userB = new User();
        $userB->setEmail('wishlist_b@example.com');
        $userB->setFirstName('User');
        $userB->setLastName('B');
        $userB->setPassword($hasher->hashPassword($userB, 'Password123!'));
        $this->em->persist($userB);

        $category = new Category();
        $category->setName('Femme');
        $category->setSlug('femme');
        $this->em->persist($category);

        $product = new Product();
        $product->setName('Robe Wishlist Test');
        $product->setPrice('39.99');
        $product->setStock(10);
        $product->setCategory($category);
        $this->em->persist($product);

        $this->em->flush();
        $this->productId = $product->getId();

        $this->tokenA = $this->login('wishlist_a@example.com', 'Password123!');
        $this->tokenB = $this->login('wishlist_b@example.com', 'Password123!');
    }

    private function login(string $email, string $password): string
    {
        $this->client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => $email, 'password' => $password]));

        return json_decode($this->client->getResponse()->getContent(), true)['token'] ?? '';
    }

    // -------------------------------------------------------------------------
    // GET /api/wishlist
    // -------------------------------------------------------------------------

    /** Sans authentification, la route retourne 401 */
    public function testListUnauthenticated(): void
    {
        $this->client->request('GET', '/api/wishlist');
        $this->assertResponseStatusCodeSame(401);
    }

    /** Une wishlist vide retourne un tableau vide, pas une erreur */
    public function testListEmptyWishlist(): void
    {
        $this->client->request('GET', '/api/wishlist', [], [], [
            'HTTP_AUTHORIZATION' => "Bearer {$this->tokenA}",
        ]);

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals([], $data);
    }

    // -------------------------------------------------------------------------
    // POST /api/wishlist
    // -------------------------------------------------------------------------

    /** Ajout reussi : la reponse contient le produit serialise avec sa categorie */
    public function testAddToWishlistSuccess(): void
    {
        $this->client->request('POST', '/api/wishlist', [], [], [
            'CONTENT_TYPE'       => 'application/json',
            'HTTP_AUTHORIZATION' => "Bearer {$this->tokenA}",
        ], json_encode(['productId' => $this->productId]));

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Robe Wishlist Test', $data['product']['name']);
        $this->assertEquals('Femme', $data['product']['category']['name']);
    }

    /** productId manquant retourne 400 */
    public function testAddToWishlistMissingProductId(): void
    {
        $this->client->request('POST', '/api/wishlist', [], [], [
            'CONTENT_TYPE'       => 'application/json',
            'HTTP_AUTHORIZATION' => "Bearer {$this->tokenA}",
        ], json_encode([]));

        $this->assertResponseStatusCodeSame(400);
    }

    /** Un produit inexistant retourne 404 */
    public function testAddToWishlistProductNotFound(): void
    {
        $this->client->request('POST', '/api/wishlist', [], [], [
            'CONTENT_TYPE'       => 'application/json',
            'HTTP_AUTHORIZATION' => "Bearer {$this->tokenA}",
        ], json_encode(['productId' => 999999]));

        $this->assertResponseStatusCodeSame(404);
    }

    /** Ajouter deux fois le meme produit retourne 409 (conflit) */
    public function testAddToWishlistDuplicateReturns409(): void
    {
        $this->client->request('POST', '/api/wishlist', [], [], [
            'CONTENT_TYPE'       => 'application/json',
            'HTTP_AUTHORIZATION' => "Bearer {$this->tokenA}",
        ], json_encode(['productId' => $this->productId]));
        $this->assertResponseStatusCodeSame(201);

        $this->client->request('POST', '/api/wishlist', [], [], [
            'CONTENT_TYPE'       => 'application/json',
            'HTTP_AUTHORIZATION' => "Bearer {$this->tokenA}",
        ], json_encode(['productId' => $this->productId]));
        $this->assertResponseStatusCodeSame(409);
    }

    // -------------------------------------------------------------------------
    // DELETE /api/wishlist/{productId}
    // -------------------------------------------------------------------------

    /** Suppression reussie : le produit disparait bien de la wishlist en base */
    public function testRemoveFromWishlistSuccess(): void
    {
        $this->client->request('POST', '/api/wishlist', [], [], [
            'CONTENT_TYPE'       => 'application/json',
            'HTTP_AUTHORIZATION' => "Bearer {$this->tokenA}",
        ], json_encode(['productId' => $this->productId]));
        $this->assertResponseStatusCodeSame(201);

        $this->client->request('DELETE', "/api/wishlist/{$this->productId}", [], [], [
            'HTTP_AUTHORIZATION' => "Bearer {$this->tokenA}",
        ]);
        $this->assertResponseStatusCodeSame(204);

        $this->em->clear();
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => 'wishlist_a@example.com']);
        $remaining = $this->em->getRepository(Wishlist::class)->findBy(['user' => $user]);
        $this->assertCount(0, $remaining);
    }

    /** Retirer un produit qui n'est pas dans la wishlist retourne 404 */
    public function testRemoveFromWishlistNotInList(): void
    {
        $this->client->request('DELETE', "/api/wishlist/{$this->productId}", [], [], [
            'HTTP_AUTHORIZATION' => "Bearer {$this->tokenA}",
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    /** Retirer un produit inexistant retourne 404 */
    public function testRemoveFromWishlistProductNotFound(): void
    {
        $this->client->request('DELETE', '/api/wishlist/999999', [], [], [
            'HTTP_AUTHORIZATION' => "Bearer {$this->tokenA}",
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    // -------------------------------------------------------------------------
    // Isolation entre comptes
    // -------------------------------------------------------------------------

    /** La wishlist d'un compte n'apparaît jamais chez un autre compte */
    public function testWishlistIsolatedBetweenUsers(): void
    {
        // User A ajoute le produit
        $this->client->request('POST', '/api/wishlist', [], [], [
            'CONTENT_TYPE'       => 'application/json',
            'HTTP_AUTHORIZATION' => "Bearer {$this->tokenA}",
        ], json_encode(['productId' => $this->productId]));
        $this->assertResponseStatusCodeSame(201);

        // User B consulte SA wishlist : doit rester vide
        $this->client->request('GET', '/api/wishlist', [], [], [
            'HTTP_AUTHORIZATION' => "Bearer {$this->tokenB}",
        ]);
        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals([], $data);

        // User B ne peut pas non plus retirer un produit qu'il n'a jamais ajouté
        $this->client->request('DELETE', "/api/wishlist/{$this->productId}", [], [], [
            'HTTP_AUTHORIZATION' => "Bearer {$this->tokenB}",
        ]);
        $this->assertResponseStatusCodeSame(404);
    }
}
