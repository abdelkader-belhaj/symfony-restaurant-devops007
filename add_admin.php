<?php

use App\Kernel;

require __DIR__ . '/vendor/autoload.php';

// Boot kernel (env comes from container environment when run inside Docker)
$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'prod', (bool) ($_SERVER['APP_DEBUG'] ?? false));
$kernel->boot();

$container = $kernel->getContainer();

/** @var \App\Service\FirebaseService $firebase */
$firebase = $container->get(\App\Service\FirebaseService::class);

$email = 'admin@gmail.com';
$plain = 'admin@321';

// Check existing
$existing = $firebase->getUserByEmail($email);
if ($existing !== null) {
    echo "Admin user already exists: {$email}\n";
    exit(0);
}

$hash = password_hash($plain, PASSWORD_DEFAULT);

$data = [
    'nomComplete' => 'Admin',
    'tel' => null,
    'email' => $email,
    'pwd' => $hash,
    'type' => 'admin',
    'provider' => 'local',
];

$firebase->createUser($data);

echo "Admin user created: {$email}\n";
