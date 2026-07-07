<?php

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Tests fonctionnels pour ProductController (CP9).
 * Complète ProductApiTest (GET liste) avec : détail, CRUD admin, droits, validations, 404.
 */
class ProductControllerTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $em;
    private string $userToken  = '';
    private string $adminToken = '';
    private int $categoryId;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em     = static::getContainer()->get(EntityManagerInterface::class);
        $hasher       = static::getContainer()->get(UserPasswordHasherInterface::class);

        // Nettoyage complet avant chaque test
        $conn = $this->em->getConnection();
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        $conn->executeStatement('TRUNCATE TABLE order_item');
        $conn->executeStatement('TRUNCATE TABLE `order`');
        $conn->executeStatement('TRUNCATE TABLE product');
        $conn->executeStatement('TRUNCATE TABLE category');
        $conn->executeStatement('TRUNCATE TABLE `user`');
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=1');

        // Categorie de test reutilisable
        $category = new Category();
        $category->setName('Femme');
        $category->setSlug('femme');
        $this->em->persist($category);
        $this->em->flush();
        $this->categoryId = $category->getId();

        // Utilisateur standard
        $user = new User();
        $user->setEmail('user_product_test@example.com');
        $user->setFirstName('Regular');
        $user->setLastName('User');
        $user->setPassword($hasher->hashPassword($user, 'Password123!'));
        $this->em->persist($user);

        // Administrateur
        $admin = new User();
        $admin->setEmail('admin_product_test@example.com');
        $admin->setFirstName('Admin');
        $admin->setLastName('User');
        $admin->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        $admin->setPassword($hasher->hashPassword($admin, 'Admin123!'));
        $this->em->persist($admin);

        $this->em->flush();

        $this->userToken  = $this->login('user_product_test@example.com', 'Password123!');
        $this->adminToken = $this->login('admin_product_test@example.com', 'Admin123!');
    }

    /** Helper : connexion et recuperation du token */
    private function login(string $email, string $password): string
    {
        $this->client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => $email, 'password' => $password]));

        return json_decode($this->client->getResponse()->getContent(), true)['token'] ?? '';
    }

    /** Helper : cree un produit directement en base et retourne son id */
    private function createProduct(string $name = 'Robe Test', string $price = '29.99', int $stock = 10): int
    {
        $category = $this->em->getRepository(Category::class)->find($this->categoryId);

        $product = new Product();
        $product->setName($name);
        $product->setDescription('Description de test');
        $product->setPrice($price);
        $product->setStock($stock);
        $product->setCategory($category);
        $product->setSubCategory('Robes');
        $product->setImageUrl('https://example.com/image.jpg');
        $product->setSizes(['XS', 'S', 'M']);
        $this->em->persist($product);
        $this->em->flush();

        return $product->getId();
    }

    // -------------------------------------------------------------------------
    // GET /api/products (liste + filtres)
    // -------------------------------------------------------------------------

    /** Le filtre par categorie ne retourne que les produits de cette categorie */
    public function testGetProductsFiltersByCategory(): void
    {
        $this->createProduct('Robe Femme');

        $otherCategory = new Category();
        $otherCategory->setName('Homme');
        $otherCategory->setSlug('homme');
        $this->em->persist($otherCategory);
        $this->em->flush();

        $productHomme = new Product();
        $productHomme->setName('Chemise Homme');
        $productHomme->setPrice('39.99');
        $productHomme->setStock(5);
        $productHomme->setCategory($otherCategory);
        $this->em->persist($productHomme);
        $this->em->flush();

        $this->client->request('GET', '/api/products?category=femme');

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(1, $data);
        $this->assertEquals('Robe Femme', $data[0]['name']);
    }

    // -------------------------------------------------------------------------
    // GET /api/products/{id}
    // -------------------------------------------------------------------------

    /** Le detail d'un produit existant retourne ses champs, y compris la categorie */
    public function testGetProductByIdSuccess(): void
    {
        $productId = $this->createProduct('Robe Bustier', '45.00', 12);

        $this->client->request('GET', "/api/products/$productId");

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Robe Bustier', $data['name']);
        $this->assertEquals(12, $data['stock']);
        $this->assertEquals('Femme', $data['category']['name']);
    }

    /** Un produit inexistant retourne 404 */
    public function testGetProductByIdNotFound(): void
    {
        $this->client->request('GET', '/api/products/999999');
        $this->assertResponseStatusCodeSame(404);
    }

    // -------------------------------------------------------------------------
    // POST /api/products (creation, admin uniquement)
    // -------------------------------------------------------------------------

    /** Un admin peut creer un produit valide */
    public function testCreateProductSuccessForAdmin(): void
    {
        $this->client->request('POST', '/api/products', [], [], [
            'CONTENT_TYPE'       => 'application/json',
            'HTTP_AUTHORIZATION' => "Bearer {$this->adminToken}",
        ], json_encode([
            'name'        => 'Robe Nouvelle',
            'description' => 'Une jolie robe',
            'price'       => '49.99',
            'stock'       => 20,
            'categoryId'  => $this->categoryId,
            'subCategory' => 'Robes',
            'imageUrl'    => 'https://example.com/robe.jpg',
            'sizes'       => ['XS', 'S', 'M', 'L'],
        ]));

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Robe Nouvelle', $data['name']);
        $this->assertEquals('Femme', $data['category']['name']);
        $this->assertArrayHasKey('id', $data);
    }

    /** Une categorie inexistante doit retourner 422 avec une erreur explicite */
    public function testCreateProductWithInvalidCategoryReturns422(): void
    {
        $this->client->request('POST', '/api/products', [], [], [
            'CONTENT_TYPE'       => 'application/json',
            'HTTP_AUTHORIZATION' => "Bearer {$this->adminToken}",
        ], json_encode([
            'name'       => 'Produit Sans Categorie',
            'price'      => '10.00',
            'stock'      => 5,
            'categoryId' => 999999,
        ]));

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayHasKey('categoryId', $data['errors']);
    }

    // -------------------------------------------------------------------------
    // PUT /api/products/{id} (modification, admin uniquement)
    // -------------------------------------------------------------------------

    /** Un admin peut modifier un produit existant */
    public function testUpdateProductSuccessForAdmin(): void
    {
        $productId = $this->createProduct('Robe Avant Modif', '30.00', 15);

        $this->client->request('PUT', "/api/products/$productId", [], [], [
            'CONTENT_TYPE'       => 'application/json',
            'HTTP_AUTHORIZATION' => "Bearer {$this->adminToken}",
        ], json_encode([
            'name'  => 'Robe Après Modif',
            'price' => '35.00',
            'stock' => 8,
        ]));

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Robe Après Modif', $data['name']);
        $this->assertEquals(8, $data['stock']);
    }

    /** Modifier un produit inexistant retourne 404 */
    public function testUpdateProductNotFound(): void
    {
        $this->client->request('PUT', '/api/products/999999', [], [], [
            'CONTENT_TYPE'       => 'application/json',
            'HTTP_AUTHORIZATION' => "Bearer {$this->adminToken}",
        ], json_encode(['name' => 'Peu importe']));

        $this->assertResponseStatusCodeSame(404);
    }

    /** Un utilisateur standard ne peut pas modifier un produit */
    public function testUpdateProductForbiddenForRegularUser(): void
    {
        $productId = $this->createProduct();

        $this->client->request('PUT', "/api/products/$productId", [], [], [
            'CONTENT_TYPE'       => 'application/json',
            'HTTP_AUTHORIZATION' => "Bearer {$this->userToken}",
        ], json_encode(['name' => 'Tentative de modif']));

        $this->assertResponseStatusCodeSame(403);
    }

    // -------------------------------------------------------------------------
    // DELETE /api/products/{id} (suppression, admin uniquement)
    // -------------------------------------------------------------------------

    /** Un admin peut supprimer un produit, qui disparaît bien de la base */
    public function testDeleteProductSuccessForAdmin(): void
    {
        $productId = $this->createProduct('Robe À Supprimer');

        $this->client->request('DELETE', "/api/products/$productId", [], [], [
            'HTTP_AUTHORIZATION' => "Bearer {$this->adminToken}",
        ]);

        $this->assertResponseStatusCodeSame(204);

        $this->em->clear();
        $this->assertNull($this->em->getRepository(Product::class)->find($productId));
    }

    /** Supprimer un produit inexistant retourne 404 */
    public function testDeleteProductNotFound(): void
    {
        $this->client->request('DELETE', '/api/products/999999', [], [], [
            'HTTP_AUTHORIZATION' => "Bearer {$this->adminToken}",
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    /** Un utilisateur standard ne peut pas supprimer un produit */
    public function testDeleteProductForbiddenForRegularUser(): void
    {
        $productId = $this->createProduct();

        $this->client->request('DELETE', "/api/products/$productId", [], [], [
            'HTTP_AUTHORIZATION' => "Bearer {$this->userToken}",
        ]);

        $this->assertResponseStatusCodeSame(403);

        // Le produit doit toujours exister
        $this->em->clear();
        $this->assertNotNull($this->em->getRepository(Product::class)->find($productId));
    }
}
