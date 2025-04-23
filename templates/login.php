<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>LavenderDiagram - Login</title>
    <link rel="stylesheet" href="templates/css/login.css">
</head>

<body>
    <div class="login-box">
        <h1>LavenderDiagram</h1>
        <?php if ($msg = get_message()): ?>
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