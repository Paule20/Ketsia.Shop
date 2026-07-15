<?php

namespace App\Controller\Admin;

use App\Document\ContactMessage;
use App\Repository\ContactMessageRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Routes d'administration pour les messages de contact (stockes dans MongoDB).
 * Toutes les routes de ce controller necessitent ROLE_ADMIN.
 * Le controle d'acces est double : via security.yaml (access_control)
 * et via l'attribut #[IsGranted] sur le controller (defense en profondeur).
 */
#[Route('/api/admin/contact', name: 'api_admin_contact_')]
#[IsGranted('ROLE_ADMIN')]
class AdminContactController extends AbstractController
{
    public function __construct(
        private readonly ContactMessageRepository $contactMessageRepository,
        private readonly DocumentManager           $documentManager,
    ) {}

    /** Liste tous les messages de contact, du plus recent au plus ancien. */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $messages = $this->contactMessageRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->json(array_map([$this, 'serialize'], $messages));
    }

    /**
     * Modifie le statut d'un message de contact.
     * Valeurs acceptees : new, read.
     * Corps attendu : { "status": "read" }
     */
    #[Route('/{id}/status', name: 'update_status', methods: ['PATCH'])]
    public function updateStatus(string $id, Request $request): JsonResponse
    {
        $contactMessage = $this->contactMessageRepository->find($id);
        if (!$contactMessage) {
            return $this->json(['error' => 'Message introuvable.'], 404);
        }

        $data      = json_decode($request->getContent(), true);
        $newStatus = $data['status'] ?? null;

        if (!$newStatus || !in_array($newStatus, ContactMessage::VALID_STATUSES, true)) {
            return $this->json([
                'error'           => 'Statut invalide.',
                'valeurs_valides' => ContactMessage::VALID_STATUSES,
            ], 422);
        }

        $contactMessage->setStatus($newStatus);
        $this->documentManager->flush();

        return $this->json($this->serialize($contactMessage));
    }

    private function serialize(ContactMessage $m): array
    {
        return [
            'id'        => $m->getId(),
            'name'      => $m->getName(),
            'email'     => $m->getEmail(),
            'subject'   => $m->getSubject(),
            'message'   => $m->getMessage(),
            'status'    => $m->getStatus(),
            'createdAt' => $m->getCreatedAt()->format('c'),
        ];
    }
}
