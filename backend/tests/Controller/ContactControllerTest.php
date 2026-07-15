<?php

namespace App\Tests\Controller;

use App\Document\ContactMessage;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests fonctionnels pour ContactController (messages de contact stockes dans MongoDB).
 * Valide : soumission publique, validation des champs, persistance en base.
 */
class ContactControllerTest extends WebTestCase
{
    private $client;
    private DocumentManager $dm;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->dm     = static::getContainer()->get(DocumentManager::class);

        $this->dm->getDocumentCollection(ContactMessage::class)->deleteMany([]);
    }

    // -------------------------------------------------------------------------
    // POST /api/contact
    // -------------------------------------------------------------------------

    /** Soumission valide : le message est persiste et la reponse confirme le succes */
    public function testSubmitSuccess(): void
    {
        $this->client->request('POST', '/api/contact', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name'    => 'Alice Dupont',
            'email'   => 'alice@example.com',
            'subject' => 'Question sur une commande',
            'message' => 'Bonjour, ma commande n\'est toujours pas arrivee.',
        ]));

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);

        $messages = $this->dm->getRepository(ContactMessage::class)->findAll();
        $this->assertCount(1, $messages);
        $this->assertEquals('Alice Dupont', $messages[0]->getName());
        $this->assertEquals(ContactMessage::STATUS_NEW, $messages[0]->getStatus());
    }

    /** Champs manquants : retourne 400 avec une erreur par champ */
    public function testSubmitMissingFieldsReturns400(): void
    {
        $this->client->request('POST', '/api/contact', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([]));

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('name', $data['errors']);
        $this->assertArrayHasKey('email', $data['errors']);
        $this->assertArrayHasKey('subject', $data['errors']);
        $this->assertArrayHasKey('message', $data['errors']);
    }

    /** Email invalide : retourne 400 avec une erreur ciblee sur le champ email */
    public function testSubmitInvalidEmailReturns400(): void
    {
        $this->client->request('POST', '/api/contact', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name'    => 'Bob',
            'email'   => 'pas-un-email',
            'subject' => 'Sujet',
            'message' => 'Message',
        ]));

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('email', $data['errors']);
        $this->assertArrayNotHasKey('name', $data['errors']);
    }
}
