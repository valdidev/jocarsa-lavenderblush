<?php
/****************************************************
 * 0) START SESSION & SETUP DB
 ****************************************************/
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
    // Create schema on first run
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            username TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL
        );
        
        CREATE TABLE IF NOT EXISTS projects (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            project_name TEXT NOT NULL,
            FOREIGN KEY(user_id) REFERENCES users(id)
        );
        
        CREATE TABLE IF NOT EXISTS classes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            project_id INTEGER NOT NULL,
            class_name TEXT NOT NULL,
            properties TEXT,
            methods TEXT,
            FOREIGN KEY(project_id) REFERENCES projects(id)
        );
    ");

    // Insert initial user (demo: plain password)
    $stmt = $db->prepare("iNSERT INTO users (name, email, username, password) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        'Fernando Valdivielso',
        'iamvaldidev@gmail.com',
        'valdidev',
        'valdidev'
    ]);
}

// 1) Attempt to add columns pos_x and pos_y (ignore if they exist).
try {
    $db->exec("ALTER TABLE classes ADD COLUMN pos_x REAL DEFAULT 250");
    $db->exec("ALTER TABLE classes ADD COLUMN pos_y REAL DEFAULT 250");
} catch (Exception $e) {
    // If columns already exist, ignore
}

/****************************************************
 * 1) HELPER FUNCTIONS
 ****************************************************/

function logged_in_user_id() {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
}

function require_login() {
    if (!logged_in_user_id()) {
        header("Location: index.php");
        exit;
    }
}

function set_message($msg) {
    $_SESSION['flash_msg'] = $msg;
}

function get_message() {
    if (!empty($_SESSION['flash_msg'])) {
        $msg = $_SESSION['flash_msg'];
        unset($_SESSION['flash_msg']);
        return $msg;
    }
    return "";
}

function redirect($url) {
    header("Location: $url");
    exit;
}

/****************************************************
 * 2) HANDLE ACTIONS (LOGIN, LOGOUT, CREATE PROJECT, SELECT PROJECT)
 ****************************************************/

// Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    redirect('index.php');
}

// Login
if (isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
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

// Create project
if (isset($_GET['action']) && $_GET['action'] === 'create_project') {
    require_login();
    if (!empty($_POST['project_name'])) {
        $stmt = $db->prepare("INSERT INTO projects (user_id, project_name) VALUES (?, ?)");
        $stmt->execute([logged_in_user_id(), $_POST['project_name']]);
        // Immediately select the newly created project
        $_SESSION['project_id'] = $db->lastInsertId();
        set_message("Project created: " . htmlspecialchars($_POST['project_name']));
    }
    redirect('index.php');
}

// Select project
if (isset($_GET['action']) && $_GET['action'] === 'select_project') {
    require_login();
    if (!empty($_POST['project_id'])) {
        // Check if project belongs to user
        $stmt = $db->prepare("SELECT id FROM projects WHERE id = ? AND user_id = ?");
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

/****************************************************
 * 3) HANDLE AJAX FOR SAVING / LOADING CLASSES
 ****************************************************/

// Save classes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['ajax']) && $_GET['ajax'] === 'save_classes') {
    require_login();
    // Must have a valid project in session
    $projectId = $_SESSION['project_id'] ?? 0;
    if (!$projectId) {
        http_response_code(400);
        echo "No project selected.";
        exit;
    }

    // Check that this project belongs to the logged in user
    $stmt = $db->prepare("SELECT id FROM projects WHERE id = ? AND user_id = ?");
    $stmt->execute([$projectId, logged_in_user_id()]);
    $proj = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$proj) {
        http_response_code(403);
        echo "Invalid project.";
        exit;
    }

    // Read JSON
    $rawJson = file_get_contents("php://input");
    $classesData = json_decode($rawJson, true);
    if (!is_array($classesData)) {
        http_response_code(400);
        echo "Invalid JSON data.";
        exit;
    }

    // Save to DB: remove old classes, insert new
    $db->beginTransaction();
    try {
        // delete any old classes for this project
        $del = $db->prepare("DELETE FROM classes WHERE project_id = ?");
        $del->execute([$projectId]);

        // re-insert
        $ins = $db->prepare("
            INSERT INTO classes (project_id, class_name, properties, methods, pos_x, pos_y) 
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
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax']) && $_GET['ajax'] === 'load_classes') {
    require_login();
    $projectId = $_SESSION['project_id'] ?? 0;
    if (!$projectId) {
        echo json_encode(["error" => "No project selected."]);
        exit;
    }

    // Verify project ownership
    $stmt = $db->prepare("SELECT id FROM projects WHERE id = ? AND user_id = ?");
    $stmt->execute([$projectId, logged_in_user_id()]);
    $proj = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$proj) {
        echo json_encode(["error" => "Invalid project."]);
        exit;
    }

    $stmt = $db->prepare("SELECT class_name, properties, methods, pos_x, pos_y FROM classes WHERE project_id = ?");
    $stmt->execute([$projectId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'className' => $r['class_name'],
            'properties' => json_decode($r['properties'], true),
            'methods'    => json_decode($r['methods'], true),
            'x'          => (float)$r['pos_x'],
            'y'          => (float)$r['pos_y'],
        ];
    }
    echo json_encode($out);
    exit;
}

/****************************************************
 * 4) IF NOT LOGGED IN => SHOW LOGIN PAGE
 ****************************************************/
if (!logged_in_user_id()):
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>LavenderDiagram - Login</title>
    <style>
    @import url('https://static.jocarsa.com/fuentes/ubuntu-font-family-0.83/ubuntu.css');
        body {
          margin:0; padding:0;
          font-family: Ubuntu, sans-serif;
          background: linear-gradient(120deg, #ffeef3, #ffdff0);
          display:flex; 
          align-items:center; 
          justify-content:center; 
          height:100vh;
        }
        .login-box {
          background:white;
          padding:30px;
          border-radius:10px;
          box-shadow: 0 2px 20px rgba(0,0,0,0.2);
          max-width:300px;
          width:100%;
        }
        h1 {
          text-align:center;
          margin-bottom:20px;
        }
        .flash-msg {
          color: red;
          text-align: center;
          margin-bottom:10px;
        }
        label {
          display:block;
          margin:10px 0 5px;
        }
        input[type=text],
        input[type=password] {
          width: 100%;
          padding:10px;
          box-sizing: border-box;
          border:1px solid #ddd;
          border-radius:5px;
        }
        button {
          margin-top:15px;
          width:100%;
          padding:10px;
          background: #f8b2cd;
          border:none;
          border-radius:5px;
          cursor:pointer;
          font-weight:bold;
        }
        button:hover {
          background: #ffa6c9;
        }
        .login-box img{
        		width:100%;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h1>LavenderDiagram</h1>
        <?php 
        $msg = get_message();
        if ($msg): ?>
          <div class="flash-msg"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        <form method="post">
         	<img src="lavenderblush.png" alt="Logo" />
            <input type="hidden" name="action" value="login">
            <label>Username</label>
            <input type="text" name="username" required>
            <label>Password</label>
            <input type="password" name="password" required>
            <button type="submit">Log In</button>
        </form>
    </div>
</body>
</html>
<?php
exit;
endif; // end if not logged in

/****************************************************
 * 5) LOGGED IN => SHOW MAIN UI
 ****************************************************/
// Fetch user projects
$stmt = $db->prepare("SELECT id, project_name FROM projects WHERE user_id = ?");
$stmt->execute([logged_in_user_id()]);
$userProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// If session project not in DB or not set, we do not select one
$currentProjectId = $_SESSION['project_id'] ?? 0;
$currentProjectName = "";
if ($currentProjectId) {
    // Validate it
    $stmt = $db->prepare("SELECT id, project_name FROM projects WHERE id = ? AND user_id = ?");
    $stmt->execute([$currentProjectId, logged_in_user_id()]);
    $currentProject = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$currentProject) {
        // Reset if invalid
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
    <title> Multiuser / Multiproject</title>
    <style>
@import url('https://static.jocarsa.com/fuentes/ubuntu-font-family-0.83/ubuntu.css');

* {
  margin:0; 
  padding:0; 
  box-sizing:border-box;
}
body {
  font-family: Ubuntu, sans-serif;
  background: #fff;
  color: #333;
  height:100vh; 
  display:flex; 
  flex-direction:column;
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
  padding:10px;
  text-align:center;
}

/* Main container: nav + workspace */
.container {
  flex:1; 
  display:flex;
}

/* Left navigation panel */
nav {
  width: 250px;
  background: linear-gradient(180deg, #fff0f5, #ffe1ec);
  border-right: 2px solid #ddd;
  padding: 20px;
  box-shadow: 4px 0 10px rgba(0,0,0,0.1);
  overflow:auto;
}
nav h3 {
  margin-bottom:10px;
  color: #c71585;
}
nav form {
  margin-bottom:20px;
  background:#fff;
  padding:10px;
  border-radius:5px;
  box-shadow: 0 0 10px rgba(0,0,0,0.05);
}
nav form label {
  display:block;
  margin-bottom:5px;
  font-weight:bold;
}
nav form input[type="text"],
nav form select {
  width:100%;
  padding:6px;
  margin-bottom:10px;
  border:1px solid #ccc;
  border-radius:4px;
}
nav form button,
nav a.nav-button {
  display:inline-block;
  padding:8px 12px;
  margin-right:5px;
  border:none;
  border-radius:4px;
  background: #f8b2cd;
  color:#333;
  cursor:pointer;
  text-decoration:none;
  text-align:center;
  font-weight:bold;
}
nav form button:hover,
nav a.nav-button:hover {
  background: #ff9ebe;
}

/* Draggable workspace */
main {
  flex:1; 
  position:relative;
  overflow:auto; 
  background: #fafafa;
  box-shadow: inset 0 0 15px rgba(0,0,0,0.1);
}

/* Draggable articles */
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
  padding:5px;
  font-weight:bold;
  text-align:center;
}
.draggable .propiedades,
.draggable .metodos {
  padding:8px;
}
.draggable p {
  font-weight: bold;
  margin-bottom:5px;
}
.draggable ul {
  padding-left:20px;
  list-style: disc;
}
.draggable ul li {
  margin-bottom:5px;
}

/* ContentEditable placeholders styling (optional) */
[contenteditable="true"]:empty:before {
  content: attr(placeholder);
  color: #aaa;
}

/* scroll handling for main if needed */
main {
  overflow:auto;
}
    </style>
</head>
<body>

<header>
  <img src="lavenderblush.png" alt="Logo" />
  jocarsa | lavenderblush
</header>

<?php 
// Display success/error messages
$msg = get_message();
if ($msg): ?>
  <div class="flash-msg"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="container">
  <nav>
    <h3>Projects</h3>
    <!-- Create project form -->
    <form method="post" action="?action=create_project">
      <label for="pname">New project</label>
      <input type="text" id="pname" name="project_name" placeholder="Enter project name" required>
      <button type="submit">Create</button>
    </form>

    <!-- Select existing project -->
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
    <!-- Draggable article template -->
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
// 1) Draggable setup
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

// 2) Gather classes from DOM
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

    // parseInt for left & top to store them as numbers
    const xPos = parseInt(a.style.left, 10) || 250;
    const yPos = parseInt(a.style.top, 10)  || 250;

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

// 3) List classes in console
function listClasses() {
  console.log(getClasses());
}

// 4) Save classes (AJAX -> index.php?ajax=save_classes)
function saveClasses() {
  const data = getClasses();
  fetch('index.php?ajax=save_classes', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify(data)
  })
  .then(r => r.text())
  .then(msg => {
    alert(msg);
    console.log(msg);
  })
  .catch(err => console.error("Error saving classes:", err));
}

// 5) Load classes for the selected project
function loadClasses() {
  fetch('index.php?ajax=load_classes')
    .then(r => r.json())
    .then(data => {
      if (Array.isArray(data)) {
        // Clear existing
        document.querySelectorAll("article.draggable").forEach(a => a.remove());
        // Add each
        data.forEach(cls => {
          const tpl = document.getElementById("article-template");
          const clone = tpl.content.cloneNode(true);
          const article = clone.querySelector("article");

          // Class name
          article.querySelector(".nombre").textContent = cls.className;

          // properties
          const ulProps = article.querySelector(".propiedades ul");
          ulProps.innerHTML = "";
          (cls.properties || []).forEach(p => {
            const li = document.createElement("li");
            li.textContent = p;
            ulProps.appendChild(li);
          });

          // methods
          const ulMets = article.querySelector(".metodos ul");
          ulMets.innerHTML = "";
          (cls.methods || []).forEach(m => {
            const li = document.createElement("li");
            li.textContent = m;
            ulMets.appendChild(li);
          });

          // Position
          article.style.left = (cls.x || 250) + "px";
          article.style.top  = (cls.y || 250) + "px";

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

  // On page load, load classes for the selected project (if any)
  loadClasses();
});
</script>
</body>
</html>

