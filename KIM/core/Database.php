<?php

namespace Core;

use PDO;
use PDOException;

// Clasa Singleton pentru gestionarea conexiunii cu baza de date SQLite
class Database {
    private static $connection = null;

    // Returneaza conexiunea PDO existenta sau creeaza una noua daca nu exista
    public static function getConnection() {
        if (self::$connection === null) {
            $dbPath = __DIR__ . '/../database/kim.sqlite';
            
            try {
                self::$connection = new PDO("sqlite:" . $dbPath);
                self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                self::$connection->exec('PRAGMA foreign_keys = ON;');
            } catch (PDOException $e) {
                die("Eroare la baza de date: " . $e->getMessage());
            }
        }

        return self::$connection;
    }
}
