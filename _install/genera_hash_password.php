<?php
/**
 * Genera l'hash per il campo password_hash della tabella users.
 * Uso: apri nel browser con ?p=LaTuaPassword (es. genera_hash_password.php?p=admin123)
 * Copia l'hash e incollalo in phpMyAdmin nel campo password_hash.
 * ELIMINA QUESTO FILE dopo l'uso per sicurezza.
 */
header('Content-Type: text/plain; charset=utf-8');
$p = isset($_GET['p']) ? $_GET['p'] : '';
if ($p === '') {
    echo "Uso: aggiungi ?p=TuaPassword all'URL\n";
    echo "Esempio: genera_hash_password.php?p=admin123\n";
    exit;
}
echo password_hash($p, PASSWORD_DEFAULT);
