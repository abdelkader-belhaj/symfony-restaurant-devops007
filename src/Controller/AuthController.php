<?php

namespace App\Controller;

use App\Service\FirebaseService;
use App\Service\GoogleAuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
    public function __construct(
        private readonly FirebaseService $firebaseService,
        private readonly GoogleAuthService $googleAuthService,
    ) {
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request): Response
    {
        $user = $request->getSession()->get('user');
        if ($user) {
            return $this->redirectBasedOnUserType($user['type']);
        }

        return $this->render('auth/register.html.twig');
    }

    #[Route('/register/submit', name: 'app_register_submit', methods: ['POST'])]
    public function registerSubmit(Request $request): Response
    {
        $user = $request->getSession()->get('user');
        if ($user) {
            return $this->redirectBasedOnUserType($user['type']);
        }

        $nomComplete = trim((string) $request->request->get('nomComplete', ''));
        $tel = trim((string) $request->request->get('tel', ''));
        $email = trim((string) $request->request->get('email', ''));
        $pwd = (string) $request->request->get('pwd', '');
        $type = 'client';

        if ($nomComplete === '' || $tel === '' || $email === '' || $pwd === '') {
            $this->addFlash('error', 'Tous les champs sont obligatoires.');
            return $this->redirectToRoute('app_register');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', 'Veuillez saisir une adresse email valide.');
            return $this->redirectToRoute('app_register');
        }

        if (!preg_match('/^[0-9]{8}$/', $tel)) {
            $this->addFlash('error', 'Le numéro de téléphone doit contenir exactement 8 chiffres.');
            return $this->redirectToRoute('app_register');
        }

        if (strlen($pwd) < 6) {
            $this->addFlash('error', 'Le mot de passe doit contenir au moins 6 caractères.');
            return $this->redirectToRoute('app_register');
        }

        $existingUser = $this->firebaseService->getUserByEmail($email);
        if ($existingUser) {
            if (($existingUser['provider'] ?? null) === 'google' && empty($existingUser['pwd'])) {
                $this->addFlash('error', 'Ce compte utilise Google. Connectez-vous avec Google ou ajoutez un mot de passe dans votre profil.');
            } else {
                $this->addFlash('error', 'Un compte avec cet email existe déjà');
            }

            return $this->redirectToRoute('app_register');
        }

        $hashedPassword = password_hash($pwd, PASSWORD_DEFAULT);

        $userData = [
            'nomComplete' => $nomComplete,
            'tel' => $tel,
            'email' => $email,
            'pwd' => $hashedPassword,
            'type' => $type,
            'provider' => 'local',
        ];

        $this->firebaseService->createUser($userData);

        $this->addFlash('success', 'Inscription réussie ! Vous pouvez maintenant vous connecter.');

        return $this->redirectToRoute('app_login');
    }

    #[Route('/login', name: 'app_login')]
    public function login(Request $request): Response
    {
        $user = $request->getSession()->get('user');
        if ($user) {
            return $this->redirectBasedOnUserType($user['type']);
        }

        return $this->render('auth/login.html.twig');
    }

    #[Route('/login/submit', name: 'app_login_submit', methods: ['POST'])]
    public function loginSubmit(Request $request): Response
    {
        $email = $request->request->get('email');
        $pwd = $request->request->get('pwd');

        $user = $this->firebaseService->getUserByEmail($email);

        if (!$user) {
            $this->addFlash('error', 'Email ou mot de passe incorrect');
            return $this->redirectToRoute('app_login');
        }

        if (empty($user['pwd'])) {
            if (($user['provider'] ?? null) === 'google') {
                $this->addFlash('error', 'Ce compte utilise Google. Connectez-vous avec Google ou ajoutez un mot de passe dans votre profil.');
            } else {
                $this->addFlash('error', 'Email ou mot de passe incorrect');
            }

            return $this->redirectToRoute('app_login');
        }

        if (!password_verify($pwd, $user['pwd'])) {
            $this->addFlash('error', 'Email ou mot de passe incorrect');
            return $this->redirectToRoute('app_login');
        }

        $this->loginUser($request, $user);

        return $this->redirectBasedOnUserType($user['type']);
    }

    #[Route('/connect/google', name: 'connect_google')]
    public function connectGoogle(): Response
    {
        return $this->redirect($this->googleAuthService->getAuthorizationUrl());
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectGoogleCheck(Request $request): Response
    {
        $user = $request->getSession()->get('user');
        if ($user) {
            return $this->redirectBasedOnUserType($user['type']);
        }

        $result = $this->googleAuthService->handleCallback($request);

        if (!$result['success']) {
            $this->addFlash('error', $result['error']);
            return $this->redirectToRoute('app_login');
        }

        $this->loginUser($request, $result['user']);

        return $this->redirectBasedOnUserType($result['user']['type']);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(Request $request): Response
    {
        $request->getSession()->remove('user');
        return $this->redirectToRoute('app_login');
    }

    private function loginUser(Request $request, array $user): void
    {
        $request->getSession()->set('user', $this->googleAuthService->buildSessionUser($user));
    }

    private function redirectBasedOnUserType(string $type): Response
    {
        return $type === 'admin'
            ? $this->redirectToRoute('app_dashboard')
            : $this->redirectToRoute('front_index');
    }
}
