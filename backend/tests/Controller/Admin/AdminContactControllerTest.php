<?php

namespace App\Tests\Controller\Admin;

use App\Document\ContactMessage;
use App\Entity\User;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Tests fonctionnels pour AdminContactController (messages de contact stockes dans MongoDB).
 * Valide : acces reserve a ROLE_ADMIN, liste triee, changement de statut.
 */
class AdminContactControllerTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $em;
    private DocumentManager $dm;
    private string $userToken  = '';
    private string $adminToken = '';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em     = static::getContainer()->get(EntityManagerInterface::class);
        $this->dm     = static::getContainer()->get(DocumentManager::class);
        $hasher       = static::getContainer()->get(UserPasswordHasherInterface::class);

        $conn = $this->em->getConnection();
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        $conn->executeStatement('TRUNCATE TABLE `user`');
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=1');
        $this->dm->getDocumentCollection(ContactMessage::class)->deleteMany([]);

        $user = new User();
        $user->setEmail('user_contact_test@example.com');
        $user->setFirstName('Regular');
        $user->setLastName('User');
        $user->setPassword($hasher->hashPassword($user, 'Password123!'));
        $this->em->persist($user);

        $admin = new User();
        $admin->setEmail('admin_contact_test@example.com');
        $admin->setFirstName('Admin');
        $admin->setLastName('User');
        $admin->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        $admin->setPassword($hasher->hashPassword($admin, 'Admin123!'));
        $this->em->persist($admin);

        $this->em->flush();

        $this->userToken  = $this->login('user_contact_test@example.com', 'Password123!');
        $this->adminToken = $this->login('admin_contact_test@example.com', 'Admin123!');
    }

    private function login(string $email, string $password): string
    {
        $this->client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => $email, 'password' => $password]));

        return json_decode($this->client->getResponse()->getContent(), true)['token'] ?? '';
    }

    private function createMessage(string $subject = 'Sujet test'): string
    {
        $message = new ContactMessage();
        $message->setName('Client Test');
        $message->setEmail('client@example.com');
        $message->setSubject($subject);
        $message->setMessage('Contenu du message.');
        $this->dm->persist($message);
        $this->dm->flush();

        return $message->getId();
    }

    // -------------------------------------------------------------------------
    // GET /api/admin/contact
    // -------------------------------------------------------------------------

    /** Sans authentification, la route retourne 401 */
    public function testListUnauthenticated(): void
    {
        $this->client->request('GET', '/api/admin/contact');
        $this->assertResponseStatusCodeSame(401);
    }

    /** Un utilisateur standard ne peut pas consulter les messages de contact */
    public function testListForbiddenForRegularUser(): void
    {
        $this->client->request('GET', '/api/admin/contact', [], [], [
            'HTTP_AUTHORIZATION' => "Bearer {$this->userToken}",
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    /** Un admin recoit la liste des messages, du plus recent au plus ancien */
    public function testListReturnsMessagesForAdmin(): void
    {
        $this->createMessage('Premier message');
        $this->createMessage('Second message');

        $this->client->request('GET', '/api/admin/contact', [], [], [
            'HTTP_AUTHORIZATION' => "Bearer {$this->adminToken}",
        ]);

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(2, $data);
        $this->assertEquals('Second message', $data[0]['subject']);
    }

    // -------------------------------------------------------------------------
    // PATCH /api/admin/contact/{id}/status
    // -------------------------------------------------------------------------

    /** Un admin peut marquer un message comme lu */
    public function testUpdateStatusSuccessForAdmin(): void
    {
        $id = $this->createMessage();

        $this->client->request('PATCH', "/api/admin/contact/$id/status", [], [], [
            'CONTENT_TYPE'       => 'application/json',
            'HTTP_AUTHORIZATION' => "Bearer {$this->adminToken}",
        ], json_encode(['status' => 'read']));

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('read', $data['status']);

        $this->dm->clear();
        $message = $this->dm->getRepository(ContactMessage::class)->find($id);
        $this->assertEquals('read', $message->getStatus());
    }

    /** Un statut qui ne fait pas partie de VALID_STATUSES doit etre rejete (422) */
    public function testUpdateStatusWithInvalidValueReturns422(): void
    {
        $id = $this->createMessage();

        $this->client->request('PATCH', "/api/admin/contact/$id/status", [], [], [
            'CONTENT_TYPE'       => 'application/json',
            'HTTP_AUTHORIZATION' => "Bearer {$this->adminToken}",
        ], json_encode(['status' => 'statut_qui_nexiste_pas']));

        $this->assertResponseStatusCodeSame(422);
    }

    /** Modifier le statut d'un message inexistant retourne 404 */
    public function testUpdateStatusNotFound(): void
    {
        $this->client->request('PATCH', '/api/admin/contact/000000000000000000000000/status', [], [], [
            'CONTENT_TYPE'       => 'application/json',
            'HTTP_AUTHORIZATION' => "Bearer {$this->adminToken}",
        ], json_encode(['status' => 'read']));

        $this->assertResponseStatusCodeSame(404);
    }

    /** Un utilisateur standard ne peut pas changer le statut d'un message */
    public function testUpdateStatusForbiddenForRegularUser(): void
    {
        $id = $this->createMessage();

        $this->client->request('PATCH', "/api/admin/contact/$id/status", [], [], [
            'CONTENT_TYPE'       => 'application/json',
            'HTTP_AUTHORIZATION' => "Bearer {$this->userToken}",
        ], json_encode(['status' => 'read']));

        $this->assertResponseStatusCodeSame(403);
    }
}
