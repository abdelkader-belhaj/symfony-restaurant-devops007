<?php

namespace App\Service;

use App\Entity\Chefs;
use App\Entity\Contact;
use App\Entity\Gallery;
use App\Entity\Menu;
use App\Entity\Order;
use App\Entity\Partties;
use App\Entity\Specials;
use App\Entity\Table;
use App\Entity\User;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class FirebaseService
{
    private ObjectManager $entityManager;

    public function __construct(
        private readonly ManagerRegistry $registry,
    ) {
        $this->entityManager = $this->registry->getManager();
    }

    public function getAllMenus(): array
    {
        $menus = [];

        foreach ($this->repo(Menu::class)->findBy([], ['id' => 'ASC']) as $menu) {
            $menus[(string) $menu->getId()] = $this->menuToArray($menu);
        }

        return $menus;
    }

    public function getMenu(string $key): ?array
    {
        $menu = $this->repo(Menu::class)->find((int) $key);

        return $menu ? $this->menuToArray($menu) : null;
    }

    public function createMenu(array $data): void
    {
        $menu = (new Menu())
            ->setTitre((string) ($data['titre'] ?? $data['title'] ?? ''))
            ->setDescription((string) ($data['description'] ?? ''))
            ->setImage((string) ($data['image'] ?? ''))
            ->setType((string) ($data['type'] ?? ''))
            ->setPrice((string) ($data['price'] ?? '0'));

        $this->persistAndFlush($menu);
    }

    public function updateMenu(string $key, array $data): void
    {
        $menu = $this->repo(Menu::class)->find((int) $key);

        if (!$menu instanceof Menu) {
            return;
        }

        $menu
            ->setTitre((string) ($data['titre'] ?? $data['title'] ?? $menu->getTitre() ?? ''))
            ->setDescription((string) ($data['description'] ?? $menu->getDescription() ?? ''))
            ->setImage((string) ($data['image'] ?? $menu->getImage() ?? ''))
            ->setType((string) ($data['type'] ?? $menu->getType() ?? ''))
            ->setPrice((string) ($data['price'] ?? $menu->getPrice() ?? '0'));

        $this->entityManager->flush();
    }

    public function deleteMenu(string $key): void
    {
        $menu = $this->repo(Menu::class)->find((int) $key);

        if ($menu instanceof Menu) {
            $this->entityManager->remove($menu);
            $this->entityManager->flush();
        }
    }

    public function getAllSpecials(): array
    {
        $specials = [];

        foreach ($this->repo(Specials::class)->findBy([], ['id' => 'ASC']) as $special) {
            $specials[(string) $special->getId()] = $this->specialToArray($special);
        }

        return $specials;
    }

    public function getSpecial(string $key): ?array
    {
        $special = $this->repo(Specials::class)->find((int) $key);

        return $special ? $this->specialToArray($special) : null;
    }

    public function createSpecial(array $data): void
    {
        $special = (new Specials())
            ->setTitre((string) ($data['titre'] ?? $data['title'] ?? ''))
            ->setSousTitre((string) ($data['sousTitre'] ?? ''))
            ->setDescription((string) ($data['description'] ?? ''))
            ->setImage((string) ($data['image'] ?? ''));

        $this->persistAndFlush($special);
    }

    public function updateSpecial(string $key, array $data): void
    {
        $special = $this->repo(Specials::class)->find((int) $key);

        if (!$special instanceof Specials) {
            return;
        }

        $special
            ->setTitre((string) ($data['titre'] ?? $data['title'] ?? $special->getTitre() ?? ''))
            ->setSousTitre((string) ($data['sousTitre'] ?? $special->getSousTitre() ?? ''))
            ->setDescription((string) ($data['description'] ?? $special->getDescription() ?? ''))
            ->setImage((string) ($data['image'] ?? $special->getImage() ?? ''));

        $this->entityManager->flush();
    }

    public function deleteSpecial(string $key): void
    {
        $special = $this->repo(Specials::class)->find((int) $key);

        if ($special instanceof Specials) {
            $this->entityManager->remove($special);
            $this->entityManager->flush();
        }
    }

    public function getAllTables(): array
    {
        $tables = [];

        foreach ($this->repo(Table::class)->findBy([], ['id' => 'ASC']) as $table) {
            $tables[(string) $table->getId()] = $this->tableToArray($table);
        }

        return $tables;
    }

    public function getTable(string $key): ?array
    {
        $table = $this->repo(Table::class)->find((int) $key);

        return $table ? $this->tableToArray($table) : null;
    }

    public function createTable(array $data): void
    {
        $table = (new Table())
            ->setName((string) ($data['name'] ?? ''))
            ->setEmail((string) ($data['email'] ?? ''))
            ->setTel((int) ($data['tel'] ?? $data['phone'] ?? 0))
            ->setDate($this->normalizeDate((string) ($data['date'] ?? 'now')))
            ->setTime($this->normalizeTime((string) ($data['time'] ?? 'now')))
            ->setNumberPeople((int) ($data['numberPeople'] ?? $data['people'] ?? 0))
            ->setMessage((string) ($data['message'] ?? ''))
            ->setStatus((string) ($data['status'] ?? 'available'))
            ->setReservationStatus((string) ($data['reservationStatus'] ?? 'pending'))
            ->setCapacity((int) ($data['capacity'] ?? 2));

        if (isset($data['reservation']) && is_array($data['reservation'])) {
            $table->setReservation($data['reservation']);
        }

        $this->persistAndFlush($table);
    }

    public function updateTable(string $key, array $data): void
    {
        $table = $this->repo(Table::class)->find((int) $key);

        if (!$table instanceof Table) {
            return;
        }

        $table
            ->setName((string) ($data['name'] ?? $table->getName() ?? ''))
            ->setEmail((string) ($data['email'] ?? $table->getEmail() ?? ''))
            ->setTel((int) ($data['tel'] ?? $data['phone'] ?? $table->getTel() ?? 0))
            ->setDate(isset($data['date']) && $data['date'] !== '' ? $this->normalizeDate((string) $data['date']) : ($table->getDate() ?? $this->normalizeDate('now')))
            ->setTime(isset($data['time']) && $data['time'] !== '' ? $this->normalizeTime((string) $data['time']) : ($table->getTime() ?? $this->normalizeTime('now')))
            ->setNumberPeople((int) ($data['numberPeople'] ?? $data['people'] ?? $table->getNumberPeople() ?? 0))
            ->setMessage((string) ($data['message'] ?? $table->getMessage() ?? ''))
            ->setStatus((string) ($data['status'] ?? $table->getStatus() ?? 'available'))
            ->setReservationStatus((string) ($data['reservationStatus'] ?? $table->getReservationStatus() ?? 'pending'))
            ->setCapacity((int) ($data['capacity'] ?? $table->getCapacity() ?? 2));

        if (array_key_exists('reservation', $data)) {
            $table->setReservation(is_array($data['reservation']) ? $data['reservation'] : null);
        }

        $this->entityManager->flush();
    }

    public function deleteTable(string $key): void
    {
        $table = $this->repo(Table::class)->find((int) $key);

        if ($table instanceof Table) {
            $this->entityManager->remove($table);
            $this->entityManager->flush();
        }
    }

    public function getAllChefs(): array
    {
        $chefs = [];

        foreach ($this->repo(Chefs::class)->findBy([], ['id' => 'ASC']) as $chef) {
            $chefs[(string) $chef->getId()] = $this->chefToArray($chef);
        }

        return $chefs;
    }

    public function getChef(string $key): ?array
    {
        $chef = $this->repo(Chefs::class)->find((int) $key);

        return $chef ? $this->chefToArray($chef) : null;
    }

    public function createChef(array $data): void
    {
        $chef = (new Chefs())
            ->setNom((string) ($data['nom'] ?? ''))
            ->setTitre((string) ($data['titre'] ?? ''))
            ->setImage((string) ($data['image'] ?? ''));

        $this->persistAndFlush($chef);
    }

    public function updateChef(string $key, array $data): void
    {
        $chef = $this->repo(Chefs::class)->find((int) $key);

        if (!$chef instanceof Chefs) {
            return;
        }

        $chef
            ->setNom((string) ($data['nom'] ?? $chef->getNom() ?? ''))
            ->setTitre((string) ($data['titre'] ?? $chef->getTitre() ?? ''))
            ->setImage((string) ($data['image'] ?? $chef->getImage() ?? ''));

        $this->entityManager->flush();
    }

    public function deleteChef(string $key): void
    {
        $chef = $this->repo(Chefs::class)->find((int) $key);

        if ($chef instanceof Chefs) {
            $this->entityManager->remove($chef);
            $this->entityManager->flush();
        }
    }

    public function getAllContacts(): array
    {
        $contacts = [];

        foreach ($this->repo(Contact::class)->findBy([], ['id' => 'ASC']) as $contact) {
            $contacts[(string) $contact->getId()] = $this->contactToArray($contact);
        }

        return $contacts;
    }

    public function getContact(string $key): ?array
    {
        $contact = $this->repo(Contact::class)->find((int) $key);

        return $contact ? $this->contactToArray($contact) : null;
    }

    public function createContact(array $data): void
    {
        $contact = (new Contact())
            ->setNom((string) ($data['name'] ?? $data['nom'] ?? ''))
            ->setEmail((string) ($data['email'] ?? ''))
            ->setSubject((string) ($data['subject'] ?? ''))
            ->setMessage((string) ($data['message'] ?? ''));

        $this->persistAndFlush($contact);
    }

    public function updateContact(string $key, array $data): void
    {
        $contact = $this->repo(Contact::class)->find((int) $key);

        if (!$contact instanceof Contact) {
            return;
        }

        $contact
            ->setNom((string) ($data['name'] ?? $data['nom'] ?? $contact->getNom() ?? ''))
            ->setEmail((string) ($data['email'] ?? $contact->getEmail() ?? ''))
            ->setSubject((string) ($data['subject'] ?? $contact->getSubject() ?? ''))
            ->setMessage((string) ($data['message'] ?? $contact->getMessage() ?? ''));

        $this->entityManager->flush();
    }

    public function deleteContact(string $key): void
    {
        $contact = $this->repo(Contact::class)->find((int) $key);

        if ($contact instanceof Contact) {
            $this->entityManager->remove($contact);
            $this->entityManager->flush();
        }
    }

    public function getAllGalleries(): array
    {
        $galleries = [];

        foreach ($this->repo(Gallery::class)->findBy([], ['id' => 'ASC']) as $gallery) {
            $galleries[(string) $gallery->getId()] = $this->galleryToArray($gallery);
        }

        return $galleries;
    }

    public function getGallery(string $key): ?array
    {
        $gallery = $this->repo(Gallery::class)->find((int) $key);

        return $gallery ? $this->galleryToArray($gallery) : null;
    }

    public function createGallery(array $data): void
    {
        $gallery = (new Gallery())->setImage((string) ($data['image'] ?? ''));
        $this->persistAndFlush($gallery);
    }

    public function updateGallery(string $key, array $data): void
    {
        $gallery = $this->repo(Gallery::class)->find((int) $key);

        if (!$gallery instanceof Gallery) {
            return;
        }

        $gallery->setImage((string) ($data['image'] ?? $gallery->getImage() ?? ''));
        $this->entityManager->flush();
    }

    public function deleteGallery(string $key): void
    {
        $gallery = $this->repo(Gallery::class)->find((int) $key);

        if ($gallery instanceof Gallery) {
            $this->entityManager->remove($gallery);
            $this->entityManager->flush();
        }
    }

    public function getAllPartties(): array
    {
        $partties = [];

        foreach ($this->repo(Partties::class)->findBy([], ['id' => 'ASC']) as $parttie) {
            $partties[(string) $parttie->getId()] = $this->parttieToArray($parttie);
        }

        return $partties;
    }

    public function getParttie(string $key): ?array
    {
        $parttie = $this->repo(Partties::class)->find((int) $key);

        return $parttie ? $this->parttieToArray($parttie) : null;
    }

    public function createParttie(array $data): void
    {
        $parttie = (new Partties())
            ->setTitre((string) ($data['titre'] ?? ''))
            ->setPrice((int) ($data['price'] ?? 0))
            ->setDebut((string) ($data['debut'] ?? ''))
            ->setLigne1((string) ($data['ligne1'] ?? ''))
            ->setLigne2((string) ($data['ligne2'] ?? ''))
            ->setLigne3((string) ($data['ligne3'] ?? ''))
            ->setFinal((string) ($data['final'] ?? ''))
            ->setImage((string) ($data['image'] ?? ''));

        $this->persistAndFlush($parttie);
    }

    public function updateParttie(string $key, array $data): void
    {
        $parttie = $this->repo(Partties::class)->find((int) $key);

        if (!$parttie instanceof Partties) {
            return;
        }

        $parttie
            ->setTitre((string) ($data['titre'] ?? $parttie->getTitre() ?? ''))
            ->setPrice((int) ($data['price'] ?? $parttie->getPrice() ?? 0))
            ->setDebut((string) ($data['debut'] ?? $parttie->getDebut() ?? ''))
            ->setLigne1((string) ($data['ligne1'] ?? $parttie->getLigne1() ?? ''))
            ->setLigne2((string) ($data['ligne2'] ?? $parttie->getLigne2() ?? ''))
            ->setLigne3((string) ($data['ligne3'] ?? $parttie->getLigne3() ?? ''))
            ->setFinal((string) ($data['final'] ?? $parttie->getFinal() ?? ''))
            ->setImage((string) ($data['image'] ?? $parttie->getImage() ?? ''));

        $this->entityManager->flush();
    }

    public function deleteParttie(string $key): void
    {
        $parttie = $this->repo(Partties::class)->find((int) $key);

        if ($parttie instanceof Partties) {
            $this->entityManager->remove($parttie);
            $this->entityManager->flush();
        }
    }

    public function createOrder(array $data): void
    {
        $order = new Order();
        $orderSummary = $data['orderSummary'] ?? [];
        $customerInfo = $data['customerInfo'] ?? [];

        $order
            ->setItems(is_array($data['items'] ?? null) ? $data['items'] : [])
            ->setOrderType((string) ($data['orderType'] ?? 'Sur place'))
            ->setTotalPrice((float) ($orderSummary['totalAmount'] ?? $data['totalPrice'] ?? 0))
            ->setTotalItems((int) ($orderSummary['totalQuantity'] ?? $data['totalItems'] ?? 0))
            ->setIsDelivery($this->isDeliveryOrder((string) ($data['orderType'] ?? '')))
            ->setDeliveryAddress((string) ($customerInfo['address'] ?? $data['deliveryAddress'] ?? ''))
            ->setCustomerName((string) ($customerInfo['name'] ?? $data['customerName'] ?? ''))
            ->setCustomerPhone((string) ($customerInfo['phone'] ?? $data['customerPhone'] ?? ''))
            ->setTableNumber(isset($customerInfo['tableNumber']) && $customerInfo['tableNumber'] !== '' ? (int) $customerInfo['tableNumber'] : (isset($data['tableNumber']) ? (int) $data['tableNumber'] : null))
            ->setStatus((string) ($data['status'] ?? $orderSummary['status'] ?? 'pending'))
            ->setCreatedAt($this->normalizeDateTime((string) ($data['orderDate'] ?? 'now')));

        $this->persistAndFlush($order);
    }

    public function getAllOrders(): array
    {
        $orders = [];

        foreach ($this->repo(Order::class)->findBy([], ['id' => 'ASC']) as $order) {
            $orders[(string) $order->getId()] = $this->orderToArray($order);
        }

        return $orders;
    }

    public function getClientRewardSummary(string $customerName): array
    {
        $normalizedCustomerName = mb_strtolower(trim($customerName));
        $totalSpent = 0.0;
        $eligibleOrderCount = 0;

        foreach ($this->getAllOrders() as $order) {
            $orderCustomerName = mb_strtolower(trim((string) ($order['customerName'] ?? $order['customerInfo']['name'] ?? '')));
            if ($orderCustomerName !== $normalizedCustomerName) {
                continue;
            }

            $status = mb_strtolower(trim((string) ($order['orderSummary']['status'] ?? $order['status'] ?? 'pending')));
            if (!in_array($status, ['validated', 'delivered'], true)) {
                continue;
            }

            $totalSpent += (float) ($order['orderSummary']['totalAmount'] ?? $order['total'] ?? 0);
            $eligibleOrderCount++;
        }

        $threshold = 500.0;
        $displayThreshold = $threshold + 0.01;
        $progress = min($totalSpent, $displayThreshold);

        return [
            'totalSpent' => $totalSpent,
            'eligibleOrderCount' => $eligibleOrderCount,
            'threshold' => $threshold,
            'remaining' => max($displayThreshold - $totalSpent, 0),
            'progressPercent' => $displayThreshold > 0 ? min(($progress / $displayThreshold) * 100, 100) : 0,
            'rewardAvailable' => $totalSpent > $threshold,
            'discountRate' => 25,
            'rouletteThreshold' => 1000.0,
            'rouletteAvailable' => $totalSpent > 1000.0,
            'rouletteRewardCount' => 8,
            'nextRewardText' => $totalSpent > $threshold
                ? 'Récompense disponible'
                : sprintf('Encore %.2f $ à dépasser pour le cadeau', max(($threshold + 0.01) - $totalSpent, 0)),
        ];
    }

    public function getRewardWheelMenus(int $limit = 6): array
    {
        $menus = array_values($this->getAllMenus());

        if ($menus === []) {
            return [];
        }

        shuffle($menus);

        return array_slice($menus, 0, min($limit, count($menus)));
    }

    public function resolveRoulettePool(SessionInterface $session, int $limit = 6): array
    {
        $pool = $session->get('gift_roulette_pool');

        if (!is_array($pool) || $pool === []) {
            $pool = $this->getRewardWheelMenus($limit);
            $session->set('gift_roulette_pool', $pool);
        }

        return $pool;
    }

    public function getOrder(string $key): ?array
    {
        $order = $this->repo(Order::class)->find((int) $key);

        return $order ? $this->orderToArray($order) : null;
    }

    public function updateOrder(string $key, array $data): void
    {
        $order = $this->repo(Order::class)->find((int) $key);

        if (!$order instanceof Order) {
            return;
        }

        $orderSummary = $data['orderSummary'] ?? [];
        $customerInfo = $data['customerInfo'] ?? [];

        $order
            ->setItems(is_array($data['items'] ?? null) ? $data['items'] : $order->getItems())
            ->setOrderType((string) ($data['orderType'] ?? $order->getOrderType() ?? 'Sur place'))
            ->setTotalPrice((float) ($orderSummary['totalAmount'] ?? $data['totalPrice'] ?? $order->getTotalPrice() ?? 0))
            ->setTotalItems((int) ($orderSummary['totalQuantity'] ?? $data['totalItems'] ?? $order->getTotalItems() ?? 0))
            ->setIsDelivery($this->isDeliveryOrder((string) ($data['orderType'] ?? $order->getOrderType() ?? '')))
            ->setDeliveryAddress((string) ($customerInfo['address'] ?? $data['deliveryAddress'] ?? $order->getDeliveryAddress() ?? ''))
            ->setCustomerName((string) ($customerInfo['name'] ?? $data['customerName'] ?? $order->getCustomerName() ?? ''))
            ->setCustomerPhone((string) ($customerInfo['phone'] ?? $data['customerPhone'] ?? $order->getCustomerPhone() ?? ''))
            ->setTableNumber(isset($customerInfo['tableNumber']) && $customerInfo['tableNumber'] !== '' ? (int) $customerInfo['tableNumber'] : (isset($data['tableNumber']) ? (int) $data['tableNumber'] : $order->getTableNumber()))
            ->setStatus((string) ($data['status'] ?? $orderSummary['status'] ?? $order->getStatus() ?? 'pending'));

        if (isset($data['orderDate'])) {
            $order->setCreatedAt($this->normalizeDateTime((string) $data['orderDate']));
        }

        $this->entityManager->flush();
    }

    public function deleteOrder(string $key): void
    {
        $order = $this->repo(Order::class)->find((int) $key);

        if ($order instanceof Order) {
            $this->entityManager->remove($order);
            $this->entityManager->flush();
        }
    }

    public function createUser(array $data): void
    {
        $user = (new User())
            ->setNomComplete((string) ($data['nomComplete'] ?? ''))
            ->setTel((int) ($data['tel'] ?? 0))
            ->setEmail((string) ($data['email'] ?? ''))
            ->setPwd((string) ($data['pwd'] ?? ''))
            ->setType((string) ($data['type'] ?? 'client'))
            ->setProvider((string) ($data['provider'] ?? 'local'))
            ->setRouletteUsed((bool) ($data['rouletteUsed'] ?? false))
            ->setRouletteGift(is_array($data['rouletteGift'] ?? null) ? $data['rouletteGift'] : null)
            ->setDiscountActive((bool) ($data['discountActive'] ?? false))
            ->setDiscountUsed((bool) ($data['discountUsed'] ?? false))
            ->setPoints((int) ($data['points'] ?? 0));

        $this->persistAndFlush($user);
    }

    public function createGoogleUser(string $googleId, string $email, string $nomComplete): void
    {
        $user = (new User())
            ->setNomComplete($nomComplete)
            ->setTel(null)
            ->setEmail($email)
            ->setPwd(null)
            ->setGoogleId($googleId)
            ->setProvider('google')
            ->setType('client')
            ->setRouletteUsed(false)
            ->setRouletteGift(null)
            ->setDiscountActive(false)
            ->setDiscountUsed(false)
            ->setPoints(0);

        $this->persistAndFlush($user);
    }

    public function updateGoogleUser(string $key, string $googleId, string $email, string $nomComplete): void
    {
        $user = $this->repo(User::class)->find((int) $key);

        if (!$user instanceof User) {
            return;
        }

        $user
            ->setGoogleId($googleId)
            ->setEmail($email)
            ->setNomComplete($nomComplete);

        $this->entityManager->flush();
    }

    public function getAllUsers(): array
    {
        $users = [];

        foreach ($this->repo(User::class)->findBy([], ['id' => 'ASC']) as $user) {
            $users[(string) $user->getId()] = $this->userToArray($user);
        }

        return $users;
    }

    public function getUser(string $key): ?array
    {
        $user = $this->repo(User::class)->find((int) $key);

        return $user ? $this->userToArray($user) : null;
    }

    public function updateUser(string $key, array $data): void
    {
        $user = $this->repo(User::class)->find((int) $key);

        if (!$user instanceof User) {
            return;
        }

        $user
            ->setNomComplete((string) ($data['nomComplete'] ?? $user->getNomComplete() ?? ''))
            ->setEmail((string) ($data['email'] ?? $user->getEmail() ?? ''))
            ->setType((string) ($data['type'] ?? $user->getType() ?? 'client'));

        if (array_key_exists('tel', $data)) {
            $user->setTel($data['tel'] !== null && $data['tel'] !== '' ? (int) $data['tel'] : null);
        }

        if (array_key_exists('pwd', $data)) {
            $user->setPwd((string) $data['pwd']);
        }

        if (array_key_exists('rouletteUsed', $data)) {
            $user->setRouletteUsed((bool) $data['rouletteUsed']);
        }

        if (array_key_exists('rouletteGift', $data)) {
            $user->setRouletteGift(is_array($data['rouletteGift']) ? $data['rouletteGift'] : null);
        }

        if (array_key_exists('discountActive', $data)) {
            $user->setDiscountActive((bool) $data['discountActive']);
        }

        if (array_key_exists('discountUsed', $data)) {
            $user->setDiscountUsed((bool) $data['discountUsed']);
        }

        if (array_key_exists('notifications', $data)) {
            $user->setNotifications(is_array($data['notifications']) ? $data['notifications'] : []);
        }

        if (array_key_exists('points', $data)) {
            $user->setPoints((int) $data['points']);
        }

        $this->entityManager->flush();
    }

    public function deleteUser(string $key): void
    {
        $user = $this->repo(User::class)->find((int) $key);

        if ($user instanceof User) {
            $this->entityManager->remove($user);
            $this->entityManager->flush();
        }
    }

    public function getUserByEmail(string $email): ?array
    {
        $user = $this->repo(User::class)->findOneBy(['email' => $email]);

        if (!$user instanceof User) {
            return null;
        }

        return ['key' => (string) $user->getId()] + $this->userToArray($user);
    }

    public function addPointsByEmail(string $email, int $points): void
    {
        $user = $this->repo(User::class)->findOneBy(['email' => $email]);
        if (!$user instanceof User) {
            return;
        }
        $user->addPoints($points);
        $this->entityManager->flush();
    }

    public function addNotificationByEmail(string $email, array $notification): void
    {
        $userData = $this->getUserByEmail($email);
        if (!$userData || !isset($userData['key'])) {
            return;
        }

        $user = $this->repo(User::class)->find((int) $userData['key']);
        if (!$user instanceof User) {
            return;
        }

        $notifications = $user->getNotifications() ?? [];
        $notifications[] = $notification;
        $user->setNotifications($notifications);

        $this->entityManager->flush();
    }

    public function getAllAdmins(): array
    {
        return array_filter($this->getAllUsers(), static fn (array $user): bool => ($user['type'] ?? '') === 'admin');
    }

    public function getAllClients(): array
    {
        return array_filter($this->getAllUsers(), static fn (array $user): bool => !isset($user['type']) || $user['type'] === 'client');
    }

    private function persistAndFlush(object $entity): void
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    private function repo(string $class): ObjectRepository
    {
        return $this->registry->getRepository($class);
    }

    private function menuToArray(Menu $menu): array
    {
        return [
            'key' => (string) $menu->getId(),
            'id' => $menu->getId(),
            'titre' => $menu->getTitre(),
            'title' => $menu->getTitre(),
            'name' => $menu->getTitre(),
            'description' => $menu->getDescription(),
            'image' => $menu->getImage(),
            'type' => $menu->getType(),
            'price' => $menu->getPrice(),
        ];
    }

    private function specialToArray(Specials $special): array
    {
        return [
            'key' => (string) $special->getId(),
            'id' => $special->getId(),
            'titre' => $special->getTitre(),
            'title' => $special->getTitre(),
            'sousTitre' => $special->getSousTitre(),
            'description' => $special->getDescription(),
            'image' => $special->getImage(),
        ];
    }

    private function tableToArray(Table $table): array
    {
        return [
            'key' => (string) $table->getId(),
            'id' => $table->getId(),
            'number' => $table->getId(),
            'name' => $table->getName(),
            'email' => $table->getEmail(),
            'tel' => $table->getTel(),
            'date' => $table->getDate()?->format('Y-m-d'),
            'time' => $table->getTime()?->format('H:i:s'),
            'numberPeople' => $table->getNumberPeople(),
            'persons' => $table->getNumberPeople(),
            'message' => $table->getMessage(),
            'status' => $table->getStatus() ?? 'available',
            'reservationStatus' => $table->getReservationStatus() ?? 'pending',
            'capacity' => $table->getCapacity() ?? 2,
            'reservation' => $table->getReservation(),
        ];
    }

    private function chefToArray(Chefs $chef): array
    {
        return [
            'key' => (string) $chef->getId(),
            'id' => $chef->getId(),
            'nom' => $chef->getNom(),
            'name' => $chef->getNom(),
            'titre' => $chef->getTitre(),
            'image' => $chef->getImage(),
        ];
    }

    private function contactToArray(Contact $contact): array
    {
        return [
            'key' => (string) $contact->getId(),
            'id' => $contact->getId(),
            'nom' => $contact->getNom(),
            'name' => $contact->getNom(),
            'email' => $contact->getEmail(),
            'subject' => $contact->getSubject(),
            'message' => $contact->getMessage(),
        ];
    }

    private function galleryToArray(Gallery $gallery): array
    {
        return [
            'key' => (string) $gallery->getId(),
            'id' => $gallery->getId(),
            'image' => $gallery->getImage(),
        ];
    }

    private function parttieToArray(Partties $parttie): array
    {
        return [
            'key' => (string) $parttie->getId(),
            'id' => $parttie->getId(),
            'titre' => $parttie->getTitre(),
            'price' => $parttie->getPrice(),
            'debut' => $parttie->getDebut(),
            'ligne1' => $parttie->getLigne1(),
            'ligne2' => $parttie->getLigne2(),
            'ligne3' => $parttie->getLigne3(),
            'final' => $parttie->getFinal(),
            'image' => $parttie->getImage(),
        ];
    }

    private function orderToArray(Order $order): array
    {
        $orderSummary = [
            'totalQuantity' => $order->getTotalItems(),
            'subtotal' => $order->getTotalPrice(),
            'deliveryFee' => 0,
            'totalAmount' => $order->getTotalPrice(),
            'status' => $order->getStatus(),
        ];

        return [
            'key' => (string) $order->getId(),
            'id' => $order->getId(),
            'items' => $order->getItems(),
            'orderSummary' => $orderSummary,
            'orderType' => $order->getOrderType(),
            'status' => $order->getStatus(),
            'orderDate' => $order->getCreatedAt()?->format('Y-m-d H:i:s'),
            'timestamp' => $order->getCreatedAt()?->getTimestamp(),
            'customerInfo' => [
                'name' => $order->getCustomerName(),
                'phone' => $order->getCustomerPhone(),
                'address' => $order->getDeliveryAddress(),
                'tableNumber' => $order->getTableNumber(),
            ],
            'customerName' => $order->getCustomerName(),
            'phone' => $order->getCustomerPhone(),
            'address' => $order->getDeliveryAddress(),
            'tableNumber' => $order->getTableNumber(),
            'total' => $order->getTotalPrice(),
        ];
    }

    private function userToArray(User $user): array
    {
        return [
            'key' => (string) $user->getId(),
            'id' => $user->getId(),
            'type' => $user->getType(),
            'nomComplete' => $user->getNomComplete(),
            'tel' => $user->getTel(),
            'email' => $user->getEmail(),
            'pwd' => $user->getPwd(),
            'googleId' => $user->getGoogleId(),
            'provider' => $user->getProvider(),
            'rouletteUsed' => (bool) $user->isRouletteUsed(),
            'rouletteGift' => $user->getRouletteGift(),
            'discountActive' => (bool) $user->isDiscountActive(),
            'discountUsed' => (bool) $user->isDiscountUsed(),
            'notifications' => $user->getNotifications() ?? [],
            'points' => $user->getPoints() ?? 0,
        ];
    }

    private function normalizeDate(string $value): DateTime
    {
        return new DateTime($value);
    }

    private function normalizeTime(string $value): DateTime
    {
        return new DateTime($value);
    }

    private function normalizeDateTime(string $value): DateTime
    {
        return new DateTime($value);
    }

    private function isDeliveryOrder(string $orderType): bool
    {
        $normalized = mb_strtolower(trim($orderType));

        return in_array($normalized, ['delivery', 'livraison', 'à emporter', 'a emporter'], true);
    }
}
