<?php
require_once 'db.php';
require_once 'helpers.php';

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    redirect('index.php');
}

if (isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $db->prepare("sELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['password'] === $password) {
        $_SESSION['user_id'] = $user['id'];
        redirect('index.php');
    } else {
        set_message("Username or password incorrect.");
        redirect('index.php');
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'create_project') {
    require_login();
    if (!empty($_POST['project_name'])) {
        $stmt = $db->prepare("iNSERT INTO projects (user_id, project_name) VALUES (?, ?)");
        $stmt->execute([logged_in_user_id(), $_POST['project_name']]);
        $_SESSION['project_id'] = $db->lastInsertId();
        set_message("Project created: " . htmlspecialchars($_POST['project_name']));
    }
    redirect('index.php');
}

if (isset($_GET['action']) && $_GET['action'] === 'select_project') {
    require_login();
    if (!empty($_POST['project_id'])) {
        $stmt = $db->prepare("sELECT id FROM projects WHERE id = ? AND user_id = ?");
        $stmt->execute([$_POST['project_id'], logged_in_user_id()]);
        $proj = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($proj) {
            $_SESSION['project_id'] = $proj['id'];
            set_message("Project selected.");
        } else {
            set_message("Invalid project selected.");
        }
    }
    redirect('index.php');
}
