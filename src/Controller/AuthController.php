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
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth', name: 'api_auth_')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly RefreshTokenManagerInterface $refreshTokenManager,
        private readonly JWTTokenManagerInterface $JWTManager,
        private readonly AuthService $authService
    ) {
    }

    #[OA\Post(
        path: '/api/auth/register',
        summary: 'Register a new user',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['phoneNumber', 'password'],
                properties: [
                    new OA\Property(property: 'phoneNumber', type: 'string', example: '+1234567890'),
                    new OA\Property(property: 'password', type: 'string', example: 'strongPassword123'),
                ]
            )
        ),
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'User registered and tokens issued',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'token', type: 'string', example: 'jwt.token.here'),
                        new OA\Property(property: 'refreshToken', type: 'string', example: 'refresh.token.here'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Missing required fields'),
            new OA\Response(response: 500, description: 'Server error during registration'),
        ]
    )]
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

    #[OA\Get(
        path: '/api/auth/profile',
        summary: 'Get authenticated user profile',
        security: [['bearerAuth' => []]],
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User profile',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'phoneNumber', type: 'string', example: '+1234567890'),
                        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'), example: ['ROLE_USER']),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
        ]
    )]
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

    #[OA\Post(
        path: '/api/auth/logout',
        summary: 'Logout user and revoke refresh token',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['refreshToken'],
                properties: [
                    new OA\Property(property: 'refreshToken', type: 'string', example: 'refresh.token.here'),
                ]
            )
        ),
        tags: ['Authentication'],
        responses: [
            new OA\Response(response: 200, description: 'Logout successful', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                ]
            )),
            new OA\Response(response: 400, description: 'Missing or invalid refresh token'),
            new OA\Response(response: 500, description: 'Server error during logout'),
        ]
    )]
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
