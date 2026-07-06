<?php

namespace App\Controller\Admin;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Routes d'administration pour la gestion des utilisateurs.
 * Toutes les routes de ce controller necessitent ROLE_ADMIN.
 */
#[Route('/api/admin/users', name: 'api_admin_users_')]
#[IsGranted('ROLE_ADMIN')]
class AdminUserController extends AbstractController
{
    public function __construct(
        private readonly UserRepository         $userRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    /**
     * Liste tous les utilisateurs inscrits.
     * Le mot de passe (hash) est volontairement exclu de la reponse.
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $users = $this->userRepository->findAll();

        $data = array_map(fn($user) => [
            'id'        => $user->getId(),
            'email'     => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName'  => $user->getLastName(),
            'roles'     => $user->getRoles(),
            'createdAt' => $user->getCreatedAt()?->format('c'),
        ], $users);

        return $this->json($data);
    }

    /**
     * Supprime un compte utilisateur.
     * Impossible de supprimer son propre compte admin (protection contre l'auto-suppression).
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        /** @var \App\Entity\User $currentUser */
        $currentUser = $this->getUser();

        // Protection : un admin ne peut pas se supprimer lui-meme
        if ($currentUser->getId() === $id) {
            return $this->json(['error' => 'Impossible de supprimer votre propre compte.'], 403);
        }

        $user = $this->userRepository->find($id);
        if (!$user) {
            return $this->json(['error' => 'Utilisateur introuvable.'], 404);
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return $this->json(null, 204);
    }
}
