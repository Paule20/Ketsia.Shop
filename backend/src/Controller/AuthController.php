<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\UserService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Gere l'authentification des utilisateurs.
 * - POST /api/register : inscription d'un nouvel utilisateur
 * - POST /api/login    : connexion (traitement delegue au firewall JWT via json_login)
 * - GET  /api/me       : infos de l'utilisateur courant (token requis)
 */
#[Route('/api', name: 'api_auth_')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly UserService              $userService,
        private readonly UserRepository           $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly JWTTokenManagerInterface $jwtManager,
    ) {}

    /**
     * Inscription d'un nouvel utilisateur.
     * Valide les donnees, cree le compte et retourne les infos de base.
     * La connexion se fait ensuite via POST /api/login.
     */
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['email']) || empty($data['password'])) {
            return $this->json(['error' => 'Email et mot de passe requis.'], 400);
        }

        $user = $this->userRepository->findOneBy(['email' => $data['email']]);

        if (!$user || !$this->passwordHasher->isPasswordValid($user, $data['password'])) {
            return $this->json(['error' => 'Identifiants invalides.'], 401);
        }

        $token = $this->jwtManager->create($user);

        return $this->json([
            'token' => $token,
            'user'  => [
                'id'        => $user->getId(),
                'email'     => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName'  => $user->getLastName(),
                'roles'     => $user->getRoles(),
            ],
        ]);
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Corps de la requete JSON invalide.'], 400);
        }

        $result = $this->userService->register($data);

        if (isset($result['errors'])) {
            return $this->json(['errors' => $result['errors']], 422);
        }

        $user = $result['user'];

        return $this->json([
            'message' => 'Compte cree avec succes.',
            'user' => [
                'id'        => $user->getId(),
                'email'     => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName'  => $user->getLastName(),
            ],
        ], 201);
    }

    /**
     * Retourne les informations de l'utilisateur actuellement connecte.
     * Le token JWT est valide par le firewall avant d'atteindre cette methode.
     */
    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        return $this->json([
            'id'        => $user->getId(),
            'email'     => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName'  => $user->getLastName(),
            'roles'     => $user->getRoles(),
            'createdAt' => $user->getCreatedAt()?->format('c'),
        ]);
    }
}
