<?php

namespace App\Controller;

use App\Document\ContactMessage;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/contact', name: 'api_contact_')]
class ContactController extends AbstractController
{
    public function __construct(
        private readonly DocumentManager $documentManager,
    ) {}

    #[Route('', name: 'submit', methods: ['POST'])]
    public function submit(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $name    = trim((string) ($data['name'] ?? ''));
        $email   = trim((string) ($data['email'] ?? ''));
        $subject = trim((string) ($data['subject'] ?? ''));
        $message = trim((string) ($data['message'] ?? ''));

        $errors = [];
        if ($name === '') {
            $errors['name'] = 'Le nom est requis.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email invalide.';
        }
        if ($subject === '') {
            $errors['subject'] = 'Le sujet est requis.';
        }
        if ($message === '') {
            $errors['message'] = 'Le message est requis.';
        }

        if ($errors) {
            return $this->json(['errors' => $errors], 400);
        }

        $contactMessage = new ContactMessage();
        $contactMessage->setName($name);
        $contactMessage->setEmail($email);
        $contactMessage->setSubject($subject);
        $contactMessage->setMessage($message);

        $this->documentManager->persist($contactMessage);
        $this->documentManager->flush();

        return $this->json(['success' => true], 201);
    }
}
