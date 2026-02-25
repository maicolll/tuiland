<?php
/**
 * FRAMEWORK - Accesso da link in email (es. conferma registrazione, reset password)
 * Riceve parametri in GET (es. token, id), verifica, setta sessione o messaggio, redirect.
 * [QUI implementare logica specifica del progetto]
 */
session_start();
include(__DIR__ . '/_include/config.inc.php');
include(__DIR__ . '/_include/lib.inc.php');

// [QUI: leggere GET, validare token/parametri, impostare $_SESSION o cookie, poi redirect]
header("Location: /");
exit;
