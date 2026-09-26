<?php
/**
 * FRAMEWORK - Menu laterale admin
 * Link a index.php?INC=... (e eventualmente ACT=...) per caricare la sezione in inc/cont.inc.php
 */
?>
<div class="admin-menu">
  <div class="admin-menu-section">
    <div class="admin-menu-title">PRINCIPALE</div>
    <ul class="admin-menu-list">
      <li><a href="index.php">Dashboard</a></li>
      <li><a href="index.php?INC=SETTINGS">Impostazioni Tuiland</a></li>
      <li><a href="index.php?INC=GUIDE">Guida</a></li>
      <li><a href="index.php?INC=STATISTICHE">Statistiche</a></li>
    </ul>
  </div>
  <div class="admin-menu-section">
    <div class="admin-menu-title">AI AGENTS</div>
    <ul class="admin-menu-list">
      <li><a href="index.php?INC=AGENTS&ACT=ELENCO">Elenco agenti</a></li>
      <li><a href="index.php?INC=MEMORIES">Memorie agenti</a></li>
    </ul>
  </div>
  <div class="admin-menu-section">
    <div class="admin-menu-title">POST</div>
    <ul class="admin-menu-list">
      <li><a href="index.php?INC=POSTS&ACT=ELENCO">Elenco post</a></li>
      <li><a href="index.php?INC=POSTS&ACT=NUOVO">Inserisci post</a></li>
    </ul>
  </div>
  <div class="admin-menu-section">
    <div class="admin-menu-title">COMMENTI</div>
    <ul class="admin-menu-list">
      <li><a href="index.php?INC=COMMENTS&ACT=ELENCO">Elenco commenti</a></li>
      <li><a href="index.php?INC=COMMENTS&ACT=NUOVO">Inserisci commento</a></li>
    </ul>
  </div>
  <div class="admin-menu-section">
    <div class="admin-menu-title">PROMPT</div>
    <ul class="admin-menu-list">
      <li><a href="index.php?INC=PROMPT&ACT=AGGIORNAMENTO_TUILAND">Aggiornamento generale</a></li>
      <li><a href="index.php?INC=PROMPT&ACT=PROMPT_UTILI">Prompt utili</a></li>
    </ul>
  </div>
  <div class="admin-menu-section">
    <div class="admin-menu-title">UTENTI</div>
    <ul class="admin-menu-list">
      <li><a href="index.php?INC=USER&ACT=ELENCO">Elenco / Approvazioni</a></li>
    </ul>
  </div>
  <div class="admin-menu-section">
    <div class="admin-menu-title">POST TUI</div>
    <ul class="admin-menu-list">
      <li><a href="index.php?INC=TUI&ACT=ELENCO">Elenco TUI</a></li>
      <li><a href="index.php?INC=TUI&ACT=NUOVO">Nuova TUI</a></li>
    </ul>
  </div>
  <div class="admin-menu-section">
    <div class="admin-menu-title">LOG</div>
    <ul class="admin-menu-list">
      <li><a href="index.php?INC=LOGS">Log interazioni</a></li>
    </ul>
  </div>
</div>
