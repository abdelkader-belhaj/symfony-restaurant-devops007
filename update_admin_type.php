<?php
// Update user type to admin
try {
    $pdo = new PDO(
        'mysql:host=127.0.0.1;port=3306;dbname=symfresto07;charset=utf8mb4',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $email = 'admin07@gmail.com';
    
    // Update user type to admin
    $sql = 'UPDATE user SET type = :type WHERE email = :email';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':type' => 'admin',
        ':email' => $email
    ]);
    
    if ($stmt->rowCount() > 0) {
        echo "✓ Utilisateur {$email} converti en admin avec succès!\n";
    } else {
        echo "⚠ Utilisateur {$email} non trouvé dans la base de données.\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
    exit(1);
}
