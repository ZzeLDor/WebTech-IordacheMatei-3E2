<?php

namespace Controllers;

use Core\Database;

// Gestioneaza calculul statisticilor administrative si exportul acestora in CSV sau XML
class StatsController {
    
    // Returneaza in format JSON toate statisticile necesare pentru graficele din tabloul de bord admin
    public function getJsonStats() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            return;
        }

        $pdo = Database::getConnection();

        $stmtSub = $pdo->query("SELECT type, count(*) as cnt FROM subscriptions GROUP BY type");
        $subsData = $stmtSub->fetchAll(\PDO::FETCH_ASSOC);

        $stmtBook = $pdo->query("
            SELECT s.category, count(b.id) as cnt 
            FROM bookings b 
            JOIN sessions s ON b.session_id = s.id 
            GROUP BY s.category
        ");
        $bookingsData = $stmtBook->fetchAll(\PDO::FETCH_ASSOC);

        $stmtRoom = $pdo->query("
            SELECT r.name as room_name, count(s.id) as cnt 
            FROM resources r 
            LEFT JOIN sessions s ON s.resource_id = r.id 
            GROUP BY r.id
        ");
        $roomsData = $stmtRoom->fetchAll(\PDO::FETCH_ASSOC);

        $totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $activeUsers = (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM subscriptions WHERE status = 'active' AND end_date >= date('now')")->fetchColumn();
        $activeUsersData = [
            "total" => $totalUsers,
            "active" => $activeUsers
        ];

        $stmtDay = $pdo->query("
            SELECT date(s.start_time) as period, count(b.id) as cnt 
            FROM bookings b 
            JOIN sessions s ON b.session_id = s.id 
            GROUP BY period 
            ORDER BY period DESC 
            LIMIT 7
        ");
        $bookingsDay = array_reverse($stmtDay->fetchAll(\PDO::FETCH_ASSOC));

        $stmtWeek = $pdo->query("
            SELECT strftime('%Y-W%W', s.start_time) as period, count(b.id) as cnt 
            FROM bookings b 
            JOIN sessions s ON b.session_id = s.id 
            GROUP BY period 
            ORDER BY period DESC 
            LIMIT 6
        ");
        $bookingsWeek = array_reverse($stmtWeek->fetchAll(\PDO::FETCH_ASSOC));

        $stmtMonth = $pdo->query("
            SELECT strftime('%Y-%m', s.start_time) as period, count(b.id) as cnt 
            FROM bookings b 
            JOIN sessions s ON b.session_id = s.id 
            GROUP BY period 
            ORDER BY period DESC 
            LIMIT 6
        ");
        $bookingsMonth = array_reverse($stmtMonth->fetchAll(\PDO::FETCH_ASSOC));

        $bookingsPeriodData = [
            "day" => $bookingsDay,
            "week" => $bookingsWeek,
            "month" => $bookingsMonth
        ];

        $stmtTop = $pdo->query("
            SELECT u.name, count(s.id) as cnt 
            FROM users u 
            JOIN sessions s ON s.trainer_id = u.id 
            WHERE u.role IN ('trainer', 'therapist')
            GROUP BY u.id 
            ORDER BY cnt DESC 
            LIMIT 5
        ");
        $topTrainersData = $stmtTop->fetchAll(\PDO::FETCH_ASSOC);

        echo json_encode([
            "status" => "ok", 
            "subscriptions" => $subsData,
            "bookings" => $bookingsData,
            "resources" => $roomsData,
            "activeUsers" => $activeUsersData,
            "bookingsPeriod" => $bookingsPeriodData,
            "topTrainers" => $topTrainersData
        ]);
    }

    // Exporta toate datele si metricile sub forma de fisier CSV descarcabil
    public function exportStatsCsv() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            return;
        }

        $pdo = Database::getConnection();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=statistici_kim.csv');

        $output = fopen('php://output', 'w');

        fputcsv($output, ['--- STATISTICI ABONAMENTE ---']);
        fputcsv($output, ['Tip Abonament', 'Numar Contracte']);
        $stmtSub = $pdo->query("SELECT type, count(*) as cnt FROM subscriptions GROUP BY type");
        foreach ($stmtSub->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            fputcsv($output, [$row['type'], $row['cnt']]);
        }

        fputcsv($output, []); 

        fputcsv($output, ['--- STATISTICI REZERVARI PE CATEGORIE ---']);
        fputcsv($output, ['Categorie', 'Numar Rezervari']);
        $stmtBook = $pdo->query("
            SELECT s.category, count(b.id) as cnt 
            FROM bookings b 
            JOIN sessions s ON b.session_id = s.id 
            GROUP BY s.category
        ");
        foreach ($stmtBook->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            fputcsv($output, [$row['category'], $row['cnt']]);
        }

        fputcsv($output, []); 

        fputcsv($output, ['--- UTILIZARE SALI ---']);
        fputcsv($output, ['Nume Sala', 'Numar Sesiuni Desfasurate']);
        $stmtRoom = $pdo->query("
            SELECT r.name as room_name, count(s.id) as cnt 
            FROM resources r 
            LEFT JOIN sessions s ON s.resource_id = r.id 
            GROUP BY r.id
        ");
        foreach ($stmtRoom->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            fputcsv($output, [$row['room_name'], $row['cnt']]);
        }

        fputcsv($output, []);
        fputcsv($output, ['--- UTILIZATORI ACTIVI ---']);
        fputcsv($output, ['Metric', 'Valoare']);
        $totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $activeUsers = (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM subscriptions WHERE status = 'active' AND end_date >= date('now')")->fetchColumn();
        fputcsv($output, ['Total Utilizatori Inregistrati', $totalUsers]);
        fputcsv($output, ['Utilizatori cu Abonament Activ', $activeUsers]);

        fputcsv($output, []);
        fputcsv($output, ['--- REZERVARI PE ZI ---']);
        fputcsv($output, ['Perioada (Zi)', 'Numar Rezervari']);
        $stmtDay = $pdo->query("
            SELECT date(s.start_time) as period, count(b.id) as cnt 
            FROM bookings b 
            JOIN sessions s ON b.session_id = s.id 
            GROUP BY period 
            ORDER BY period DESC 
            LIMIT 7
        ");
        foreach (array_reverse($stmtDay->fetchAll(\PDO::FETCH_ASSOC)) as $row) {
            fputcsv($output, [$row['period'], $row['cnt']]);
        }

        fputcsv($output, []);
        fputcsv($output, ['--- REZERVARI PE SAPTAMANA ---']);
        fputcsv($output, ['Perioada (Saptamana)', 'Numar Rezervari']);
        $stmtWeek = $pdo->query("
            SELECT strftime('%Y-W%W', s.start_time) as period, count(b.id) as cnt 
            FROM bookings b 
            JOIN sessions s ON b.session_id = s.id 
            GROUP BY period 
            ORDER BY period DESC 
            LIMIT 6
        ");
        foreach (array_reverse($stmtWeek->fetchAll(\PDO::FETCH_ASSOC)) as $row) {
            fputcsv($output, [$row['period'], $row['cnt']]);
        }

        fputcsv($output, []);
        fputcsv($output, ['--- REZERVARI PE LUNA ---']);
        fputcsv($output, ['Perioada (Luna)', 'Numar Rezervari']);
        $stmtMonth = $pdo->query("
            SELECT strftime('%Y-%m', s.start_time) as period, count(b.id) as cnt 
            FROM bookings b 
            JOIN sessions s ON b.session_id = s.id 
            GROUP BY period 
            ORDER BY period DESC 
            LIMIT 6
        ");
        foreach (array_reverse($stmtMonth->fetchAll(\PDO::FETCH_ASSOC)) as $row) {
            fputcsv($output, [$row['period'], $row['cnt']]);
        }

        fputcsv($output, []);
        fputcsv($output, ['--- TOP ANTRENORI / TERAPEUTI ---']);
        fputcsv($output, ['Nume Antrenor/Terapeut', 'Numar Sesiuni']);
        $stmtTop = $pdo->query("
            SELECT u.name, count(s.id) as cnt 
            FROM users u 
            JOIN sessions s ON s.trainer_id = u.id 
            WHERE u.role IN ('trainer', 'therapist')
            GROUP BY u.id 
            ORDER BY cnt DESC 
            LIMIT 5
        ");
        foreach ($stmtTop->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            fputcsv($output, [$row['name'], $row['cnt']]);
        }

        fclose($output);
    }

    // Exporta toate datele si metricile sub forma de structura XML compatibila academic
    public function exportStatsXml() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            return;
        }

        $pdo = Database::getConnection();

        header('Content-Type: text/xml; charset=utf-8');
        header('Content-Disposition: attachment; filename=statistici_kim.xml');

        $xml = new \SimpleXMLElement('<statistici/>');

        $subsXml = $xml->addChild('abonamente');
        $stmtSub = $pdo->query("SELECT type, count(*) as cnt FROM subscriptions GROUP BY type");
        foreach ($stmtSub->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $item = $subsXml->addChild('item');
            $item->addChild('tip', htmlspecialchars($row['type']));
            $item->addChild('valoare', $row['cnt']);
        }

        $booksXml = $xml->addChild('rezervari');
        $stmtBook = $pdo->query("
            SELECT s.category, count(b.id) as cnt 
            FROM bookings b 
            JOIN sessions s ON b.session_id = s.id 
            GROUP BY s.category
        ");
        foreach ($stmtBook->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $item = $booksXml->addChild('item');
            $item->addChild('categorie', htmlspecialchars($row['category']));
            $item->addChild('valoare', $row['cnt']);
        }

        $roomsXml = $xml->addChild('utilizare_sali');
        $stmtRoom = $pdo->query("
            SELECT r.name as room_name, count(s.id) as cnt 
            FROM resources r 
            LEFT JOIN sessions s ON s.resource_id = r.id 
            GROUP BY r.id
        ");
        foreach ($stmtRoom->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $item = $roomsXml->addChild('item');
            $item->addChild('sala', htmlspecialchars($row['room_name']));
            $item->addChild('valoare', $row['cnt']);
        }

        $usersXml = $xml->addChild('utilizatori_activi');
        $totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $activeUsers = (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM subscriptions WHERE status = 'active' AND end_date >= date('now')")->fetchColumn();
        $usersXml->addChild('total_inregistrati', $totalUsers);
        $usersXml->addChild('abonament_activ', $activeUsers);

        $periodXml = $xml->addChild('rezervari_perioada');
        
        $ziXml = $periodXml->addChild('zi');
        $stmtDay = $pdo->query("
            SELECT date(s.start_time) as period, count(b.id) as cnt 
            FROM bookings b 
            JOIN sessions s ON b.session_id = s.id 
            GROUP BY period 
            ORDER BY period DESC 
            LIMIT 7
        ");
        foreach (array_reverse($stmtDay->fetchAll(\PDO::FETCH_ASSOC)) as $row) {
            $item = $ziXml->addChild('item');
            $item->addChild('perioada', htmlspecialchars($row['period']));
            $item->addChild('valoare', $row['cnt']);
        }

        $saptXml = $periodXml->addChild('saptamana');
        $stmtWeek = $pdo->query("
            SELECT strftime('%Y-W%W', s.start_time) as period, count(b.id) as cnt 
            FROM bookings b 
            JOIN sessions s ON b.session_id = s.id 
            GROUP BY period 
            ORDER BY period DESC 
            LIMIT 6
        ");
        foreach (array_reverse($stmtWeek->fetchAll(\PDO::FETCH_ASSOC)) as $row) {
            $item = $saptXml->addChild('item');
            $item->addChild('perioada', htmlspecialchars($row['period']));
            $item->addChild('valoare', $row['cnt']);
        }

        $lunaXml = $periodXml->addChild('luna');
        $stmtMonth = $pdo->query("
            SELECT strftime('%Y-%m', s.start_time) as period, count(b.id) as cnt 
            FROM bookings b 
            JOIN sessions s ON b.session_id = s.id 
            GROUP BY period 
            ORDER BY period DESC 
            LIMIT 6
        ");
        foreach (array_reverse($stmtMonth->fetchAll(\PDO::FETCH_ASSOC)) as $row) {
            $item = $lunaXml->addChild('item');
            $item->addChild('perioada', htmlspecialchars($row['period']));
            $item->addChild('valoare', $row['cnt']);
        }

        $trainersXml = $xml->addChild('top_antrenori');
        $stmtTop = $pdo->query("
            SELECT u.name, count(s.id) as cnt 
            FROM users u 
            JOIN sessions s ON s.trainer_id = u.id 
            WHERE u.role IN ('trainer', 'therapist')
            GROUP BY u.id 
            ORDER BY cnt DESC 
            LIMIT 5
        ");
        foreach ($stmtTop->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $item = $trainersXml->addChild('item');
            $item->addChild('nume', htmlspecialchars($row['name']));
            $item->addChild('valoare', $row['cnt']);
        }

        echo $xml->asXML();
    }
}
