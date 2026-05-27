<?php

namespace Controllers;

use Core\Database;
use Exception;

// Gestioneaza salile de sport/kinetoterapie, echipamentele din inventar si importul de specialisti
class ResourceController {

    // Returneaza toate salile de antrenament si echipamentele inregistrate in baza de date
    public function getRoomsAndEquipment() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(["eroare" => "Acces interzis"]);
            return;
        }

        $pdo = Database::getConnection();

        $rooms = $pdo->query("SELECT * FROM resources ORDER BY name ASC")->fetchAll();

        $equipment = $pdo->query("
            SELECT e.*, r.name as room_name 
            FROM equipment e 
            LEFT JOIN resources r ON e.resource_id = r.id 
            ORDER BY e.name ASC
        ")->fetchAll();

        echo json_encode([
            "status" => "ok",
            "rooms" => $rooms,
            "equipment" => $equipment
        ]);
    }

    // Salveaza sau actualizeaza o sala in baza de date
    public function saveRoom() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(["eroare" => "Acces interzis"]);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['name']) || !isset($data['capacity'])) {
            http_response_code(400);
            echo json_encode(["eroare" => "Date incomplete"]);
            return;
        }

        $pdo = Database::getConnection();

        if (!empty($data['id'])) {
            $stmt = $pdo->prepare("UPDATE resources SET name = ?, capacity = ?, description = ? WHERE id = ?");
            $stmt->execute([$data['name'], $data['capacity'], $data['description'] ?? '', $data['id']]);
            $msg = "Sala actualizata cu succes";
        } else {
            $stmt = $pdo->prepare("INSERT INTO resources (type, name, capacity, description) VALUES ('room', ?, ?, ?)");
            $stmt->execute([$data['name'], $data['capacity'], $data['description'] ?? '']);
            $msg = "Sala creata cu succes";
        }

        echo json_encode(["status" => "ok", "mesaj" => $msg]);
    }

    // Sterge o anumita sala din baza de date
    public function deleteRoom() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(["eroare" => "Acces interzis"]);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(["eroare" => "ID lipsa"]);
            return;
        }

        $pdo = Database::getConnection();
        
        $stmt = $pdo->prepare("DELETE FROM resources WHERE id = ?");
        $stmt->execute([$data['id']]);

        echo json_encode(["status" => "ok", "mesaj" => "Sala stearsa cu succes"]);
    }

    // Adauga un echipament nou sau il actualizeaza pe cel existent
    public function saveEquipment() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(["eroare" => "Acces interzis"]);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['name']) || empty($data['serial_number'])) {
            http_response_code(400);
            echo json_encode(["eroare" => "Date incomplete"]);
            return;
        }

        $pdo = Database::getConnection();
        $roomId = !empty($data['resource_id']) ? $data['resource_id'] : null;

        try {
            if (!empty($data['id'])) {
                $stmt = $pdo->prepare("UPDATE equipment SET name = ?, serial_number = ?, status = ?, resource_id = ? WHERE id = ?");
                $stmt->execute([$data['name'], $data['serial_number'], $data['status'] ?? 'functional', $roomId, $data['id']]);
                $msg = "Echipament actualizat cu succes";
            } else {
                $stmt = $pdo->prepare("INSERT INTO equipment (name, serial_number, status, resource_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$data['name'], $data['serial_number'], $data['status'] ?? 'functional', $roomId]);
                $msg = "Echipament adaugat cu succes";
            }
            echo json_encode(["status" => "ok", "mesaj" => $msg]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(["eroare" => "Codul serial trebuie sa fie unic!"]);
        }
    }

    // Sterge un echipament pe baza ID-ului specificat
    public function deleteEquipment() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(["eroare" => "Acces interzis"]);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(["eroare" => "ID lipsa"]);
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM equipment WHERE id = ?");
        $stmt->execute([$data['id']]);

        echo json_encode(["status" => "ok", "mesaj" => "Echipament sters cu succes"]);
    }

    // Importa o lista de antrenori dintr-un fisier incarcat de tip CSV sau XML
    public function importTrainers() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(["eroare" => "Acces interzis"]);
            return;
        }

        if (empty($_FILES['file'])) {
            http_response_code(400);
            echo json_encode(["eroare" => "Niciun fisier nu a fost incarcat"]);
            return;
        }

        $file = $_FILES['file'];
        $fileName = $file['name'];
        $filePath = $file['tmp_name'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $importedCount = 0;

            if ($ext === 'csv') {
                if (($handle = fopen($filePath, "r")) !== FALSE) {
                    $bom = fread($handle, 3);
                    if ($bom !== "\xEF\xBB\xBF") {
                        rewind($handle);
                    }

                    $headers = fgetcsv($handle, 1000, ",");
                    if ($headers !== FALSE) {
                        $headers = array_map(function($h) {
                            return strtolower(trim(str_replace("\xEF\xBB\xBF", '', $h)));
                        }, $headers);

                        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                            $data = array_combine($headers, $row);
                            if ($data === FALSE) continue;

                            $nume = trim($data['nume'] ?? $data['name'] ?? '');
                            $email = trim($data['email'] ?? '');
                            $rol = trim($data['rol'] ?? $data['role'] ?? 'trainer');
                            $spec = trim($data['specializare'] ?? $data['specialization'] ?? '');

                            if (empty($nume) || empty($email)) continue;

                            $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                            $stmtCheck->execute([$email]);
                            $userId = $stmtCheck->fetchColumn();

                            if ($userId) {
                                $stmtUpd = $pdo->prepare("UPDATE users SET name = ?, role = ?, specialization = ? WHERE id = ?");
                                $stmtUpd->execute([$nume, $rol, $spec ?: null, $userId]);
                            } else {
                                $passHash = password_hash('KimUser123!', PASSWORD_BCRYPT);
                                $stmtIns = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, specialization) VALUES (?, ?, ?, ?, ?)");
                                $stmtIns->execute([$nume, $email, $passHash, $rol, $spec ?: null]);
                            }
                            $importedCount++;
                        }
                    }
                    fclose($handle);
                }
            } elseif ($ext === 'xml') {
                $xmlString = file_get_contents($filePath);
                $xml = simplexml_load_string($xmlString);
                if ($xml === FALSE) {
                    throw new Exception("Format XML invalid");
                }

                foreach ($xml->children() as $specialist) {
                    $nume = trim((string)$specialist->nume);
                    $email = trim((string)$specialist->email);
                    $rol = trim((string)$specialist->rol ?: 'trainer');
                    $spec = trim((string)$specialist->specializare);

                    if (empty($nume) || empty($email)) continue;

                    $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                    $stmtCheck->execute([$email]);
                    $userId = $stmtCheck->fetchColumn();

                    if ($userId) {
                        $stmtUpd = $pdo->prepare("UPDATE users SET name = ?, role = ?, specialization = ? WHERE id = ?");
                        $stmtUpd->execute([$nume, $rol, $spec ?: null, $userId]);
                    } else {
                        $passHash = password_hash('KimUser123!', PASSWORD_BCRYPT);
                        $stmtIns = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, specialization) VALUES (?, ?, ?, ?, ?)");
                        $stmtIns->execute([$nume, $email, $passHash, $rol, $spec ?: null]);
                    }
                    $importedCount++;
                }
            } else {
                throw new Exception("Extensie nesuportata. Se accepta doar CSV si XML.");
            }

            $pdo->commit();
            echo json_encode(["status" => "ok", "mesaj" => "Import finalizat cu succes! Am importat/actualizat {$importedCount} inregistrari."]);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(["eroare" => $e->getMessage()]);
        }
    }
}
