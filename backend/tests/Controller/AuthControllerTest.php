<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Tests fonctionnels pour l'inscription et la connexion.
 * Utilise la base de donnees de test (ketsia_shop_test) definie dans .env.test.
 * Ces tests valident les cas nominaux et les erreurs metier (CP9).
 */
class AuthControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // Reinitialise le schema avant chaque test pour l'isolation
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $connection = $entityManager->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        $connection->executeStatement('TRUNCATE TABLE order_item');
        $connection->executeStatement('TRUNCATE TABLE `order`');
        $connection->executeStatement('TRUNCATE TABLE product');
        $connection->executeStatement('TRUNCATE TABLE category');
        $connection->executeStatement('TRUNCATE TABLE `user`');
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
    }

    // -------------------------------------------------------------------------
    // POST /api/register
    // -------------------------------------------------------------------------

    /** Cas nominal : inscription avec des donnees valides */
    public function testRegisterSuccess(): void
    {
        $this->client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'     => 'test@example.com',
            'password'  => 'Password123!',
            'firstName' => 'Jean',
            'lastName'  => 'Test',
        ]));

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('user', $data);
        $this->assertEquals('test@example.com', $data['user']['email']);
    }

    /** Un email deja utilise doit retourner 422 */
    public function testRegisterDuplicateEmail(): void
    {
        // Premiere inscription
        $this->client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'     => 'dupe@example.com',
            'password'  => 'Password123!',
            'firstName' => 'A',
            'lastName'  => 'B',
        ]));
        $this->assertResponseStatusCodeSame(201);

        // Deuxieme inscription avec le meme email
        $this->client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'     => 'dupe@example.com',
            'password'  => 'Password123!',
            'firstName' => 'A',
            'lastName'  => 'B',
        ]));

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayHasKey('email', $data['errors']);
    }

    /** Un mot de passe trop court doit retourner 422 */
    public function testRegisterPasswordTooShort(): void
    {
        $this->client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'     => 'short@example.com',
            'password'  => '123',
            'firstName' => 'A',
            'lastName'  => 'B',
        ]));

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayHasKey('password', $data['errors']);
    }

    // -------------------------------------------------------------------------
    // POST /api/login
    // -------------------------------------------------------------------------

    /** Connexion avec des identifiants valides doit retourner un token JWT */
    public function testLoginSuccess(): void
    {
        // Creer le compte d'abord
        $this->client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'     => 'login@example.com',
            'password'  => 'Password123!',
            'firstName' => 'Login',
            'lastName'  => 'User',
        ]));
        $this->assertResponseStatusCodeSame(201);

        // Connexion
        $this->client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'    => 'login@example.com',
            'password' => 'Password123!',
        ]));

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $data);
        $this->assertNotEmpty($data['token']);
    }

    /** Des identifiants invalides doivent retourner 401 */
    public function testLoginInvalidCredentials(): void
    {
        $this->client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'    => 'nope@example.com',
            'password' => 'wrongpassword',
        ]));

        $this->assertResponseStatusCodeSame(401);
    }

    // -------------------------------------------------------------------------
    // GET /api/me
    // -------------------------------------------------------------------------

    /** Acceder a /api/me sans token doit retourner 401 */
    public function testMeWithoutToken(): void
    {
        $this->client->request('GET', '/api/me');
        $this->assertResponseStatusCodeSame(401);
    }

    /** Acceder a /api/me avec un token valide doit retourner les infos utilisateur */
    public function testMeWithValidToken(): void
    {
        // Inscription
        $this->client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'     => 'me@example.com',
            'password'  => 'Password123!',
            'firstName' => 'Me',
            'lastName'  => 'User',
        ]));

        // Connexion pour obtenir le token
        $this->client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email'    => 'me@example.com',
            'password' => 'Password123!',
        ]));
        $token = json_decode($this->client->getResponse()->getContent(), true)['token'];

        // Requete authentifiee
        $this->client->request('GET', '/api/me', [], [], [
            'HTTP_AUTHORIZATION' => "Bearer $token",
        ]);

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('me@example.com', $data['email']);
    }
}
