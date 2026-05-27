<?php

require_once __DIR__ . '/../autoload.php';

use Core\Database;

try {
    $pdo = Database::getConnection();

    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS trainer_schedules (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            trainer_id INTEGER NOT NULL,
            day_of_week INTEGER NOT NULL, -- 0 (Duminica) - 6 (Sambata)
            start_time TEXT NOT NULL,      -- 'HH:MM'
            end_time TEXT NOT NULL,        -- 'HH:MM'
            FOREIGN KEY(trainer_id) REFERENCES users(id) ON DELETE CASCADE
        );
    ");
    echo "Tabela 'trainer_schedules' a fost creata/verificata cu succes!\n";

    
    $stmt = $pdo->query("SELECT id, name FROM users WHERE role IN ('trainer', 'therapist')");
    $specialists = $stmt->fetchAll();

    foreach ($specialists as $spec) {
        
        $check = $pdo->prepare("SELECT COUNT(*) FROM trainer_schedules WHERE trainer_id = ?");
        $check->execute([$spec['id']]);
        if ($check->fetchColumn() == 0) {
            
            $insert = $pdo->prepare("
                INSERT INTO trainer_schedules (trainer_id, day_of_week, start_time, end_time)
                VALUES (?, ?, '08:00', '16:00')
            ");
            for ($d = 1; $d <= 5; $d++) {
                $insert->execute([$spec['id'], $d]);
            }
            echo "Adaugat program implicit (Luni-Vineri 08:00-16:00) pentru trainerul: " . $spec['name'] . "\n";
        }
    }

} catch (Exception $e) {
    echo "Eroare: " . $e->getMessage() . "\n";
}
