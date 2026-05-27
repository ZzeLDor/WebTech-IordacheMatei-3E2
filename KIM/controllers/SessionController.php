<?php

namespace Controllers;

use Core\Database;
use Core\Mailer;

// Gestioneaza sesiunile de antrenament/kinetoterapie si rezervarile membrilor
class SessionController {

    // Maparea categoriilor de sedinte la tipurile de abonamente eligibile
    private static $categorySubscriptionMap = [
        'fitness'        => ['fitness', 'mixed'],
        'forta'          => ['forta', 'strength', 'mixed'],
        'kinetoterapie'  => ['kinetoterapie', 'kineto', 'mixed'],
    ];

    // Verifica daca un utilizator are un abonament valid si activ compatibil cu categoria sedintei
    private function canBookCategory(int $userId, string $userRole, string $category): bool {
        if (in_array($userRole, ['admin', 'trainer'])) {
            return true;
        }

        $allowed = self::$categorySubscriptionMap[$category] ?? [];
        if (empty($allowed)) return false;

        $pdo = Database::getConnection();
        $placeholders = implode(',', array_fill(0, count($allowed), '?'));
        $stmt = $pdo->prepare("
            SELECT id FROM subscriptions
            WHERE user_id = ?
              AND status = 'active'
              AND end_date >= date('now')
              AND type IN ($placeholders)
            LIMIT 1
        ");
        $stmt->execute(array_merge([$userId], $allowed));
        return $stmt->fetch() !== false;
    }

    // Ofera lista sesiunilor programate (ascunde sesiunile private pentru membrii neinscrisi)
    public function getSesiuni() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            return;
        }

        $pdo = Database::getConnection();
        $userId = (int)$_SESSION['user_id'];
        $userRole = $_SESSION['role'];

        $query = "
            SELECT s.id, s.title, s.description, s.category, s.start_time, s.end_time, s.max_capacity,
            u.name as trainer_name, s.resource_id, r.name as room_name,
            (SELECT COUNT(*) FROM bookings b WHERE b.session_id = s.id) as current_bookings
            FROM sessions s
            JOIN users u ON s.trainer_id = u.id
            LEFT JOIN resources r ON s.resource_id = r.id
            WHERE 1=1
        ";
        $params = [];

        if ($userRole !== 'admin') {
            $query .= "
                AND (
                    s.title NOT LIKE '%(Privat)%'
                    OR s.trainer_id = ?
                    OR EXISTS (
                        SELECT 1 FROM bookings b2 
                        WHERE b2.session_id = s.id AND b2.user_id = ?
                    )
                )
            ";
            $params[] = $userId;
            $params[] = $userId;
        }

        $query .= " ORDER BY s.start_time ASC";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $sesiuni = $stmt->fetchAll();

        echo json_encode(["status" => "ok", "sesiuni" => $sesiuni]);
    }

    // Obtine informatii detaliate despre o anumita sedinta si lista de participanti
    public function getSesiuneById() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            return;
        }

        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(["eroare" => "ID lipsa"]);
            return;
        }

        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT s.id, s.title, s.description, s.category, s.trainer_id, s.start_time, s.end_time, s.max_capacity,
            u.name as trainer_name, r.name as room_name, r.type as room_type,
            (SELECT COUNT(*) FROM bookings b WHERE b.session_id = s.id) as current_bookings
            FROM sessions s
            JOIN users u ON s.trainer_id = u.id
            LEFT JOIN resources r ON s.resource_id = r.id
            WHERE s.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $sesiune = $stmt->fetch();

        if (!$sesiune) {
            http_response_code(404);
            echo json_encode(["eroare" => "Sesiunea nu exista"]);
            return;
        }

        $userId = (int)$_SESSION['user_id'];
        $userRole = $_SESSION['role'];
        $isPrivate = (strpos($sesiune['title'], '(Privat)') !== false);

        if ($isPrivate && $userRole !== 'admin' && (int)$sesiune['trainer_id'] !== $userId) {
            $stmtBooked = $pdo->prepare("SELECT 1 FROM bookings WHERE session_id = ? AND user_id = ?");
            $stmtBooked->execute([$sesiune['id'], $userId]);
            if (!$stmtBooked->fetch()) {
                http_response_code(403);
                echo json_encode(["eroare" => "Acces interzis la aceasta sesiune privata"]);
                return;
            }
        }

        $stmt2 = $pdo->prepare("
            SELECT u.id, u.name, u.email, b.created_at as booked_at
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            WHERE b.session_id = :id
            ORDER BY b.created_at ASC
        ");
        $stmt2->execute(['id' => $id]);
        $participanti = $stmt2->fetchAll();

        $stmt3 = $pdo->prepare("SELECT id FROM bookings WHERE session_id = :sid AND user_id = :uid");
        $stmt3->execute(['sid' => $id, 'uid' => $_SESSION['user_id']]);
        $esteInscris = $stmt3->fetch() ? true : false;

        $poateRezeva = $this->canBookCategory(
            (int)$_SESSION['user_id'],
            $_SESSION['role'],
            $sesiune['category']
        );

        echo json_encode([
            "status"       => "ok",
            "sesiune"      => $sesiune,
            "participanti" => $participanti,
            "este_inscris" => $esteInscris,
            "poate_rezerva" => $poateRezeva,
        ]);
    }

    // Ofera lista salilor si resurselor disponibile pentru programare
    public function getResurse() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            return;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT id, type, name, capacity FROM resources");
        $resurse = $stmt->fetchAll();
        echo json_encode(["status" => "ok", "resurse" => $resurse]);
    }

    // Adauga o noua sedinta de grup sau privata in orarul saptamanal
    public function creareSesiune() {
        if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'trainer')) {
            http_response_code(403);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $validCategories = ['fitness', 'forta', 'kinetoterapie'];
        $category = in_array($data['category'] ?? '', $validCategories) ? $data['category'] : 'fitness';

        $pdo = Database::getConnection();

        $resourceId = (int)($data['resource_id'] ?? 0);
        if ($resourceId !== 1 && $resourceId > 0) {
            $stmtCheck = $pdo->prepare("
                SELECT COUNT(*) FROM sessions 
                WHERE resource_id = ? 
                  AND start_time < ? 
                  AND end_time > ?
            ");
            $stmtCheck->execute([$resourceId, $data['end_time'], $data['start_time']]);
            if ($stmtCheck->fetchColumn() > 0) {
                http_response_code(400);
                echo json_encode(["eroare" => "Aceasta sala este deja ocupata in intervalul selectat!"]);
                return;
            }
        }

        $stmt = $pdo->prepare("
            INSERT INTO sessions (title, description, category, trainer_id, resource_id, start_time, end_time, max_capacity)
            VALUES (:title, :desc, :cat, :tid, :rid, :start, :end, :cap)
        ");
        $stmt->execute([
            'title' => $data['title'],
            'desc'  => $data['description'] ?? null,
            'cat'   => $category,
            'tid'   => $_SESSION['user_id'],
            'rid'   => $data['resource_id'],
            'start' => $data['start_time'],
            'end'   => $data['end_time'],
            'cap'   => $data['capacity'],
        ]);

        echo json_encode(["status" => "ok"]);
    }

    // Inscrie membrul autentificat la sedinta selectata si trimite confirmarea pe email
    public function rezerva() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT max_capacity, category,
            (SELECT COUNT(*) FROM bookings WHERE session_id = :sid) as ocupat
            FROM sessions WHERE id = :sid
        ");
        $stmt->execute(['sid' => $data['session_id']]);
        $sesiune = $stmt->fetch();

        if (!$sesiune) {
            http_response_code(404);
            echo json_encode(["eroare" => "Sesiunea nu exista"]);
            return;
        }

        if (!$this->canBookCategory((int)$_SESSION['user_id'], $_SESSION['role'], $sesiune['category'])) {
            $categoryLabels = ['fitness' => 'Fitness', 'forta' => 'Forta', 'kinetoterapie' => 'Kinetoterapie'];
            $label = $categoryLabels[$sesiune['category']] ?? $sesiune['category'];
            http_response_code(403);
            echo json_encode(["eroare" => "Abonamentul tau nu include sesiuni de tip " . $label . ". Contacteaza receptia pentru upgrade."]);
            return;
        }

        if ($sesiune['ocupat'] >= $sesiune['max_capacity']) {
            http_response_code(400);
            echo json_encode(["eroare" => "Sesiunea este plina"]);
            return;
        }

        $stmt2 = $pdo->prepare("SELECT id FROM bookings WHERE session_id = :sid AND user_id = :uid");
        $stmt2->execute(['sid' => $data['session_id'], 'uid' => $_SESSION['user_id']]);
        if ($stmt2->fetch()) {
            http_response_code(400);
            echo json_encode(["eroare" => "Esti deja inscris la aceasta sesiune"]);
            return;
        }

        $stmt3 = $pdo->prepare("INSERT INTO bookings (session_id, user_id) VALUES (:sid, :uid)");
        $stmt3->execute([
            'sid' => $data['session_id'],
            'uid' => $_SESSION['user_id'],
        ]);

        try {
            $stmtUser = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
            $stmtUser->execute([$_SESSION['user_id']]);
            $user = $stmtUser->fetch();

            $stmtSes = $pdo->prepare("
                SELECT s.title, s.category, s.start_time, s.end_time,
                       u.name as trainer_name, r.name as room_name
                FROM sessions s
                JOIN users u ON s.trainer_id = u.id
                LEFT JOIN resources r ON s.resource_id = r.id
                WHERE s.id = ?
            ");
            $stmtSes->execute([$data['session_id']]);
            $sesDetails = $stmtSes->fetch();

            if ($user && $sesDetails) {
                Mailer::send(
                    $user['email'],
                    $user['name'],
                    'Rezervare confirmata – ' . $sesDetails['title'],
                    Mailer::bookingConfirmationBody((array)$sesDetails)
                );
            }
        } catch (\Exception $e) {
            error_log('[KIM] Booking email error: ' . $e->getMessage());
        }

        echo json_encode(["status" => "ok", "mesaj" => "Inscrierea a fost confirmata!"]);
    }

    // Anuleaza o sedinta din baza de date si notifica prin email membrii inscrisi
    public function anulareSesiune() {
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'trainer', 'therapist'])) {
            http_response_code(403);
            echo json_encode(["eroare" => "Acces interzis"]);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        $sessionId = isset($data['session_id']) ? (int)$data['session_id'] : 0;
        $reason    = trim($data['reason'] ?? '');

        if (!$sessionId) {
            http_response_code(400);
            echo json_encode(["eroare" => "ID sesiune lipsa"]);
            return;
        }

        $pdo = Database::getConnection();

        $stmtSes = $pdo->prepare("
            SELECT s.id, s.title, s.category, s.start_time, s.end_time, s.trainer_id,
                   u.name as trainer_name, r.name as room_name
            FROM sessions s
            JOIN users u ON s.trainer_id = u.id
            LEFT JOIN resources r ON s.resource_id = r.id
            WHERE s.id = ?
        ");
        $stmtSes->execute([$sessionId]);
        $sesiune = $stmtSes->fetch();

        if (!$sesiune) {
            http_response_code(404);
            echo json_encode(["eroare" => "Sesiunea nu exista"]);
            return;
        }

        if ($_SESSION['role'] !== 'admin' && (int)$sesiune['trainer_id'] !== (int)$_SESSION['user_id']) {
            http_response_code(403);
            echo json_encode(["eroare" => "Poti anula doar propriile sesiuni"]);
            return;
        }

        $stmtPart = $pdo->prepare("
            SELECT u.name, u.email
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            WHERE b.session_id = ?
        ");
        $stmtPart->execute([$sessionId]);
        $participanti = $stmtPart->fetchAll();

        $pdo->prepare("DELETE FROM bookings WHERE session_id = ?")->execute([$sessionId]);
        $pdo->prepare("DELETE FROM sessions WHERE id = ?")->execute([$sessionId]);

        foreach ($participanti as $p) {
            try {
                Mailer::send(
                    $p['email'],
                    $p['name'],
                    'Sesiune anulata – ' . $sesiune['title'],
                    Mailer::sessionCancelledBody((array)$sesiune, $reason)
                );
            } catch (\Exception $e) {
                error_log('[KIM] Cancellation email error for ' . $p['email'] . ': ' . $e->getMessage());
            }
        }

        echo json_encode([
            "status" => "ok",
            "mesaj"  => "Sesiunea a fost anulata. " . count($participanti) . " participant(ti) au fost notificati."
        ]);
    }

    // Modifica detaliile unei sedinte (ora, sala, descriere, categorie, capacitate)
    public function modificareSesiune() {
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'trainer', 'therapist'])) {
            http_response_code(403);
            echo json_encode(["eroare" => "Acces interzis"]);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        $sessionId = isset($data['session_id']) ? (int)$data['session_id'] : 0;

        if (!$sessionId) {
            http_response_code(400);
            echo json_encode(["eroare" => "ID sesiune lipsa"]);
            return;
        }

        $pdo = Database::getConnection();

        $stmtCheck = $pdo->prepare("SELECT trainer_id, resource_id, start_time, end_time FROM sessions WHERE id = ?");
        $stmtCheck->execute([$sessionId]);
        $existing = $stmtCheck->fetch();

        if (!$existing) {
            http_response_code(404);
            echo json_encode(["eroare" => "Sesiunea nu exista"]);
            return;
        }

        if ($_SESSION['role'] !== 'admin' && (int)$existing['trainer_id'] !== (int)$_SESSION['user_id']) {
            http_response_code(403);
            echo json_encode(["eroare" => "Poti edita doar propriile sesiuni"]);
            return;
        }

        $resourceId = !empty($data['resource_id']) ? (int)$data['resource_id'] : (int)$existing['resource_id'];
        $startTime = !empty($data['start_time']) ? $data['start_time'] : $existing['start_time'];
        $endTime = !empty($data['end_time']) ? $data['end_time'] : $existing['end_time'];

        if ($resourceId !== 1 && $resourceId > 0) {
            $stmtCheckOverlap = $pdo->prepare("
                SELECT COUNT(*) FROM sessions 
                WHERE resource_id = ? 
                  AND id != ?
                  AND start_time < ? 
                  AND end_time > ?
            ");
            $stmtCheckOverlap->execute([$resourceId, $sessionId, $endTime, $startTime]);
            if ($stmtCheckOverlap->fetchColumn() > 0) {
                http_response_code(400);
                echo json_encode(["eroare" => "Aceasta sala este deja ocupata in intervalul selectat!"]);
                return;
            }
        }

        $validCategories = ['fitness', 'forta', 'kinetoterapie'];
        $category = in_array($data['category'] ?? '', $validCategories) ? $data['category'] : null;

        $fields = [];
        $params = [];

        if (!empty($data['title']))       { $fields[] = 'title = :title';         $params['title']       = $data['title']; }
        if (!empty($data['description'])) { $fields[] = 'description = :desc';    $params['desc']        = $data['description']; }
        if ($category)                    { $fields[] = 'category = :category';   $params['category']    = $category; }
        if (!empty($data['start_time']))  { $fields[] = 'start_time = :start';    $params['start']       = $data['start_time']; }
        if (!empty($data['end_time']))    { $fields[] = 'end_time = :end';        $params['end']         = $data['end_time']; }
        if (!empty($data['max_capacity'])){ $fields[] = 'max_capacity = :cap';    $params['cap']         = (int)$data['max_capacity']; }
        if (!empty($data['resource_id'])) { $fields[] = 'resource_id = :rid';     $params['rid']         = (int)$data['resource_id']; }

        if (empty($fields)) {
            http_response_code(400);
            echo json_encode(["eroare" => "Niciun camp de actualizat"]);
            return;
        }

        $params['id'] = $sessionId;
        $sql = "UPDATE sessions SET " . implode(', ', $fields) . " WHERE id = :id";
        $pdo->prepare($sql)->execute($params);

        echo json_encode(["status" => "ok", "mesaj" => "Sesiunea a fost actualizata!"]);
    }
}
