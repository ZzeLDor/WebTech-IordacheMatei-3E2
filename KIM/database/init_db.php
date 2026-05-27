<?php

require_once __DIR__ . '/../autoload.php';

use Core\Database;

try {
    $pdo = Database::getConnection();

    $schemaPath = __DIR__ . '/schema.sql';
    $sql = file_get_contents($schemaPath);
    
    $pdo->exec($sql);
    
    echo "Baza de date a fost creata!\n";
} catch (Exception $e) {
    echo "Eroare: " . $e->getMessage() . "\n";
}
