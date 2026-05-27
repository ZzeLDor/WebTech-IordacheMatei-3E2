<?php

require_once __DIR__ . '/../autoload.php';

use Core\Database;

try {
    $pdo = Database::getConnection();

    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS equipment (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            serial_number TEXT UNIQUE NOT NULL,
            status TEXT NOT NULL DEFAULT 'functional',
            resource_id INTEGER,
            FOREIGN KEY(resource_id) REFERENCES resources(id) ON DELETE SET NULL
        )
    ");
    
    
    $count = $pdo->query("SELECT count(*) FROM equipment")->fetchColumn();
    if ($count == 0) {
        $pdo->exec("
            INSERT INTO equipment (name, serial_number, status, resource_id) VALUES
            ('Banda de alergat Matrix T70', 'SN-MXT70-001', 'functional', 1),
            ('Bicicleta medicinala TechnoGym', 'SN-TGBC-002', 'functional', 1),
            ('Aparat Multifunctional Kinesis', 'SN-TGKM-003', 'maintenance', 1),
            ('Bancheta Pilates Reformer', 'SN-PLRF-004', 'functional', 1)
        ");
    }

    echo "Migrare reusita: Tabela equipment a fost creata si populata!\n";
} catch (Exception $e) {
    echo "Eroare la migrare: " . $e->getMessage() . "\n";
}
