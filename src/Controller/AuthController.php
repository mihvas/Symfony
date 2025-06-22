<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Service\AuthService;
use DateTimeImmutable;
use Exception;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/auth', name: 'api_auth_')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly RefreshTokenManagerInterface $refreshTokenManager,
        private readonly JWTTokenManagerInterface $JWTManager,
        private readonly AuthService $authService
    ) {
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['phoneNumber'], $data['password'])) {
            return $this->json(['error' => 'missing required fields'], 400);
        }

        try {
            $user = $this->authService->createUser($data);
        } catch (Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }

        try {
            $refreshToken = new RefreshToken();
            $refreshToken->setRefreshToken(bin2hex(random_bytes(64)));
            $refreshToken->setUsername($user->getUserIdentifier());
            $refreshToken->setValid((new DateTimeImmutable())->modify('+1 month'));
            $this->refreshTokenManager->save($refreshToken);

            return $this->json([
                'token' => $this->JWTManager->create($user),
                'refreshToken' => $refreshToken->getRefreshToken(),
            ], 201);
        } catch (Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/profile', name: 'profile', methods: ['GET'])]
    public function profile(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'not authenticated'], 401);
        }

        return $this->json([
            'id' => $user->getId(),
            'phoneNumber' => $user->getPhoneNumber(),
            'roles' => $user->getRoles(),
        ]);
    }

    #[Route('/logout', name: 'logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['refreshToken'])) {
            return $this->json(['error' => 'missing required fields'], 400);
        }

        try {
            $refreshToken = $this->refreshTokenManager->get($data['refreshToken']);
            if (!$refreshToken) {
                return $this->json(['error' => 'invalid refresh token'], 400);
            }
            $this->refreshTokenManager->delete($refreshToken);
        } catch (Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }

        return $this->json(['success' => true]);
    }
}
