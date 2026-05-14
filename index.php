<?php session_start(); ?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AssaggiaMente — Recensioni Ristoranti</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <nav>
        <a class="logo" href="index.php">AssaggiaMente</a>
        <div class="nav-links">
            <a href="#" onclick="loadRestaurants(); return false;">Ristoranti</a>

            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="#" onclick="showAddRestaurantForm(); return false;">Aggiungi</a>
                    <a href="#" onclick="showMyRestaurants(); return false;">I miei ristoranti</a>
                <?php endif; ?>
                <span class="nav-pill"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a class="nav-action" href="logout.php">Esci</a>
            <?php else: ?>
                <a class="nav-action" href="login.php">Accedi</a>
            <?php endif; ?>
        </div>
    </nav>
</header>

<main>
    <div class="page-header">
        <h1>Ristoranti</h1>
        <p>Scopri e recensisci i migliori ristoranti della zona.</p>
    </div>
    <div id="restaurants-list" class="restaurants-grid"></div>
</main>

<!-- Modale aggiunta ristorante (solo admin) -->
<div id="add-restaurant-modal" class="modal" style="display:none">
    <div class="modal-content modal-large">
        <button class="modal-close" onclick="closeAddRestaurantModal()" aria-label="Chiudi">&times;</button>
        <h3>Nuovo ristorante</h3>
        <form id="add-restaurant-form" onsubmit="return false;">
            <input type="text"  id="rest-name"    placeholder="Nome del ristorante" required>
            <input type="text"  id="rest-address" placeholder="Indirizzo" required>
            <select id="rest-price" required>
                <option value="1">€ — Economico</option>
                <option value="2">€€ — Moderato</option>
                <option value="3">€€€ — Costoso</option>
                <option value="4">€€€€ — Lusso</option>
            </select>
            <input type="url"   id="rest-image"   placeholder="URL immagine (opzionale)">
            <textarea id="rest-desc" rows="4" placeholder="Descrizione del ristorante" required></textarea>
            <button type="button" onclick="addRestaurant()">Salva ristorante</button>
        </form>
    </div>
</div>

<script src="script.js"></script>
<script>
    loadRestaurants();
</script>
</body>
</html>