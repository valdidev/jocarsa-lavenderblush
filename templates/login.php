<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>LavenderDiagram - Login</title>
    <style>
        @import url('https://static.jocarsa.com/fuentes/ubuntu-font-family-0.83/ubuntu.css');

        body {
            margin: 0;
            padding: 0;
            font-family: Ubuntu, sans-serif;
            background: linear-gradient(120deg, #ffeef3, #ffdff0);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        .login-box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.2);
            max-width: 300px;
            width: 100%;
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
        }

        .flash-msg {
            color: red;
            text-align: center;
            margin-bottom: 10px;
        }

        label {
            display: block;
            margin: 10px 0 5px;
        }

        input[type=text],
        input[type=password] {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        button {
            margin-top: 15px;
            width: 100%;
            padding: 10px;
            background: #f8b2cd;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        button:hover {
            background: #ffa6c9;
        }

        .login-box img {
            width: 100%;
        }
    </style>
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