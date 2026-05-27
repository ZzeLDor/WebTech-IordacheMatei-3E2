<?php

namespace Controllers;

use Core\Database;

// Gestioneaza adaugarea, expirarea si suspendarea abonamentelor pentru membri
class SubscriptionController {

    private static $validTypes = ['fitness', 'strength', 'kineto', 'mixed'];

    // Expira automat abonamentele active a caror data de final a fost depasita
    private static function autoExpire($pdo) {
        $pdo->exec("
            UPDATE subscriptions
            SET status = 'expired'
            WHERE status = 'active' AND end_date < date('now')
        ");
    }

    // Returneaza abonamentele din baza de date (toate pentru admin, doar cel propriu pentru membri)
    public function getAbonamente() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(["eroare" => "Neautorizat"]);
            return;
        }

        $pdo = Database::getConnection();
        self::autoExpire($pdo);

        if ($_SESSION['role'] === 'admin') {
            $stmt = $pdo->query("
                SELECT s.id, u.name as user_name, s.type, s.status, s.start_date, s.end_date
                FROM subscriptions s
                JOIN users u ON s.user_id = u.id
                ORDER BY s.end_date DESC
            ");
            $subs = $stmt->fetchAll();
        } else {
            $stmt = $pdo->prepare("
                SELECT id, type, status, start_date, end_date
                FROM subscriptions
                WHERE user_id = :uid
                ORDER BY end_date DESC
            ");
            $stmt->execute(['uid' => $_SESSION['user_id']]);
            $subs = $stmt->fetchAll();
        }

        echo json_encode(["status" => "ok", "abonamente" => $subs]);
    }

    // Afiseaza istoricul complet de abonamente al unui anumit membru (doar pentru administrator)
    public function getHistoryForUser() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(["eroare" => "Acces interzis"]);
            return;
        }

        $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
        if (!$userId) {
            http_response_code(400);
            echo json_encode(["eroare" => "user_id lipsa"]);
            return;
        }

        $pdo = Database::getConnection();
        self::autoExpire($pdo);

        $stmt = $pdo->prepare("
            SELECT id, type, status, start_date, end_date
            FROM subscriptions
            WHERE user_id = :uid
            ORDER BY end_date DESC
        ");
        $stmt->execute(['uid' => $userId]);
        $history = $stmt->fetchAll();

        echo json_encode(["status" => "ok", "history" => $history]);
    }

    // Creeaza un nou abonament pentru un membru si le marcheaza pe cele vechi ca expirate
    public function creareAbonament() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(["eroare" => "Acces interzis"]);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['user_id']) || !isset($data['type']) || !isset($data['luni'])) {
            http_response_code(400);
            echo json_encode(["eroare" => "Date incomplete"]);
            return;
        }

        $months = max(1, (int)$data['luni']);
        $start  = date('Y-m-d');
        $end    = date('Y-m-d', strtotime("+{$months} months"));

        $pdo = Database::getConnection();

        $pdo->prepare("
            UPDATE subscriptions SET status = 'expired'
            WHERE user_id = :uid AND status = 'active'
        ")->execute(['uid' => $data['user_id']]);

        $stmt = $pdo->prepare("
            INSERT INTO subscriptions (user_id, type, status, start_date, end_date)
            VALUES (:uid, :tip, 'active', :start, :end)
        ");
        $stmt->execute([
            'uid'   => $data['user_id'],
            'tip'   => $data['type'],
            'start' => $start,
            'end'   => $end,
        ]);

        echo json_encode(["status" => "ok", "mesaj" => "Abonament creat cu succes!"]);
    }

    // Permite suspendarea sau reactivarea temporara a unui abonament activ
    public function updateStatus() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(["eroare" => "Acces interzis"]);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        $subId     = isset($data['sub_id'])    ? (int)$data['sub_id']  : 0;
        $newStatus = $data['status'] ?? '';

        if (!$subId || !in_array($newStatus, ['active', 'suspended'])) {
            http_response_code(400);
            echo json_encode(["eroare" => "Date invalide"]);
            return;
        }

        $pdo = Database::getConnection();

        $check = $pdo->prepare("SELECT id, status, end_date FROM subscriptions WHERE id = ?");
        $check->execute([$subId]);
        $sub = $check->fetch();

        if (!$sub) {
            http_response_code(404);
            echo json_encode(["eroare" => "Abonamentul nu exista"]);
            return;
        }

        if ($sub['status'] === 'expired') {
            http_response_code(400);
            echo json_encode(["eroare" => "Nu se poate modifica un abonament expirat. Creati unul nou."]);
            return;
        }

        $pdo->prepare("UPDATE subscriptions SET status = :s WHERE id = :id")
            ->execute(['s' => $newStatus, 'id' => $subId]);

        $label = $newStatus === 'active' ? 'reactivat' : 'suspendat';
        echo json_encode(["status" => "ok", "mesaj" => "Abonamentul a fost {$label}."]);
    }
}
