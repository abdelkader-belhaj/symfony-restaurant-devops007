<?php

namespace App\Controller;

use App\Service\FirebaseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminSettingsController extends AbstractController
{
    #[Route('/admin/settings', name: 'admin_settings')]
    public function settings(Request $request): Response
    {
        $user = $request->getSession()->get('user');

        if (!$user || ($user['type'] ?? null) !== 'admin') {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('admin/settings.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/profile', name: 'user_profile', methods: ['GET', 'POST'])]
    public function profile(Request $request, FirebaseService $firebaseService): Response
    {
        $user = $request->getSession()->get('user');

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $profileUser = $user;

        if (!empty($user['id'])) {
            $storedUser = $firebaseService->getUser((string) $user['id']);
            if ($storedUser) {
                $profileUser = array_merge($profileUser, $storedUser);
            }
        }

        // Marquer les notifications comme lues
        $notifications = $profileUser['notifications'] ?? [];
        $hasUnread = false;
        foreach ($notifications as &$n) {
            if (empty($n['read'])) {
                $n['read'] = true;
                $hasUnread = true;
            }
        }
        if ($hasUnread && !empty($user['id'])) {
            $firebaseService->updateUser((string) $user['id'], ['notifications' => $notifications]);
            $profileUser['notifications'] = $notifications;
        }

        $reservations = $this->buildUserReservations($profileUser, $firebaseService);

        if ($request->isMethod('POST') && ($profileUser['type'] ?? 'client') === 'client') {
            $nomComplete = trim((string) $request->request->get('nomComplete'));
            $tel = preg_replace('/\D+/', '', (string) $request->request->get('tel'));
            $email = trim((string) $request->request->get('email'));
            $pwd = (string) $request->request->get('pwd');

            if ($nomComplete === '' || $email === '') {
                $this->addFlash('danger', 'Veuillez remplir le nom et l\'email.');
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('danger', 'L\'adresse email est invalide.');
            } else {
                $data = [
                    'nomComplete' => $nomComplete,
                    'email' => $email,
                ];

                if ($tel !== '') {
                    $data['tel'] = (int) $tel;
                }

                if ($pwd !== '') {
                    if (strlen($pwd) < 6) {
                        $this->addFlash('danger', 'Le mot de passe doit contenir au moins 6 caractères.');
                        return $this->redirectToRoute('user_profile');
                    }

                    $data['pwd'] = password_hash($pwd, PASSWORD_DEFAULT);
                }

                $firebaseService->updateUser((string) $profileUser['id'], $data);

                $updatedUser = $firebaseService->getUser((string) $profileUser['id']);
                if ($updatedUser) {
                    $request->getSession()->set('user', [
                        'id' => $updatedUser['id'] ?? $profileUser['id'],
                        'email' => $updatedUser['email'] ?? $email,
                        'nomComplete' => $updatedUser['nomComplete'] ?? $nomComplete,
                        'tel' => $updatedUser['tel'] ?? ($profileUser['tel'] ?? null),
                        'type' => $updatedUser['type'] ?? ($profileUser['type'] ?? 'client'),
                        'provider' => $updatedUser['provider'] ?? ($profileUser['provider'] ?? null),
                    ]);

                    $profileUser = array_merge($profileUser, $updatedUser);
                }

                $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');
                return $this->redirectToRoute('user_profile');
            }
        }

        $profileUser['reservationsCount'] = count($reservations);
        $profileUser['reservations'] = $reservations;
        $profileUser['hasPassword'] = !empty($profileUser['pwd']);
        unset($profileUser['pwd']);

        if (($profileUser['type'] ?? 'client') === 'client') {
            return $this->render('profile/client.html.twig', [
                'user' => $profileUser,
            ]);
        }

        return $this->render('admin/profile.html.twig', [
            'user' => $profileUser,
        ]);
    }

    #[Route('/profile/reservations-data', name: 'user_profile_reservations_data', methods: ['GET'])]
    public function reservationsData(Request $request, FirebaseService $firebaseService): JsonResponse
    {
        $user = $request->getSession()->get('user');
        if (!$user) {
            return $this->json([], 401);
        }

        $profileUser = $user;
        if (!empty($user['id'])) {
            $storedUser = $firebaseService->getUser((string) $user['id']);
            if ($storedUser) {
                $profileUser = array_merge($profileUser, $storedUser);
            }
        }

        return $this->json([
            'reservations' => $this->buildUserReservations($profileUser, $firebaseService),
        ]);
    }

    private function buildUserReservations(array $profileUser, FirebaseService $firebaseService): array
    {
        $reservations = [];
        foreach ($firebaseService->getAllTables() as $id => $table) {
            $emailMatches = isset($table['email']) && isset($profileUser['email'])
                && mb_strtolower(trim((string) $table['email'])) === mb_strtolower(trim((string) $profileUser['email']));

            if ($emailMatches) {
                $reservationStatus = (string) ($table['reservationStatus'] ?? $table['reservation_status'] ?? $table['status'] ?? 'pending');
                $resolvedStatus = in_array($reservationStatus, ['pending', 'confirmed', 'cancelled', 'completed', 'reserved'], true)
                    ? $reservationStatus
                    : 'pending';

                $reservations[] = [
                    'id' => (string) $id,
                    'name' => $table['name'] ?? ($profileUser['nomComplete'] ?? 'Client'),
                    'date' => $table['date'] ?? null,
                    'time' => $table['time'] ?? null,
                    'people' => $table['numberPeople'] ?? $table['persons'] ?? 0,
                    'status' => $table['status'] ?? 'pending',
                    'reservationStatus' => $resolvedStatus,
                    'message' => $table['message'] ?? '',
                ];
            }
        }

        usort($reservations, static function (array $left, array $right): int {
            return strcmp((string) ($right['date'] ?? ''), (string) ($left['date'] ?? ''));
        });

        return $reservations;
    }

    #[Route('/cadeaux', name: 'user_rewards', methods: ['GET', 'POST'])]
    public function rewards(Request $request, FirebaseService $firebaseService): Response
    {
        $user = $request->getSession()->get('user');

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if (($user['type'] ?? 'client') !== 'client') {
            return $this->redirectToRoute('app_dashboard');
        }

        $session = $request->getSession();
        $storedUser = !empty($user['id']) ? $firebaseService->getUser((string) $user['id']) : null;
        if ($storedUser) {
            $user = array_merge($user, $storedUser);
        }

        $summary = $firebaseService->getClientRewardSummary((string) ($user['nomComplete'] ?? ''));
        $wheelPool = $firebaseService->resolveRoulettePool($session);

        if ($request->isMethod('POST')) {
            if (!($summary['rewardAvailable'] ?? false)) {
                $this->addFlash('warning', 'Vous devez atteindre 500$ de commandes validées pour activer votre cadeau.');
            } elseif (($user['discountUsed'] ?? false)) {
                $this->addFlash('warning', 'Votre remise de 25% a deja ete utilisee.');
            } elseif (($user['discountActive'] ?? false)) {
                $this->addFlash('info', 'Votre remise de 25% est deja active pour votre prochaine commande.');
            } else {
                $session->set('gift_discount_active', true);
                $session->set('gift_discount_rate', (int) ($summary['discountRate'] ?? 25));
                if (!empty($user['id'])) {
                    $firebaseService->updateUser((string) $user['id'], [
                        'discountActive' => true,
                    ]);
                    $user['discountActive'] = true;
                }
                $this->addFlash('success', 'Votre remise de 25% est activée pour votre prochaine commande.');
                return $this->redirectToRoute('user_rewards');
            }
        }

        if (($user['discountActive'] ?? false) && !($user['discountUsed'] ?? false)) {
            $session->set('gift_discount_active', true);
            $session->set('gift_discount_rate', (int) ($summary['discountRate'] ?? 25));
        } else {
            $session->remove('gift_discount_active');
            $session->remove('gift_discount_rate');
        }

        $summary['giftDiscountActive'] = (bool) (($user['discountActive'] ?? false) && !($user['discountUsed'] ?? false));
        $summary['giftDiscountRate'] = (int) $session->get('gift_discount_rate', $summary['discountRate'] ?? 25);
        $summary['giftDiscountUsed'] = (bool) ($user['discountUsed'] ?? false);
        $summary['rouletteUsed'] = (bool) ($user['rouletteUsed'] ?? false);
        $summary['rouletteResult'] = $session->get('gift_roulette_result') ?: ($user['rouletteGift'] ?? null);
        $summary['roulettePendingGift'] = !$summary['rouletteUsed'] && is_array($summary['rouletteResult']);
        if (is_array($summary['rouletteResult']) && !$summary['rouletteUsed']) {
            $session->set('gift_roulette_order_gift', $summary['rouletteResult']);
        } elseif (!$summary['rouletteUsed']) {
            $session->remove('gift_roulette_used');
            $session->remove('gift_roulette_result');
            $session->remove('gift_roulette_order_gift');
        }
        $summary['roulettePoolSize'] = count($wheelPool);

        return $this->render('profile/rewards.html.twig', [
            'user' => $user,
            'reward' => $summary,
            'rouletteItems' => $wheelPool,
        ]);
    }

    #[Route('/cadeaux/consommer', name: 'user_rewards_consume', methods: ['POST'])]
    public function consumeReward(Request $request): JsonResponse
    {
        $session = $request->getSession();
        $session->remove('gift_discount_active');
        $session->remove('gift_discount_rate');

        return new JsonResponse(['success' => true]);
    }

    #[Route('/cadeaux/roulette', name: 'user_rewards_spin', methods: ['POST'])]
    public function spinRoulette(Request $request, FirebaseService $firebaseService): JsonResponse
    {
        $user = $request->getSession()->get('user');

        if (!$user || ($user['type'] ?? 'client') !== 'client') {
            return new JsonResponse(['success' => false, 'message' => 'Connexion requise.'], Response::HTTP_UNAUTHORIZED);
        }

        $storedUser = !empty($user['id']) ? $firebaseService->getUser((string) $user['id']) : null;
        if ($storedUser) {
            $user = array_merge($user, $storedUser);
        }

        $summary = $firebaseService->getClientRewardSummary((string) ($user['nomComplete'] ?? ''));
        if (!($summary['rouletteAvailable'] ?? false)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Vous devez dépasser 1000$ de commandes validées pour accéder à la roulette.',
            ], Response::HTTP_FORBIDDEN);
        }

        $session = $request->getSession();
        if (!($user['rouletteUsed'] ?? false) && is_array($user['rouletteGift'] ?? null)) {
            $session->set('gift_roulette_result', $user['rouletteGift']);
            $session->set('gift_roulette_order_gift', $user['rouletteGift']);

            return new JsonResponse([
                'success' => false,
                'message' => 'Votre cadeau roulette est deja gagne. Continuez votre commande gratuite.',
                'result' => $user['rouletteGift'],
                'redirectUrl' => $this->generateUrl('app_menu_commande', ['rouletteGift' => 1]),
            ], Response::HTTP_CONFLICT);
        }

        if (($user['rouletteUsed'] ?? false)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Vous avez déjà utilisé votre roulette cadeau.',
            ], Response::HTTP_CONFLICT);
        }

        $pool = $firebaseService->resolveRoulettePool($session);

        if ($pool === []) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Aucun plat disponible pour la roulette.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $result = $pool[array_rand($pool)];

        $session->set('gift_roulette_used', true);
        $session->set('gift_roulette_result', $result);
        $session->set('gift_roulette_order_gift', $result);
        $session->remove('gift_roulette_pool');

        if (!empty($user['id'])) {
            $firebaseService->updateUser((string) $user['id'], [
                'rouletteGift' => $result,
            ]);
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'La roulette a tourné avec succès.',
            'result' => $result,
            'pool' => $pool,
            'redirectUrl' => $this->generateUrl('app_menu_commande', ['rouletteGift' => 1]),
        ]);
    }
}
