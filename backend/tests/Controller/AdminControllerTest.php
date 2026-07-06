<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Tests fonctionnels pour les routes admin (CP9).
 * Valide que les routes /api/admin/* sont inaccessibles aux utilisateurs non-admin.
 */
class AdminControllerTest extends WebTestCase
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

        // Nettoyage
        $conn = $this->em->getConnection();
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        $conn->executeStatement('TRUNCATE TABLE order_item');
        $conn->executeStatement('TRUNCATE TABLE `order`');
        $conn->executeStatement('TRUNCATE TABLE product');
        $conn->executeStatement('TRUNCATE TABLE category');
        $conn->executeStatement('TRUNCATE TABLE `user`');
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=1');

        // Cree un utilisateur standard
        $user = new User();
        $user->setEmail('user_admin_test@example.com');
        $user->setFirstName('Regular');
        $user->setLastName('User');
        $user->setPassword($hasher->hashPassword($user, 'Password123!'));
        $this->em->persist($user);

        // Cree un administrateur
        $admin = new User();
        $admin->setEmail('admin_test@example.com');
        $admin->setFirstName('Admin');
        $admin->setLastName('User');
        $admin->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        $admin->setPassword($hasher->hashPassword($admin, 'Admin123!'));
        $this->em->persist($admin);

        $this->em->flush();

        // Obtient les tokens
        $this->userToken  = $this->login('user_admin_test@example.com', 'Password123!');
        $this->adminToken = $this->login('admin_test@example.com', 'Admin123!');
    }

    /** Helper : connexion et recuperation du token */
    private function login(string $email, string $password): string
    {
        $this->client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => $email, 'password' => $password]));

        return json_decode($this->client->getResponse()->getContent(), true)['token'] ?? '';
    }

    // -------------------------------------------------------------------------
    // GET /api/admin/users
    // -------------------------------------------------------------------------

    /** Un utilisateur standard ne peut pas acceder a la liste des users admin */
    public function testAdminUsersListForbiddenForRegularUser(): void
    {
        $this->client->request('GET', '/api/admin/users', [], [], [
            'HTTP_AUTHORIZATION' => "Bearer {$this->userToken}",
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    /** Un administrateur peut acceder a la liste des utilisateurs */
    public function testAdminUsersListAllowedForAdmin(): void
    {
        $this->client->request('GET', '/api/admin/users', [], [], [
            'HTTP_AUTHORIZATION' => "Bearer {$this->adminToken}",
        ]);

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertGreaterThanOrEqual(2, count($data));
    }

    /** Sans token, la route admin retourne 401 */
    public function testAdminUsersListUnauthenticated(): void
    {
        $this->client->request('GET', '/api/admin/users');
        $this->assertResponseStatusCodeSame(401);
    }

    // -------------------------------------------------------------------------
    // GET /api/admin/orders
    // -------------------------------------------------------------------------

    /** Un utilisateur standard ne peut pas acceder aux commandes admin */
    public function testAdminOrdersListForbiddenForRegularUser(): void
    {
        $this->client->request('GET', '/api/admin/orders', [], [], [
            'HTTP_AUTHORIZATION' => "Bearer {$this->userToken}",
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    /** Un administrateur peut acceder a toutes les commandes */
    public function testAdminOrdersListAllowedForAdmin(): void
    {
        $this->client->request('GET', '/api/admin/orders', [], [], [
            'HTTP_AUTHORIZATION' => "Bearer {$this->adminToken}",
        ]);

        $this->assertResponseStatusCodeSame(200);
    }

    // -------------------------------------------------------------------------
    // POST /api/products (ecriture admin)
    // -------------------------------------------------------------------------

    /** Un utilisateur standard ne peut pas creer de produit */
    public function testCreateProductForbiddenForRegularUser(): void
    {
        $this->client->request('POST', '/api/products', [], [], [
            'CONTENT_TYPE'      => 'application/json',
            'HTTP_AUTHORIZATION' => "Bearer {$this->userToken}",
        ], json_encode([
            'name'       => 'Test',
            'price'      => '10.00',
            'stock'      => 1,
            'categoryId' => 1,
        ]));

        $this->assertResponseStatusCodeSame(403);
    }
}
