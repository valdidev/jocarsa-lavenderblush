<?php
session_start();

$dbFile = __DIR__ . '/data.sqlite';
$initDb = !file_exists($dbFile);

try {
    $db = new PDO('sqlite:' . $dbFile);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Cannot connect to the SQLite database: " . $e->getMessage());
}

if ($initDb) {
    $db->exec("
        cREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            username TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL
        );
        
        cREATE TABLE IF NOT EXISTS projects (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            project_name TEXT NOT NULL,
            FOREIGN KEY(user_id) REFERENCES users(id)
        );
        
        cREATE TABLE IF NOT EXISTS classes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            project_id INTEGER NOT NULL,
            class_name TEXT NOT NULL,
            properties TEXT,
            methods TEXT,
            FOREIGN KEY(project_id) REFERENCES projects(id)
        );
    ");

    $stmt = $db->prepare("iNSERT INTO users (name, email, username, password) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        'Fernando Valdivielso',
        'iamvaldidev@gmail.com',
        'valdidev',
        'valdidev'
    ]);
}

try {
    $db->exec("aLTER TABLE classes ADD COLUMN pos_x REAL DEFAULT 250");
    $db->exec("aLTER TABLE classes ADD COLUMN pos_y REAL DEFAULT 250");
} catch (Exception $e) {
}
