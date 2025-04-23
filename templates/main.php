<?php
// Fetch user projects
$stmt = $db->prepare("sELECT id, project_name FROM projects WHERE user_id = ?");
$stmt->execute([logged_in_user_id()]);
$userProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Validate current project
$currentProjectId = $_SESSION['project_id'] ?? 0;
$currentProjectName = "";
if ($currentProjectId) {
    $stmt = $db->prepare("sELECT id, project_name FROM projects WHERE id = ? AND user_id = ?");
    $stmt->execute([$currentProjectId, logged_in_user_id()]);
    $currentProject = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$currentProject) {
        $currentProjectId = 0;
        unset($_SESSION['project_id']);
    } else {
        $currentProjectName = $currentProject['project_name'];
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Multiuser / Multiproject</title>
    <link rel="stylesheet" href="templates/css/main.css">
</head>

<body>
    <header>
        <img src="lavenderblush.png" alt="Logo" />
        lavenderblush diagram
    </header>

    <?php if ($msg = get_message()): ?>
        <div class="flash-msg"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="container">
        <nav>
            <h3>Projects</h3>
            <form method="post" action="?action=create_project">
                <label for="pname">New project</label>
                <input type="text" id="pname" name="project_name" placeholder="Enter project name" required>
                <button type="submit">Create</button>
            </form>

            <?php if ($userProjects): ?>
                <form method="post" action="?action=select_project">
                    <label for="projid">Open existing:</label>
                    <select name="project_id" id="projid">
                        <?php foreach ($userProjects as $p): ?>
                            <option value="<?= $p['id'] ?>"
                                <?= ($p['id'] == $currentProjectId) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['project_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit">Open</button>
                </form>
            <?php endif; ?>

            <h3>Actions</h3>
            <a href="#" class="nav-button" id="addBtn">Añadir clase</a><br><br>
            <a href="#" class="nav-button" id="listBtn">Mostrar clases</a><br><br>
            <a href="#" class="nav-button" id="saveBtn">Guardar clases</a><br><br>
            <a href="?action=logout" class="nav-button" style="background:red;color:white;">Logout</a>
        </nav>

        <main>
            <template id="article-template">
                <article class="draggable" style="left:250px; top:250px;">
                    <div class="nombre" contenteditable="true" placeholder="Nombre de la clase">Clase</div>
                    <div class="propiedades">
                        <p>Propiedades</p>
                        <ul contenteditable="true" placeholder="Introduce tus propiedades...">
                            <li></li>
                        </ul>
                    </div>
                    <div class="metodos">
                        <p>Métodos</p>
                        <ul contenteditable="true" placeholder="Introduce tus métodos...">
                            <li></li>
                        </ul>
                    </div>
                </article>
            </template>
        </main>
    </div>

    <script src="templates/js/main.js"></script>
</body>

</html>