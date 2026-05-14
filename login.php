<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AssaggiaMente — Accedi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="auth-container">
    <div class="auth-card">
        <span class="auth-logo">AssaggiaMente</span>
        <p class="auth-subtitle">Recensisci i tuoi ristoranti preferiti</p>

        <div class="auth-tabs">
            <button class="tab-btn active" onclick="showTab('login')">Accedi</button>
            <button class="tab-btn" onclick="showTab('register')">Registrati</button>
        </div>

        <div id="login-tab" class="auth-form">
            <input type="email"    id="login-email"    placeholder="Indirizzo email" required>
            <input type="password" id="login-password" placeholder="Password" required>
            <button onclick="login()">Accedi</button>
        </div>

        <div id="register-tab" class="auth-form" style="display:none">
            <input type="text"     id="reg-name"     placeholder="Nome e cognome" required>
            <input type="email"    id="reg-email"    placeholder="Indirizzo email" required>
            <input type="password" id="reg-password" placeholder="Password (min. 6 caratteri)" required>
            <button onclick="register()">Crea account</button>
        </div>
    </div>
</div>

<script src="script.js"></script>
</body>
</html>