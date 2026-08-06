<?php
// Create sessions table
try {
    $pdo = new PDO(
        'mysql:host=127.0.0.1;port=3306;dbname=symfresto07;charset=utf8mb4',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $sql = <<<SQL
    CREATE TABLE IF NOT EXISTS sessions (
        sess_id VARCHAR(128) NOT NULL PRIMARY KEY,
        sess_data BLOB NOT NULL,
        sess_time INTEGER NOT NULL,
        sess_lifetime INTEGER NOT NULL
    ) COLLATE utf8mb4_bin, ENGINE=InnoDB;
    SQL;
    
    $pdo->exec($sql);
    echo "✓ Table 'sessions' créée avec succès!\n";
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
    exit(1);
}
