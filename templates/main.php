<?php
// Fetch user projects
$stmt = $db->prepare("SELECT id, project_name FROM projects WHERE user_id = ?");
$stmt->execute([logged_in_user_id()]);
$userProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Validate current project
$currentProjectId = $_SESSION['project_id'] ?? 0;
$currentProjectName = "";
if ($currentProjectId) {
    $stmt = $db->prepare("SELECT id, project_name FROM projects WHERE id = ? AND user_id = ?");
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
    <style>
        @import url('https://static.jocarsa.com/fuentes/ubuntu-font-family-0.83/ubuntu.css');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Ubuntu, sans-serif;
            background: #fff;
            color: #333;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        header {
            background-color: lavenderblush;
            padding: 20px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            border-bottom: 2px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        header img {
            width: 60px;
            margin-right: 20px;
        }

        .flash-msg {
            color: green;
            font-weight: bold;
            padding: 10px;
            text-align: center;
        }

        .container {
            flex: 1;
            display: flex;
        }

        nav {
            width: 250px;
            background: linear-gradient(180deg, #fff0f5, #ffe1ec);
            border-right: 2px solid #ddd;
            padding: 20px;
            box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
            overflow: auto;
        }

        nav h3 {
            margin-bottom: 10px;
            color: #c71585;
        }

        nav form {
            margin-bottom: 20px;
            background: #fff;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
        }

        nav form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        nav form input[type="text"],
        nav form select {
            width: 100%;
            padding: 6px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        nav form button,
        nav a.nav-button {
            display: inline-block;
            padding: 8px 12px;
            margin-right: 5px;
            border: none;
            border-radius: 4px;
            background: #f8b2cd;
            color: #333;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            font-weight: bold;
        }

        nav form button:hover,
        nav a.nav-button:hover {
            background: #ff9ebe;
        }

        main {
            flex: 1;
            position: relative;
            overflow: auto;
            background: #fafafa;
            box-shadow: inset 0 0 15px rgba(0, 0, 0, 0.1);
        }

        .draggable {
            width: 220px;
            height: 320px;
            position: absolute;
            background: #fff;
            border: 2px solid lavenderblush;
            border-radius: 8px;
            box-shadow: 0px 5px 25px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .draggable .nombre {
            background: #c71585;
            color: white;
            padding: 5px;
            font-weight: bold;
            text-align: center;
        }

        .draggable .propiedades,
        .draggable .metodos {
            padding: 8px;
        }

        .draggable p {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .draggable ul {
            padding-left: 20px;
            list-style: disc;
        }

        .draggable ul li {
            margin-bottom: 5px;
        }

        [contenteditable="true"]:empty:before {
            content: attr(placeholder);
            color: #aaa;
        }

        main {
            overflow: auto;
        }
    </style>
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
            <a href="#" class="nav-button" id="saveBtn">Guardar clases</a>< depo <br>
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

    <script>
        function makeDraggable(el) {
            let offsetX = 0, offsetY = 0;
            let isDragging = false;

            el.addEventListener("mousedown", e => {
                isDragging = true;
                offsetX = e.clientX - el.getBoundingClientRect().left;
                offsetY = e.clientY - el.getBoundingClientRect().top;
                el.style.cursor = "grabbing";
                el.style.zIndex = 999999;
            });

            document.addEventListener("mousemove", e => {
                if (!isDragging) return;
                el.style.left = (e.clientX - offsetX) + "px";
                el.style.top = (e.clientY - offsetY) + "px";
            });

            document.addEventListener("mouseup", () => {
                isDragging = false;
                el.style.cursor = "grab";
                el.style.zIndex = 1;
            });
        }

        function getClasses() {
            const articles = document.querySelectorAll("article.draggable");
            let result = [];
            articles.forEach(a => {
                const className = a.querySelector(".nombre")?.textContent.trim() || "Clase";
                const props = [];
                a.querySelectorAll(".propiedades ul li").forEach(li => {
                    props.push(li.textContent.trim());
                });
                const mets = [];
                a.querySelectorAll(".metodos ul li").forEach(li => {
                    mets.push(li.textContent.trim());
                });

                const xPos = parseInt(a.style.left, 10) || 250;
                const yPos = parseInt(a.style.top, 10) || 250;

                result.push({
                    className: className,
                    properties: props,
                    methods: mets,
                    x: xPos,
                    y: yPos
                });
            });
            return result;
        }

        function listClasses() {
            console.log(getClasses());
        }

        function saveClasses() {
            const data = getClasses();
            fetch('index.php?ajax=save_classes', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(r => r.text())
            .then(msg => {
                alert(msg);
                console.log(msg);
            })
            .catch(err => console.error("Error saving classes:", err));
        }

        function loadClasses() {
            fetch('index.php?ajax=load_classes')
            .then(r => r.json())
            .then(data => {
                if (Array.isArray(data)) {
                    document.querySelectorAll("article.draggable").forEach(a => a.remove());
                    data.forEach(cls => {
                        const tpl = document.getElementById("article-template");
                        const clone = tpl.content.cloneNode(true);
                        const article = clone.querySelector("article");

                        article.querySelector(".nombre").textContent = cls.className;

                        const ulProps = article.querySelector(".propiedades ul");
                        ulProps.innerHTML = "";
                        (cls.properties || []).forEach(p => {
                            const li = document.createElement("li");
                            li.textContent = p;
                            ulProps.appendChild(li);
                        });

                        const ulMets = article.querySelector(".metodos ul");
                        ulMets.innerHTML = "";
                        (cls.methods || []).forEach(m => {
                            const li = document.createElement("li");
                            li.textContent = m;
                            ulMets.appendChild(li);
                        });

                        article.style.left = (cls.x || 250) + "px";
                        article.style.top = (cls.y || 250) + "px";

                        document.querySelector("main").appendChild(article);
                        makeDraggable(article);
                    });
                } else if (data.error) {
                    console.warn(data.error);
                }
            })
            .catch(err => console.error("Error loading classes:", err));
        }

        document.addEventListener("DOMContentLoaded", () => {
            const addBtn = document.getElementById("addBtn");
            const listBtn = document.getElementById("listBtn");
            const saveBtn = document.getElementById("saveBtn");
            const main = document.querySelector("main");

            addBtn.addEventListener("click", e => {
                e.preventDefault();
                const tpl = document.getElementById("article-template");
                const clone = tpl.content.cloneNode(true);
                const article = clone.querySelector("article");
                main.appendChild(article);
                makeDraggable(article);
            });

            listBtn.addEventListener("click", e => {
                e.preventDefault();
                listClasses();
            });

            saveBtn.addEventListener("click", e => {
                e.preventDefault();
                saveClasses();
            });

            loadClasses();
        });
    </script>
</body>
</html>