<?php

namespace Controllers;

use Core\Database;

// Gestioneaza actiunile administrative legate de utilizatori
class UserController {
    
    // Rolurile si specializarile permise in aplicatie
    private const VALID_ROLES = ['admin', 'trainer', 'therapist', 'member'];
    private const VALID_SPECS = ['fitness', 'forta', 'kinetoterapie'];
    private const SPECIALIST_ROLES = ['trainer', 'therapist'];

    // Ofera lista tuturor utilizatorilor din baza de date (doar pentru administrator)
    public function getTotiUtilizatorii() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(["eroare" => "Acces interzis"]);
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->query("
            SELECT u.id, u.name, u.email, u.role, u.specialization, u.created_at,
                   (SELECT id || '|' || type || '|' || status || '|' || end_date
                    FROM subscriptions
                    WHERE user_id = u.id AND status IN ('active','suspended') AND end_date >= date('now')
                    ORDER BY end_date DESC LIMIT 1) as active_subscription
            FROM users u
            ORDER BY u.created_at DESC
        ");
        $users = $stmt->fetchAll();

        echo json_encode(["status" => "ok", "utilizatori" => $users]);
    }

    // Permite administratorului sa schimbe rolul unui utilizator (ex: de la membru la antrenor)
    public function updateRol() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(["eroare" => "Acces interzis"]);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['user_id']) || !isset($data['rol_nou'])) {
            http_response_code(400);
            echo json_encode(["eroare" => "Date incomplete"]);
            return;
        }

        if (!in_array($data['rol_nou'], self::VALID_ROLES)) {
            http_response_code(400);
            echo json_encode(["eroare" => "Rol invalid"]);
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE users SET role = :role WHERE id = :id");
        $stmt->execute(['role' => $data['rol_nou'], 'id' => $data['user_id']]);

        echo json_encode(["status" => "ok", "mesaj" => "Rol actualizat"]);
    }

    // Actualizeaza specializarea unui antrenor sau kinetoterapeut (ex: fitness, kinetoterapie)
    public function updateSpecializare() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(["eroare" => "Acces interzis"]);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['user_id']) || !isset($data['specialization'])) {
            http_response_code(400);
            echo json_encode(["eroare" => "Date incomplete"]);
            return;
        }

        $spec = $data['specialization'] === '' ? null : $data['specialization'];
        if ($spec !== null && !in_array($spec, self::VALID_SPECS)) {
            http_response_code(400);
            echo json_encode(["eroare" => "Specializare invalida"]);
            return;
        }

        $pdo = Database::getConnection();

        $check = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $check->execute([$data['user_id']]);
        $user = $check->fetch();

        if (!$user || !in_array($user['role'], self::SPECIALIST_ROLES)) {
            http_response_code(400);
            echo json_encode(["eroare" => "Specializarea se poate seta doar pentru antrenori sau kinetoterapeuti"]);
            return;
        }

        $stmt = $pdo->prepare("UPDATE users SET specialization = :spec WHERE id = :id");
        $stmt->execute(['spec' => $spec, 'id' => $data['user_id']]);

        echo json_encode(["status" => "ok", "mesaj" => "Specializare actualizata"]);
    }

    // Ofera lista specialistilor (antrenori/terapeuti) si orarul acestora de lucru
    public function getSpecialisti() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(["eroare" => "Acces interzis"]);
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->query("
            SELECT id, name, role, specialization 
            FROM users 
            WHERE role IN ('trainer', 'therapist')
            ORDER BY name ASC
        ");
        $specialists = $stmt->fetchAll();

        $stmtSched = $pdo->query("SELECT trainer_id, day_of_week, start_time, end_time FROM trainer_schedules");
        $schedules = $stmtSched->fetchAll();

        $groupedScheds = [];
        foreach ($schedules as $sch) {
            $groupedScheds[$sch['trainer_id']][] = [
                'day_of_week' => (int)$sch['day_of_week'],
                'start_time'  => $sch['start_time'],
                'end_time'    => $sch['end_time']
            ];
        }

        foreach ($specialists as &$u) {
            $u['schedules'] = $groupedScheds[$u['id']] ?? [];
        }

        echo json_encode(["status" => "ok", "specialisti" => $specialists]);
    }
}
