<?php
// Qui potresti aggiungere sessioni, autenticazione, ecc.
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quiz Manager — UniBG</title>
  <link rel="stylesheet" href="css/template.css">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

  <header id="header">
    <h1>🎓 Quiz Manager</h1>
    <span class="header-sub">Università degli Studi di Bergamo</span>
  </header>

  <nav id="navigazione">
    <a href="#" data-section="quiz"           class="active">📋 Quiz</a>
    <a href="#" data-section="utenti">👤 Utenti</a>
    <a href="#" data-section="partecipazioni">🏆 Partecipazioni</a>
  </nav>

  <div id="main-layout">
    <aside id="filtro-ricerca">
      <!-- popolato da main.js -->
    </aside>
    <main id="contenuto">
      <!-- contenuto dinamico via JS -->
    </main>
  </div>

  <footer id="footer">
    © 2025 PW26 — Programmazione Web — UniBG
  </footer>

  <script src="js/api.js"></script>
  <script src="js/crud.js"></script>
  <script src="js/main.js"></script>
</body>
</html>