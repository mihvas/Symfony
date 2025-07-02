<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function createUser(array $data): User
    {
        if (!isset($data['phoneNumber']) || !isset($data['password'])) {
            throw new InvalidArgumentException('Missing param');
        }

        $userRepository = $this->em->getRepository(User::class);

        $existingUser = $userRepository->findOneBy(['phone_number' => $data['phoneNumber']]);

        if ($existingUser !== null) {
            $isPasswordValid = $this->passwordHasher->isPasswordValid($existingUser, $data['password']);

            if ($isPasswordValid) {
                return $existingUser;
            }

            throw new RuntimeException('Пользователь с таким телефоном уже существует, но пароль неверный.');
        }

        $user = new User();
        $user->setPhoneNumber($data['phoneNumber']);
        $user->setRoles(['ROLE_USER']);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
}
