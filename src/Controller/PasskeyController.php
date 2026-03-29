<?php

namespace App\Controller;

use App\Service\PasskeyService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class PasskeyController extends AbstractController
{
    public function __construct(
        private PasskeyService $passkeyService,
        private JWTTokenManagerInterface $jwtManager,
        private Security $security
    ) {}

    // ==========================================
    // REGISTRATION ROUTES (session auth - /account/passkey/*)
    // ==========================================

    /**
     * Get registration options for creating a new passkey.
     * User must be authenticated via session.
     */
    #[Route('/account/passkey/register/options', name: 'passkey_register_options', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function registerOptions(): JsonResponse
    {
        try {
            $user = $this->getUser();
            $options = $this->passkeyService->generateRegistrationOptions($user);

            return $this->json($options);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Verify registration response and save the passkey.
     */
    #[Route('/account/passkey/register/verify', name: 'passkey_register_verify', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function registerVerify(Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                throw new \InvalidArgumentException('Invalid request body');
            }

            $name = $data['name'] ?? 'Windows Hello';
            $credential = $this->passkeyService->verifyRegistration($user, $data, $name);

            return $this->json([
                'success' => true,
                'credential' => [
                    'id' => $credential->getId(),
                    'name' => $credential->getName(),
                    'createdAt' => $credential->getCreatedAt()->format('c'),
                ]
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * List all passkeys for the authenticated user.
     */
    #[Route('/account/passkey/list', name: 'passkey_list', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function listPasskeys(): JsonResponse
    {
        $user = $this->getUser();
        $credentials = $this->passkeyService->getCredentials($user);

        return $this->json([
            'credentials' => array_map(fn($cred) => [
                'id' => $cred->getId(),
                'name' => $cred->getName(),
                'createdAt' => $cred->getCreatedAt()->format('c'),
                'lastUsedAt' => $cred->getLastUsedAt()?->format('c'),
            ], $credentials)
        ]);
    }

    /**
     * Delete a passkey.
     */
    #[Route('/account/passkey/{id}', name: 'passkey_delete', methods: ['DELETE'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function deletePasskey(int $id): JsonResponse
    {
        try {
            $user = $this->getUser();
            $this->passkeyService->deleteCredential($user, $id);

            return $this->json(['success' => true]);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    // ==========================================
    // LOGIN ROUTES (public - /api/passkey/login/*)
    // ==========================================

    /**
     * Get authentication options for passkey login.
     * Public endpoint - no authentication required.
     */
    #[Route('/api/passkey/login/options', name: 'api_passkey_login_options', methods: ['POST'])]
    public function loginOptions(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $email = $data['email'] ?? null;

            $options = $this->passkeyService->generateAuthenticationOptions($email);

            return $this->json($options);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Verify authentication response and log user in via session.
     * Public endpoint - no authentication required.
     * This creates a session for web-based login.
     */
    #[Route('/api/passkey/login/verify', name: 'api_passkey_login_verify', methods: ['POST'])]
    public function loginVerify(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                throw new \InvalidArgumentException('Invalid request body');
            }

            $user = $this->passkeyService->verifyAuthentication($data);
            
            // Log the user in via session (for web pages)
            $this->security->login($user, 'form_login', 'main');
            
            // Also generate JWT for API use
            $token = $this->jwtManager->create($user);

            return $this->json([
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'roles' => $user->getRoles(),
                ]
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], Response::HTTP_UNAUTHORIZED);
        }
    }
}
