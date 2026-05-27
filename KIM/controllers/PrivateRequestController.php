<?php

namespace Controllers;

use Core\Database;
use Exception;

// Gestioneaza cererile de sedinte private trimise de membri catre antrenori sau kinetoterapeuti
class PrivateRequestController {

    // Returneaza lista de cereri private, filtrata in functie de rol (membru, antrenor, admin)
    public function getRequests() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(["eroare" => "Neautentificat"]);
            return;
        }

        $userId = $_SESSION['user_id'];
        $role = $_SESSION['role'];
        $pdo = Database::getConnection();

        $query = "
            SELECT pr.*, 
                   u.name as user_name, 
                   t.name as trainer_name,
                   h.name as handler_name
            FROM private_requests pr
            JOIN users u ON pr.user_id = u.id
            LEFT JOIN users t ON pr.preferred_trainer_id = t.id
            LEFT JOIN users h ON pr.handled_by = h.id
            WHERE 1=1
        ";
        $params = [];

        if ($role === 'member') {
            $query .= " AND pr.user_id = ?";
            $params[] = $userId;
        } elseif ($role === 'trainer' || $role === 'therapist') {
            $stmtSpec = $pdo->prepare("SELECT specialization FROM users WHERE id = ?");
            $stmtSpec->execute([$userId]);
            $spec = $stmtSpec->fetchColumn();

            if ($spec) {
                $query .= " AND (pr.preferred_trainer_id = ? OR (pr.preferred_trainer_id IS NULL AND pr.category = ?))";
                $params[] = $userId;
                $params[] = $spec;
            } else {
                $query .= " AND pr.preferred_trainer_id = ?";
                $params[] = $userId;
            }
        }
        
        $query .= " ORDER BY pr.created_at DESC";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $requests = $stmt->fetchAll();

        echo json_encode(["status" => "ok", "requests" => $requests]);
    }

    // Permite unui membru sa creeze o cerere noua de sedinta privata intr-un anumit interval orar
    public function createRequest() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'member') {
            http_response_code(403);
            echo json_encode(["eroare" => "Doar membrii pot face cereri"]);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['title']) || empty($data['category']) || empty($data['date']) || empty($data['start_time']) || empty($data['end_time'])) {
            http_response_code(400);
            echo json_encode(["eroare" => "Date incomplete"]);
            return;
        }

        $pdo = Database::getConnection();
        
        $trainerId = !empty($data['preferred_trainer_id']) ? $data['preferred_trainer_id'] : null;

        $stmt = $pdo->prepare("
            INSERT INTO private_requests (user_id, title, description, category, preferred_trainer_id, date, start_time, end_time)
            VALUES (:uid, :title, :desc, :cat, :tid, :date, :start, :end)
        ");

        $startFull = $data['date'] . ' ' . $data['start_time'];
        $endFull = $data['date'] . ' ' . $data['end_time'];

        $stmt->execute([
            'uid' => $_SESSION['user_id'],
            'title' => $data['title'],
            'desc' => $data['description'] ?? '',
            'cat' => $data['category'],
            'tid' => $trainerId,
            'date' => $data['date'],
            'start' => $startFull,
            'end' => $endFull
        ]);

        echo json_encode(["status" => "ok", "mesaj" => "Cerere inregistrata cu succes"]);
    }

    // Aproba o cerere de sedinta privata, creeaza automat sedinta/rezervarea si trimite email
    public function acceptRequest() {
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['trainer', 'therapist', 'admin'])) {
            http_response_code(403);
            echo json_encode(["eroare" => "Acces interzis"]);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['request_id']) || empty($data['room_id'])) {
            http_response_code(400);
            echo json_encode(["eroare" => "Trebuie specificata o sala pentru a accepta"]);
            return;
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("SELECT * FROM private_requests WHERE id = ? AND status = 'pending'");
            $stmt->execute([$data['request_id']]);
            $req = $stmt->fetch();

            if (!$req) {
                throw new Exception("Cererea nu exista sau nu este in asteptare");
            }

            $assignedTrainerId = !empty($req['preferred_trainer_id']) ? $req['preferred_trainer_id'] : $_SESSION['user_id'];

            $insertSess = $pdo->prepare("
                INSERT INTO sessions (title, description, category, trainer_id, resource_id, start_time, end_time, max_capacity)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)
            ");
            $insertSess->execute([
                $req['title'] . ' (Privat)',
                $req['description'],
                $req['category'],
                $assignedTrainerId, 
                $data['room_id'],
                $req['start_time'],
                $req['end_time']
            ]);
            $sessionId = $pdo->lastInsertId();

            $insertBook = $pdo->prepare("INSERT INTO bookings (session_id, user_id) VALUES (?, ?)");
            $insertBook->execute([$sessionId, $req['user_id']]);

            $updReq = $pdo->prepare("UPDATE private_requests SET status = 'accepted', handled_by = ? WHERE id = ?");
            $updReq->execute([$_SESSION['user_id'], $req['id']]);

            $pdo->commit();

            try {
                $stmtUser = $pdo->prepare("SELECT email, name FROM users WHERE id = ?");
                $stmtUser->execute([$req['user_id']]);
                $u = $stmtUser->fetch();
                if ($u && $u['email']) {
                    $to = $u['email'];

                    $stmtTrainer = $pdo->prepare("SELECT name FROM users WHERE id = ?");
                    $stmtTrainer->execute([$assignedTrainerId]);
                    $trainerName = $stmtTrainer->fetchColumn() ?: 'Trainer';

                    $stmtRoom = $pdo->prepare("SELECT name FROM resources WHERE id = ?");
                    $stmtRoom->execute([$data['room_id']]);
                    $roomName = $stmtRoom->fetchColumn() ?: 'Sala Principala';

                    $subject = "Aprobare cerere sesiune privata – " . $req['title'];
                    $htmlBody = \Core\Mailer::privateRequestAcceptedBody($req, $trainerName, $roomName);

                    \Core\Mailer::send($to, $u['name'], $subject, $htmlBody);
                }
            } catch (Exception $mailE) {
                // Erorile de mail nu blocheaza tranzactia bazei de date
            }

            echo json_encode(["status" => "ok", "mesaj" => "Cerere acceptata si sesiune creata. Email trimis."]);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(["eroare" => $e->getMessage()]);
        }
    }

    // Respinge o cerere de sedinta privata si trimite un email de notificare catre membru
    public function denyRequest() {
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['trainer', 'therapist', 'admin'])) {
            http_response_code(403);
            echo json_encode(["eroare" => "Acces interzis"]);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['request_id'])) {
            http_response_code(400);
            echo json_encode(["eroare" => "ID cerere lipsa"]);
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM private_requests WHERE id = ?");
        $stmt->execute([$data['request_id']]);
        $req = $stmt->fetch();

        if (!$req || $req['status'] !== 'pending') {
            http_response_code(400);
            echo json_encode(["eroare" => "Cerere invalida"]);
            return;
        }

        if ($_SESSION['role'] !== 'admin' && $req['preferred_trainer_id'] != $_SESSION['user_id']) {
            http_response_code(403);
            echo json_encode(["eroare" => "Poti respinge doar cererile alocate tie personal, nu si pe cele generale pe categorie"]);
            return;
        }

        $updReq = $pdo->prepare("UPDATE private_requests SET status = 'denied', handled_by = ? WHERE id = ?");
        $updReq->execute([$_SESSION['user_id'], $data['request_id']]);

        try {
            $stmtUser = $pdo->prepare("SELECT email, name FROM users WHERE id = ?");
            $stmtUser->execute([$req['user_id']]);
            $u = $stmtUser->fetch();
            if ($u && $u['email']) {
                $to = $u['email'];

                $stmtHandler = $pdo->prepare("SELECT name, role FROM users WHERE id = ?");
                $stmtHandler->execute([$_SESSION['user_id']]);
                $handler = $stmtHandler->fetch();
                $handlerName = $handler['name'] ?? 'Staff KIM';
                $handlerRole = $handler['role'] ?? $_SESSION['role'];

                $subject = "Respingere cerere sesiune privata – " . $req['title'];
                $htmlBody = \Core\Mailer::privateRequestDeniedBody($req, $handlerName, $handlerRole);

                \Core\Mailer::send($to, $u['name'], $subject, $htmlBody);
            }
        } catch (Exception $mailE) {
            // Erorile de mail nu blocheaza tranzactia
        }

        echo json_encode(["status" => "ok", "mesaj" => "Cerere respinsa. Email trimis."]);
    }
}
