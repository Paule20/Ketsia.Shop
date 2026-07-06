<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Gere la logique metier liee aux utilisateurs.
 * Isole la logique d'inscription du Controller pour la rendre testable
 * et reutilisable (ex: commande CLI de creation d'admin).
 */
class UserService
{
    public function __construct(
        private readonly EntityManagerInterface      $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepository             $userRepository,
        private readonly ValidatorInterface         $validator,
    ) {}

    /**
     * Cree un nouvel utilisateur apres validation des donnees.
     *
     * @param array $data Donnees issues du corps de la requete JSON
     * @return array{user: User}|array{errors: array} Utilisateur cree ou liste d'erreurs
     */
    public function register(array $data): array
    {
        // Verification que l'email n'est pas deja utilise (retour rapide avant creation d'objet)
        if ($this->userRepository->findOneBy(['email' => $data['email'] ?? ''])) {
            return ['errors' => ['email' => 'Cet email est deja utilise.']];
        }

        // Validation de la longueur du mot de passe avant hashage
        if (strlen($data['password'] ?? '') < 8) {
            return ['errors' => ['password' => 'Le mot de passe doit contenir au moins 8 caracteres.']];
        }

        $user = new User();
        $user->setEmail(trim($data['email'] ?? ''));
        $user->setFirstName(trim($data['firstName'] ?? ''));
        $user->setLastName(trim($data['lastName'] ?? ''));

        // Hash du mot de passe — jamais stocke en clair
        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        // Validation des contraintes Symfony (Assert\Email, NotBlank, etc.)
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return ['errors' => $errorMessages];
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return ['user' => $user];
    }
}
