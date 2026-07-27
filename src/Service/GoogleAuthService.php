<?php

namespace App\Service;

use App\Entity\User;
use League\OAuth2\Client\Provider\Google;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class GoogleAuthService
{
    public function __construct(
        private readonly FirebaseService $firebaseService,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $googleClientId,
        private readonly string $googleClientSecret,
    ) {
    }

    private function createProvider(): Google
    {
        return new Google([
            'clientId' => $this->googleClientId,
            'clientSecret' => $this->googleClientSecret,
            'redirectUri' => $this->urlGenerator->generate(
                'connect_google_check',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
        ]);
    }

    public function getAuthorizationUrl(): string
    {
        return $this->createProvider()->getAuthorizationUrl([
            'scope' => ['email', 'profile'],
        ]);
    }

    /**
     * @return array{success: bool, user?: array, error?: string}
     */
    public function handleCallback(Request $request): array
    {
        $code = $request->query->get('code');

        if (!$code) {
            return [
                'success' => false,
                'error' => 'Connexion Google annulée ou invalide.',
            ];
        }

        try {
            $provider = $this->createProvider();

            $token = $provider->getAccessToken('authorization_code', [
                'code' => $code,
            ]);

            /** @var \League\OAuth2\Client\Provider\GoogleUser $googleUser */
            $googleUser = $provider->getResourceOwner($token);
        } catch (\Throwable) {
            return [
                'success' => false,
                'error' => 'Impossible de valider la connexion Google.',
            ];
        }

        $googleId = (string) $googleUser->getId();
        $email = (string) $googleUser->getEmail();
        $name = (string) ($googleUser->getName() ?: $email);

        if ($email === '') {
            return [
                'success' => false,
                'error' => 'Google n\'a pas fourni d\'adresse email.',
            ];
        }

        $existingUser = $this->firebaseService->getUserByEmail($email);

        if ($existingUser === null) {
            $this->firebaseService->createGoogleUser($googleId, $email, $name);

            return [
                'success' => true,
                'user' => $this->firebaseService->getUserByEmail($email),
            ];
        }

        if (($existingUser['provider'] ?? null) === 'google') {
            $this->firebaseService->updateGoogleUser((string) $existingUser['key'], $googleId, $email, $name);

            return [
                'success' => true,
                'user' => $this->firebaseService->getUserByEmail($email),
            ];
        }

        return [
            'success' => false,
            'error' => 'Un compte existe déjà avec cet email. Connectez-vous avec email et mot de passe.',
        ];
    }

    public function buildSessionUser(array $user): array
    {
        return [
            'id' => $user['key'],
            'email' => $user['email'],
            'nomComplete' => $user['nomComplete'],
            'tel' => $user['tel'] ?? null,
            'type' => $user['type'],
            'provider' => $user['provider'] ?? null,
            'points' => $user['points'] ?? 0,
        ];
    }
}
