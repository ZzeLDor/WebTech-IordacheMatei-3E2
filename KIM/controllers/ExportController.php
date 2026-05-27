<?php

namespace Controllers;

use Core\Database;

// Gestioneaza exportul listei de antrenori in formate deschise (CSV si XML)
class ExportController {
    
    // Exporta toti antrenorii si kinetoterapeutii in format CSV descarcabil
    public function exportCsv() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT id, name, email, role FROM users WHERE role IN ('trainer', 'therapist')");
        $trainers = $stmt->fetchAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=antrenori.csv');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Nume', 'Email', 'Rol']);
        foreach ($trainers as $t) {
            fputcsv($output, $t);
        }
        fclose($output);
    }

    // Exporta toti antrenorii si kinetoterapeutii in format XML structurat
    public function exportXml() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT id, name, email, role FROM users WHERE role IN ('trainer', 'therapist')");
        $trainers = $stmt->fetchAll();

        header('Content-Type: text/xml; charset=utf-8');
        header('Content-Disposition: attachment; filename=antrenori.xml');

        $xml = new \SimpleXMLElement('<antrenori/>');
        foreach ($trainers as $t) {
            $antrenor = $xml->addChild('antrenor');
            $antrenor->addChild('id', $t['id']);
            $antrenor->addChild('nume', htmlspecialchars($t['name']));
            $antrenor->addChild('email', htmlspecialchars($t['email']));
            $antrenor->addChild('rol', $t['role']);
        }

        echo $xml->asXML();
    }
}
