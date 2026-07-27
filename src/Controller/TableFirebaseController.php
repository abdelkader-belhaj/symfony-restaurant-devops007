<?php

namespace App\Controller;

use App\Service\FirebaseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tables')]
class TableFirebaseController extends AbstractController
{
    private FirebaseService $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    #[Route('/', name: 'firebase_table_index')]
    public function index(Request $request): Response
    {
        $tables = $this->firebase->getAllTables();

        // Recherche
        $search = $request->query->get('search', '');
        $statusFilter = $request->query->get('status', '');

        if ($search || $statusFilter) {
            $tables = array_filter($tables, function ($table) use ($search, $statusFilter) {
                $matchesSearch = true;
                $matchesStatus = true;

                if ($search) {
                    $searchLower = mb_strtolower($search);
                    $name = mb_strtolower($table['name'] ?? '');
                    $email = mb_strtolower($table['email'] ?? '');
                    $matchesSearch = str_contains($name, $searchLower) || str_contains($email, $searchLower);
                }

                if ($statusFilter) {
                    $matchesStatus = ($table['reservationStatus'] ?? 'pending') === $statusFilter;
                }

                return $matchesSearch && $matchesStatus;
            });
        }

        // Trier par date
        usort($tables, function ($a, $b) {
            $dateA = $a['date'] ?? '0000-00-00';
            $dateB = $b['date'] ?? '0000-00-00';
            return strcmp($dateB, $dateA); // Plus récent en premier
        });

        return $this->render('tableFirebase/index.html.twig', [
            'tables' => $tables,
            'search' => $search,
            'statusFilter' => $statusFilter,
        ]);
    }

    #[Route('/new', name: 'firebase_table_new')]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $submittedToken = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('new-reservation', $submittedToken)) {
                $this->addFlash('error', 'Token CSRF invalide.');
                return $this->redirectToRoute('front_index', ['_fragment' => 'book-a-table']);
            }

            $data = [
                'name' => $request->request->get('name'),
                'email' => $request->request->get('email'),
                'phone' => $request->request->get('phone'),
                'date' => $request->request->get('date'),
                'time' => $request->request->get('time'),
                'people' => $request->request->get('people'),
                'message' => $request->request->get('message'),
            ];
            $this->firebase->createTable($data);

            $email = $data['email'] ?? '';
            if ($email) {
                $this->firebase->addPointsByEmail($email, 27);
            }

            $this->addFlash('success', 'Votre demande de réservation a été envoyée.');
            return $this->redirectToRoute('front_index', ['_fragment' => 'book-a-table']);
        }
        return $this->render('tableFirebase/new.html.twig');
    }

    #[Route('/{key}', name: 'firebase_table_show')]
    public function show(string $key): Response
    {
        $table = $this->firebase->getTable($key);
        if (!$table) {
            throw $this->createNotFoundException('Réservation non trouvée');
        }
        return $this->render('tableFirebase/show.html.twig', [
            'table' => $table,
            'key' => $key,
        ]);
    }

    #[Route('/{key}/edit', name: 'firebase_table_edit')]
    public function edit(Request $request, string $key): Response
    {
        $table = $this->firebase->getTable($key);
        if (!$table) {
            throw $this->createNotFoundException('Réservation non trouvée');
        }
        if ($request->isMethod('POST')) {
            $submittedToken = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('edit-reservation' . $key, $submittedToken)) {
                $this->addFlash('error', 'Token CSRF invalide.');
                return $this->redirectToRoute('firebase_table_index');
            }

            $data = [
                'name' => $request->request->get('name'),
                'email' => $request->request->get('email'),
                'tel' => $request->request->get('tel'),
                'date' => $request->request->get('date'),
                'time' => $request->request->get('time'),
                'numberPeople' => $request->request->get('numberPeople'),
                'message' => $request->request->get('message'),
            ];
            $this->firebase->updateTable($key, $data);
            $this->addFlash('success', 'Réservation modifiée avec succès.');
            return $this->redirectToRoute('firebase_table_index');
        }
        return $this->render('tableFirebase/edit.html.twig', [
            'table' => $table,
            'key' => $key,
        ]);
    }

    #[Route('/{key}/delete', name: 'firebase_table_delete', methods: ['POST'])]
    public function delete(string $key, Request $request): Response
    {
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete' . $key, $submittedToken)) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('firebase_table_index');
        }

        $this->firebase->deleteTable($key);
        $this->addFlash('success', 'Réservation supprimée avec succès.');
        return $this->redirectToRoute('firebase_table_index');
    }

    #[Route('/{tableId}/status', name: 'admin_update_table_status', methods: ['POST'])]
    public function updateStatus(Request $request, string $tableId): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (!isset($data['status'])) {
                throw new \InvalidArgumentException('Le statut est requis');
            }

            if (!in_array($data['status'], ['available', 'reserved', 'occupied', 'maintenance'])) {
                throw new \InvalidArgumentException('Statut invalide');
            }

            // Vérification du token CSRF
            if (!$this->isCsrfTokenValid('update-table-status', $request->headers->get('X-CSRF-TOKEN'))) {
                throw new \InvalidArgumentException('Token CSRF invalide');
            }

            $table = $this->firebase->getTable($tableId);
            if (!$table) {
                throw new \InvalidArgumentException('Table non trouvée');
            }

            // Sauvegarder l'ancien statut avant mise à jour
            $oldStatus = $table['status'];

            // Mise à jour du statut
            $table['status'] = $data['status'];

            // Si on passe de réservé à un autre statut, supprimer les infos de réservation
            if ($oldStatus === 'reserved' && $table['status'] !== 'reserved') {
                unset($table['reservation']);
            }

            // Mettre à jour reservationStatus en cohérence avec le statut
            if ($table['status'] === 'reserved') {
                $table['reservationStatus'] = 'confirmed';
            } elseif ($table['status'] === 'available') {
                $table['reservationStatus'] = 'pending';
            } elseif ($table['status'] === 'occupied') {
                $table['reservationStatus'] = 'confirmed';
            } elseif ($table['status'] === 'maintenance') {
                $table['reservationStatus'] = 'pending';
            }

            $this->firebase->updateTable($tableId, $table);

            return $this->json(['message' => 'Statut mis à jour avec succès']);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{key}/reservation-status', name: 'firebase_table_reservation_status', methods: ['POST'])]
    public function updateReservationStatus(string $key, Request $request): Response
    {
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('reservation-status' . $key, $submittedToken)) {
            return $this->json(['success' => false, 'message' => 'Token CSRF invalide.'], 403);
        }

        $newStatus = $request->request->get('reservationStatus');

        if (!in_array($newStatus, ['pending', 'confirmed', 'cancelled', 'completed'])) {
            return $this->json(['success' => false, 'message' => 'Statut de réservation invalide.'], 400);
        }

        $table = $this->firebase->getTable($key);
        if (!$table) {
            return $this->json(['success' => false, 'message' => 'Réservation non trouvée.'], 404);
        }

        $currentStatus = $table['reservationStatus'] ?? 'pending';

        if ($currentStatus === $newStatus) {
            return $this->json(['success' => false, 'message' => 'La réservation a déjà ce statut.'], 400);
        }

        $table['reservationStatus'] = $newStatus;

        if ($newStatus === 'cancelled' || $newStatus === 'completed') {
            $table['status'] = 'available';
        } elseif ($newStatus === 'confirmed' || $newStatus === 'reserved') {
            $table['status'] = 'reserved';
        } elseif ($newStatus === 'pending') {
            $table['status'] = 'available';
        }

        $this->firebase->updateTable($key, $table);

        $statusLabels = [
            'pending' => 'En attente',
            'confirmed' => 'Confirmée',
            'cancelled' => 'Annulée',
            'completed' => 'Terminée'
        ];

        $statusLabel = $statusLabels[$newStatus] ?? $newStatus;

        $clientEmail = $table['email'] ?? '';
        if ($clientEmail) {
            $this->firebase->addNotificationByEmail($clientEmail, [
                'type' => 'reservation_status',
                'title' => 'Statut de réservation mis à jour',
                'message' => 'Votre réservation du ' . ($table['date'] ?? '') . ' a été mise à jour : "' . $statusLabel . '".',
                'status' => $newStatus,
                'date' => (new \DateTime())->format('Y-m-d H:i:s'),
                'read' => false,
                'reservationKey' => $key
            ]);
        }

        return $this->json([
            'success' => true,
            'message' => 'Statut changé à "' . $statusLabel . '" avec succès.',
            'newStatus' => $newStatus
        ]);
    }

    #[Route('/{tableId}/cancel', name: 'client_cancel_reservation', methods: ['POST'])]
    public function cancelReservation(string $tableId, Request $request): Response
    {
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('cancel-reservation' . $tableId, $submittedToken)) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('user_profile');
        }

        $user = $request->getSession()->get('user');
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté.');
            return $this->redirectToRoute('app_login');
        }

        $table = $this->firebase->getTable($tableId);
        if (!$table) {
            $this->addFlash('error', 'Réservation non trouvée.');
            return $this->redirectToRoute('user_profile');
        }

        // Vérifier que la réservation appartient à l'utilisateur
        if (($table['email'] ?? '') !== $user['email']) {
            $this->addFlash('error', 'Cette réservation ne vous appartient pas.');
            return $this->redirectToRoute('user_profile');
        }

        $resStatus = $table['reservationStatus'] ?? 'pending';
        if ($resStatus !== 'pending') {
            $this->addFlash('error', 'Seules les réservations en attente peuvent être annulées.');
            return $this->redirectToRoute('user_profile');
        }

        // Vérifier que la date n'est pas passée (comparaison de dates seulement)
        $today = new \DateTime('today');
        $reservationDate = new \DateTime($table['date'] ?? 'now');
        if ($reservationDate < $today) {
            $this->addFlash('error', 'Impossible d\'annuler une réservation passée.');
            return $this->redirectToRoute('user_profile');
        }

        $table['reservationStatus'] = 'cancelled';
        $table['status'] = 'available';
        unset($table['reservation']);

        $this->firebase->updateTable($tableId, $table);

        $clientEmail = $table['email'] ?? '';
        if ($clientEmail) {
            $this->firebase->addNotificationByEmail($clientEmail, [
                'type' => 'reservation_cancelled',
                'title' => 'Réservation annulée',
                'message' => 'Votre réservation du ' . ($table['date'] ?? '') . ' a été annulée.',
                'status' => 'cancelled',
                'date' => (new \DateTime())->format('Y-m-d H:i:s'),
                'read' => false,
                'reservationKey' => $tableId
            ]);
        }

        $this->addFlash('success', 'Votre réservation a été annulée avec succès.');
        return $this->redirectToRoute('user_profile');
    }
}
