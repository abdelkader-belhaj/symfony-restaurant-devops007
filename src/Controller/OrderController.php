<?php

namespace App\Controller;

use App\Service\FirebaseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

final class OrderController extends AbstractController
{
    #[Route('/menuC', name: 'app_menu_commande')]
    public function index(Request $request, FirebaseService $firebaseService): Response
    {
        $menus = $firebaseService->getAllMenus();
        $session = $request->getSession();
        $user = $session->get('user');
        $storedUser = !empty($user['id']) ? $firebaseService->getUser((string) $user['id']) : null;
        $summary = $storedUser ? $firebaseService->getClientRewardSummary((string) ($storedUser['nomComplete'] ?? '')) : [];
        $discountActive = (bool) (
            $storedUser
            && !($storedUser['discountUsed'] ?? false)
            && (($storedUser['discountActive'] ?? false) || ($summary['rewardAvailable'] ?? false))
        );

        if ($discountActive && !($storedUser['discountActive'] ?? false) && !empty($storedUser['id'])) {
            $firebaseService->updateUser((string) $storedUser['id'], [
                'discountActive' => true,
            ]);
        }

        if ($discountActive) {
            $session->set('gift_discount_active', true);
            $session->set('gift_discount_rate', 25);
        } else {
            $session->remove('gift_discount_active');
            $session->remove('gift_discount_rate');
        }

        $menusByType = [];
        $featuredItems = [];

        if ($menus) {
            foreach ($menus as $key => $menu) {
                if (isset($menu['type'])) {
                    $type = strtolower($menu['type']);
                    $menuWithSlug = array_merge($menu, [
                        'slug' => $key,
                        'image' => $menu['image'] ?? null,
                        'name' => $menu['name'] ?? ($menu['titre'] ?? '')
                    ]);
                    $menusByType[$type][] = $menuWithSlug;
                    
                    // Ajoutez les 3 premiers menus comme featured
                    if (count($featuredItems) < 3) {
                        $featuredItems[] = $menuWithSlug;
                    }
                }
            }
        }

        return $this->render('order/indexFront.html.twig', [
            'menusByType' => $menusByType,
            'featuredItems' => $featuredItems,
            'giftDiscountActive' => $discountActive,
            'giftDiscountRate' => (int) $session->get('gift_discount_rate', 25),
            'rouletteGift' => $session->get('gift_roulette_order_gift'),
            'autoConfirmRouletteGift' => $request->query->getBoolean('rouletteGift') && is_array($session->get('gift_roulette_order_gift')),
        ]);
    }
    #[Route('/admin/orders', name: 'admin_orders')]
    public function adminOrders(FirebaseService $firebaseService): Response
    {
        $orders = $firebaseService->getAllOrders();

        // On veut les commandes les plus récentes en haut
        $orders = array_reverse($orders, true);

        return $this->render('order/admin_orders.html.twig', [
            'orders' => $orders
        ]);
    }

    #[Route('/api/orders', name: 'api_create_order', methods: ['POST'])]
    public function createOrder(Request $request, FirebaseService $firebaseService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return new JsonResponse(['error' => 'Invalid data'], 400);
        }

        // Debug: Afficher les données reçues
        error_log('Données reçues : ' . print_r($data, true));

        // Formater et enrichir les données de la commande
        $session = $request->getSession();
        $rouletteGift = $session->get('gift_roulette_order_gift');
        $hasRouletteGiftItem = false;
        $user = $session->get('user');
        $storedUser = !empty($user['id']) ? $firebaseService->getUser((string) $user['id']) : null;

        $giftRequested = array_filter($data['items'] ?? [], static function (array $item): bool {
            return (bool) ($item['isRouletteGift'] ?? false);
        });

        if ($giftRequested && !is_array($rouletteGift)) {
            return new JsonResponse([
                'error' => 'Roulette gift unavailable',
                'message' => 'Aucun cadeau roulette disponible pour cette commande.',
            ], Response::HTTP_CONFLICT);
        }

        $items = array_map(function($item) use ($rouletteGift, &$hasRouletteGiftItem) {
            if ((bool) ($item['isRouletteGift'] ?? false)) {
                $hasRouletteGiftItem = true;
                $giftId = (string) ($rouletteGift['id'] ?? $rouletteGift['key'] ?? 'roulette-gift');
                $giftTitle = (string) ($rouletteGift['titre'] ?? $rouletteGift['name'] ?? $rouletteGift['title'] ?? 'Plat cadeau');

                return [
                    'id' => 'gift-' . $giftId,
                    'title' => $giftTitle,
                    'quantity' => 1,
                    'unitPrice' => 0,
                    'totalPrice' => 0,
                    'category' => (string) ($rouletteGift['type'] ?? 'Cadeau roulette'),
                    'image' => (string) ($rouletteGift['image'] ?? ''),
                    'customizations' => [],
                    'isRouletteGift' => true,
                    'giftLabel' => 'Cadeau roulette gratuit',
                ];
            }

            return [
                'id' => $item['id'] ?? '',
                'title' => $item['title'] ?? '',
                'quantity' => $item['quantity'] ?? 0,
                'unitPrice' => $item['unitPrice'] ?? 0,
                'totalPrice' => $item['totalPrice'] ?? 0,
                'category' => $item['category'] ?? '',
                'image' => $item['image'] ?? '',
                'customizations' => $item['customizations'] ?? []
            ];
        }, $data['items'] ?? []);

        $serverSubtotal = array_reduce($items, static function (float $sum, array $item): float {
            return $sum + (float) ($item['totalPrice'] ?? 0);
        }, 0.0);
        $deliveryFee = (float) ($data['orderSummary']['deliveryFee'] ?? 0);
        $totalBeforeDiscount = $serverSubtotal + $deliveryFee;
        $summary = $storedUser ? $firebaseService->getClientRewardSummary((string) ($storedUser['nomComplete'] ?? '')) : [];
        $discountApplies = (bool) (
            $storedUser
            && $totalBeforeDiscount > 0
            && !($storedUser['discountUsed'] ?? false)
            && (($storedUser['discountActive'] ?? false) || ($summary['rewardAvailable'] ?? false))
        );
        $discountRate = 25;
        $discountAmount = $discountApplies ? ($totalBeforeDiscount * $discountRate / 100) : 0;
        $serverTotal = max($totalBeforeDiscount - $discountAmount, 0);

        $formattedData = [
            'items' => $items,
            'orderSummary' => [
                'totalQuantity' => $data['orderSummary']['totalQuantity'] ?? 0,
                'subtotal' => $serverSubtotal,
                'deliveryFee' => $deliveryFee,
                'discountRate' => $discountApplies ? $discountRate : 0,
                'discountAmount' => $discountAmount,
                'totalAmount' => $hasRouletteGiftItem && count($items) === 1 ? 0 : $serverTotal,
                'status' => 'pending'
            ],
            'orderType' => $data['orderType'] ?? 'Sur place',
            'status' => 'pending',
            'orderDate' => (new \DateTime())->format('Y-m-d H:i:s'),
            'customerInfo' => [
                'name' => $data['customerInfo']['name'] ?? '',
                'phone' => $data['customerInfo']['phone'] ?? '',
                'address' => $data['customerInfo']['address'] ?? '',
                'tableNumber' => $data['customerInfo']['tableNumber'] ?? ''
            ]
        ];

        try {
            // Debug: Afficher les données formatées
            error_log('Données formatées : ' . print_r($formattedData, true));
            
            // Utiliser le FirebaseService pour créer la commande
            $firebaseService->createOrder($formattedData);

            if ($hasRouletteGiftItem) {
                $user = $session->get('user');
                if (!empty($user['id'])) {
                    $firebaseService->updateUser((string) $user['id'], [
                        'rouletteUsed' => true,
                    ]);
                }

                $session->remove('gift_roulette_order_gift');
                $session->remove('gift_roulette_result');
            }

            if ($discountApplies && !empty($storedUser['id'])) {
                $firebaseService->updateUser((string) $storedUser['id'], [
                    'discountActive' => false,
                    'discountUsed' => true,
                ]);
            }

            $session->remove('gift_discount_active');
            $session->remove('gift_discount_rate');
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => $formattedData
            ], 201);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Failed to create order',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/admin/orders/{orderId}/status', name: 'admin_update_order_status', methods: ['POST'])]
    public function updateOrderStatus(string $orderId, Request $request, FirebaseService $firebaseService): Response
    {
        $newStatus = $request->request->get('status');
        if (!$newStatus) {
            return $this->redirectToRoute('app_dashboard');
        }

        try {
            $order = $firebaseService->getOrder($orderId);
            
            if ($order) {
                // Mettre à jour le statut dans la commande
                $order['status'] = $newStatus;
                
                // S'assurer que orderSummary existe
                if (!isset($order['orderSummary'])) {
                    $order['orderSummary'] = [];
                }
                
                // Mettre à jour le statut dans orderSummary également
                $order['orderSummary']['status'] = $newStatus;
                
                // Mettre à jour la date de modification
                $order['lastModified'] = (new \DateTime())->format('Y-m-d H:i:s');
                
                // Préparer le message approprié
                $messages = [
                    'validated' => 'La commande a été validée avec succès.',
                    'delivered' => 'La commande a été marquée comme livrée.',
                    'rejected' => 'La commande a été refusée.',
                    'pending' => 'La commande a été remise en attente.'
                ];

                // Sauvegarder les modifications
                $firebaseService->updateOrder($orderId, $order);
                
                // Ajouter le message flash approprié
                $message = $messages[$newStatus] ?? 'Le statut de la commande a été mis à jour.';
                $this->addFlash($newStatus === 'rejected' ? 'warning' : 'success', $message);
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la mise à jour du statut.');
        }

        // Rediriger vers le tableau de bord
        return $this->redirectToRoute('app_dashboard');
    }

    #[Route('/admin/orders/new', name: 'admin_new_order')]
    public function newOrder(Request $request, FirebaseService $firebaseService): Response
    {
        // Récupérer tous les plats du menu
        $menus = $firebaseService->getAllMenus();
        
        $menuItems = [];
        foreach ($menus as $key => $menu) {
            $menuItems[$key] = [
                'title' => $menu['name'] ?? ($menu['titre'] ?? ''),
                'category' => $menu['type'] ?? '',
                'price' => $menu['price'] ?? 0,
                'description' => $menu['description'] ?? ''
            ];
        }

        if ($request->isMethod('POST')) {
            $formData = $request->request->all();
            
            // Récupérer les détails du plat sélectionné
            $selectedMenuItem = $menuItems[$formData['menuItem']] ?? null;
            
            // Calculer le prix total
            $unitPrice = $selectedMenuItem ? floatval($selectedMenuItem['price']) : floatval($formData['unitPrice']);
            $totalPrice = $unitPrice * intval($formData['quantity']);
            
            $orderData = [
                'items' => [[
                    'id' => $formData['menuItem'],
                    'title' => $selectedMenuItem ? $selectedMenuItem['title'] : $formData['title'],
                    'quantity' => intval($formData['quantity']),
                    'unitPrice' => $unitPrice,
                    'totalPrice' => $totalPrice,
                    'category' => $selectedMenuItem ? $selectedMenuItem['category'] : $formData['category']
                ]],
                'orderSummary' => [
                    'totalQuantity' => intval($formData['quantity']),
                    'subtotal' => $totalPrice,
                    'deliveryFee' => 0,
                    'totalAmount' => $totalPrice
                ],
                'orderType' => $formData['orderType'],
                'status' => 'pending',
                'orderDate' => (new \DateTime())->format('Y-m-d H:i:s'),
                'customerName' => $formData['customerName'],
                'phone' => $formData['phone'],
                'address' => $formData['address'],
                'tableNumber' => $formData['tableNumber']
            ];

            $firebaseService->createOrder($orderData);

            return $this->redirectToRoute('admin_orders');
        }

        return $this->render('order/new_order.html.twig', [
            'menuItems' => $menuItems
        ]);
    }

    #[Route('/admin/orders/{orderId}/delete', name: 'admin_delete_order', methods: ['POST'])]
    public function deleteOrder(string $orderId, FirebaseService $firebaseService): Response
    {
        $firebaseService->deleteOrder($orderId);
        
        $this->addFlash('success', 'La commande a été supprimée avec succès.');
        return $this->redirectToRoute('admin_orders');
    }
}
