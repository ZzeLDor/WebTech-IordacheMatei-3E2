<?php

namespace Controllers;

use Core\Database;
use Exception;

// Gestioneaza profilul personal al membrilor si orarul antrenorilor
class ProfileController {

    // Ofera detaliile profilului, abonamentul curent si activitatile/rezervarile viitoare ale membrului
    public function getProfil() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(["eroare" => "Neautorizat"]);
            return;
        }

        $pdo = Database::getConnection();
        $uid = (int)$_SESSION['user_id'];

        $stmt = $pdo->prepare("SELECT id, name, email, role, created_at FROM users WHERE id = ?");
        $stmt->execute([$uid]);
        $user = $stmt->fetch();

        if (!$user) {
            http_response_code(404);
            echo json_encode(["eroare" => "Utilizatorul nu exista"]);
            return;
        }

        $stmt2 = $pdo->prepare("
            SELECT id, type, status, start_date, end_date
            FROM subscriptions
            WHERE user_id = ?
            ORDER BY
                CASE WHEN status = 'active' AND end_date >= date('now') THEN 0 ELSE 1 END,
                end_date DESC
            LIMIT 1
        ");
        $stmt2->execute([$uid]);
        $abonament = $stmt2->fetch() ?: null;

        $stmt3 = $pdo->prepare("
            SELECT
                b.id as booking_id,
                b.created_at as booked_at,
                s.id as session_id,
                s.title,
                s.description,
                s.category,
                s.start_time,
                s.end_time,
                s.max_capacity,
                u.name as trainer_name,
                r.name as room_name,
                (SELECT COUNT(*) FROM bookings bx WHERE bx.session_id = s.id) as current_bookings
            FROM bookings b
            JOIN sessions s ON b.session_id = s.id
            JOIN users u ON s.trainer_id = u.id
            LEFT JOIN resources r ON s.resource_id = r.id
            WHERE b.user_id = ?
            ORDER BY s.start_time DESC
        ");
        $stmt3->execute([$uid]);
        $activitati = $stmt3->fetchAll();

        echo json_encode([
            "status"     => "ok",
            "user"       => $user,
            "abonament"  => $abonament,
            "activitati" => $activitati,
        ]);
    }

    // Ofera orarul curent de lucru stabilit de un antrenor/kinetoterapeut
    public function getSchedule() {
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['trainer', 'therapist'])) {
            http_response_code(403);
            echo json_encode(["eroare" => "Doar antrenorii si kinetoterapeutii isi pot edita programul."]);
            return;
        }

        $pdo = Database::getConnection();
        $uid = (int)$_SESSION['user_id'];

        $stmt = $pdo->prepare("SELECT day_of_week, start_time, end_time FROM trainer_schedules WHERE trainer_id = ?");
        $stmt->execute([$uid]);
        $schedules = $stmt->fetchAll();

        echo json_encode(["status" => "ok", "schedule" => $schedules]);
    }

    // Actualizeaza zilele si orele de lucru pentru antrenorul autentificat
    public function updateSchedule() {
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['trainer', 'therapist'])) {
            http_response_code(403);
            echo json_encode(["eroare" => "Acces interzis"]);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        if (!isset($data['schedule']) || !is_array($data['schedule'])) {
            http_response_code(400);
            echo json_encode(["eroare" => "Date incomplete"]);
            return;
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $uid = (int)$_SESSION['user_id'];

            $del = $pdo->prepare("DELETE FROM trainer_schedules WHERE trainer_id = ?");
            $del->execute([$uid]);

            $ins = $pdo->prepare("INSERT INTO trainer_schedules (trainer_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)");
            
            foreach ($data['schedule'] as $daySched) {
                if (empty($daySched['working'])) {
                    continue; 
                }

                $day = (int)$daySched['day_of_week'];
                $start = $daySched['start_time'];
                $end = $daySched['end_time'];

                if (!preg_match('/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/', $start) || !preg_match('/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/', $end)) {
                    throw new Exception("Formatul orarului este invalid.");
                }

                if ($start >= $end) {
                    throw new Exception("Ora de inceput trebuie sa fie mai mica decat ora de sfarsit.");
                }

                $ins->execute([$uid, $day, $start, $end]);
            }

            $pdo->commit();
            echo json_encode(["status" => "ok", "mesaj" => "Programul a fost salvat cu succes!"]);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(["eroare" => $e->getMessage()]);
        }
    }
}

