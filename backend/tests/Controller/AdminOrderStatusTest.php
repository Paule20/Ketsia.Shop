<?php

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Tests fonctionnels pour la modification du statut d'une commande côté admin (CP9).
 * Complète AdminControllerTest (qui teste déjà l'accès en lecture à /api/admin/orders).
 */
class AdminOrderStatusTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $em;
    private string $userToken  = '';
    private string $adminToken = '';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em     = static::getContainer()->get(EntityManagerInterface::class);
        $hasher       = static::getContainer()->get(UserPasswordHasherInterface::class);

        $conn = $this->em->getConnection();
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        $conn->executeStatement('TRUNCATE TABLE order_item');
        $conn->executeStatement('TRUNCATE TABLE `order`');
        $conn->executeStatement('TRUNCATE TABLE product');
        $conn->executeStatement('TRUNCATE TABLE category');
        $conn->executeStatement('TRUNCATE TABLE `user`');
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=1');

        $user = new User();
        $user->setEmail('user_status_test@example.com');
        $user->setFirstName('Regular');
        $user->setLastName('User');
        $user->setPassword($hasher->hashPassword($user, 'Password123!'));
        $this->em->persist($user);

        $admin = new User();
        $admin->setEmail('admin_status_test@example.com');
        $admin->setFirstName('Admin');
        $admin->setLastName('User');
        $admin->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        $admin->setPassword($hasher->hashPassword($admin, 'Admin123!'));
        $this->em->persist($admin);

        $this->em->flush();

        $this->userToken  = $this->login('user_status_test@example.com', 'Password123!');
        $this->adminToken = $this->login('admin_status_test@example.com', 'Admin123!');
    }

    private function login(string $email, string $password): string
    {
        $this->client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => $email, 'password' => $password]));

        return json_decode($this->client->getResponse()->getContent(), true)['token'] ?? '';
    }

    /** Crée une commande de test directement en base, pour l'utilisateur "standard" créé en setUp */
    private function createOrder(string $status = 'pending'): int
    {
        $category = new Category();
        $category->setName('Femme');
        $category->setSlug('femme-' . uniqid());
        $this->em->persist($category);

        $product = new Product();
        $product->setName('Produit Commande Test');
        $product->setPrice('25.00');
        $product->setStock(10);
        $product->setCategory($category);
        $this->em->persist($product);

        $user = $this->em->getRepository(User::class)->findOneBy(['email' => 'user_status_test@example.com']);

        $order = new Order();
        $order->setUser($user);
        $order->setStatus($status);
        $order->setTotal('25.00');
        $order->setShippingAddress('1 rue de Test');
        $this->em->persist($order);
        $this->em->flush();

        return $order->getId();
    }

    // -------------------------------------------------------------------------
    // PATCH /api/admin/orders/{id}/status
    // -------------------------------------------------------------------------

    /** Un admin peut faire passer une commande de "pending" à "paid" */
    public function testUpdateOrderStatusSuccessForAdmin(): void
    {
        $orderId = $this->createOrder('pending');

        $this->client->request('PATCH', "/api/admin/orders/$orderId/status", [], [], [
            'CONTENT_TYPE'       => 'application/json',
            'HTTP_AUTHORIZATION' => "Bearer {$this->adminToken}",
        ], json_encode(['status' => 'paid']));

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('paid', $data['status']);

        // Vérifie la persistance réelle en base
        $this->em->clear();
        $order = $this->em->getRepository(Order::class)->find($orderId);
        $this->assertEquals('paid', $order->getStatus());
    }

    /** Un statut qui ne fait pas partie de VALID_STATUSES doit être rejeté (422) */
    public function testUpdateOrderStatusWithInvalidValueReturns422(): void
    {
        $orderId = $this->createOrder('pending');

        $this->client->request('PATCH', "/api/admin/orders/$orderId/status", [], [], [
            'CONTENT_TYPE'       => 'application/json',
            'HTTP_AUTHORIZATION' => "Bearer {$this->adminToken}",
        ], json_encode(['status' => 'statut_qui_nexiste_pas']));

        $this->assertResponseStatusCodeSame(422);

        // Le statut original ne doit pas avoir changé
        $this->em->clear();
        $order = $this->em->getRepository(Order::class)->find($orderId);
        $this->assertEquals('pending', $order->getStatus());
    }

    /** Modifier le statut d'une commande inexistante retourne 404 */
    public function testUpdateOrderStatusNotFound(): void
    {
        $this->client->request('PATCH', '/api/admin/orders/999999/status', [], [], [
            'CONTENT_TYPE'       => 'application/json',
            'HTTP_AUTHORIZATION' => "Bearer {$this->adminToken}",
        ], json_encode(['status' => 'paid']));

        $this->assertResponseStatusCodeSame(404);
    }

    /** Un utilisateur standard ne peut pas changer le statut d'une commande */
    public function testUpdateOrderStatusForbiddenForRegularUser(): void
    {
        $orderId = $this->createOrder('pending');

        $this->client->request('PATCH', "/api/admin/orders/$orderId/status", [], [], [
            'CONTENT_TYPE'       => 'application/json',
            'HTTP_AUTHORIZATION' => "Bearer {$this->userToken}",
        ], json_encode(['status' => 'paid']));

        $this->assertResponseStatusCodeSame(403);
    }

    /** Sans authentification, la route retourne 401 */
    public function testUpdateOrderStatusUnauthenticated(): void
    {
        $orderId = $this->createOrder('pending');

        $this->client->request('PATCH', "/api/admin/orders/$orderId/status", [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['status' => 'paid']));

        $this->assertResponseStatusCodeSame(401);
    }
}
