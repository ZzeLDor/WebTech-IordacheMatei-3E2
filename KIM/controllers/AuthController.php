<?php

namespace Controllers;

use Core\Database;

// Gestioneaza procesul de autentificare si inregistrare in aplicatie
class AuthController {
    
    // Conecteaza utilizatorul in sistem si stocheaza detaliile in sesiune
    public function login() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['email']) || !isset($data['password'])) {
            http_response_code(400);
            echo json_encode(["eroare" => "Date incomplete"]);
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $data['email']]);
        $user = $stmt->fetch();

        // Verifica hash-ul parolei si salveaza sesiunea daca este corecta
        if ($user && password_verify($data['password'], $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role']    = $user['role'];
            $_SESSION['name']    = $user['name'];
            
            echo json_encode([
                "status" => "ok",
                "mesaj" => "Autentificare cu succes",
                "role" => $user['role']
            ]);
        } else {
            http_response_code(401);
            echo json_encode(["eroare" => "Email sau parola incorecta"]);
        }
    }

    // Inregistreaza un cont nou de membru in baza de date, criptand parola
    public function register() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['name']) || !isset($data['email']) || !isset($data['password'])) {
            http_response_code(400);
            echo json_encode(["eroare" => "Date incomplete"]);
            return;
        }

        $pdo = Database::getConnection();
        
        // Verifica daca adresa de email este deja folosita
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute(['email' => $data['email']]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(["eroare" => "Email deja folosit"]);
            return;
        }

        $hash = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :hash)");
        $stmt->execute([
            'name' => $data['name'],
            'email' => $data['email'],
            'hash' => $hash
        ]);

        echo json_encode(["status" => "ok", "mesaj" => "Cont creat cu succes"]);
    }
    
    // Distruge sesiunea curenta si deconecteaza utilizatorul
    public function logout() {
        session_destroy();
        echo json_encode(["status" => "ok", "mesaj" => "Deconectat"]);
    }
}
