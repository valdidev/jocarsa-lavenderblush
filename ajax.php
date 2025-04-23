<?php
require_once 'db.php';
require_once 'helpers.php';

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_GET['ajax']) &&
    $_GET['ajax'] === 'save_classes'
) {
    require_login();
    $projectId = $_SESSION['project_id'] ?? 0;
    if (!$projectId) {
        http_response_code(400);
        echo "No project selected.";
        exit;
    }

    $stmt = $db->prepare("sELECT id FROM projects WHERE id = ? AND user_id = ?");
    $stmt->execute([$projectId, logged_in_user_id()]);
    $proj = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$proj) {
        http_response_code(403);
        echo "Invalid project.";
        exit;
    }

    $rawJson = file_get_contents("php://input");
    $classesData = json_decode($rawJson, true);
    if (!is_array($classesData)) {
        http_response_code(400);
        echo "Invalid JSON data.";
        exit;
    }

    $db->beginTransaction();
    try {
        $del = $db->prepare("dELETE FROM classes WHERE project_id = ?");
        $del->execute([$projectId]);

        $ins = $db->prepare("
            iNSERT INTO classes (project_id, class_name, properties, methods, pos_x, pos_y) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        foreach ($classesData as $cls) {
            $cn = trim($cls['className'] ?? 'Clase');
            $props = isset($cls['properties']) ? json_encode($cls['properties']) : '[]';
            $mets = isset($cls['methods']) ? json_encode($cls['methods']) : '[]';
            $posX = floatval($cls['x'] ?? 250);
            $posY = floatval($cls['y'] ?? 250);

            $ins->execute([$projectId, $cn, $props, $mets, $posX, $posY]);
        }

        $db->commit();
        echo "Classes saved successfully for project #{$projectId}";
    } catch (Exception $ex) {
        $db->rollBack();
        http_response_code(500);
        echo "Error saving classes: " . $ex->getMessage();
    }
    exit;
}

// Load classes
if (
    $_SERVER['REQUEST_METHOD'] === 'GET' &&
    isset($_GET['ajax']) &&
    $_GET['ajax'] === 'load_classes'
) {
    require_login();
    $projectId = $_SESSION['project_id'] ?? 0;
    if (!$projectId) {
        echo json_encode(["error" => "No project selected."]);
        exit;
    }

    $stmt = $db->prepare("sELECT id FROM projects WHERE id = ? AND user_id = ?");
    $stmt->execute([$projectId, logged_in_user_id()]);
    $proj = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$proj) {
        echo json_encode(["error" => "Invalid project."]);
        exit;
    }

    $stmt = $db->prepare("sELECT class_name, properties, methods, pos_x, pos_y FROM classes WHERE project_id = ?");
    $stmt->execute([$projectId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'className' => $r['class_name'],
            'properties' => json_decode($r['properties'], true),
            'methods' => json_decode($r['methods'], true),
            'x' => (float)$r['pos_x'],
            'y' => (float)$r['pos_y'],
        ];
    }
    echo json_encode($out);
    exit;
}
