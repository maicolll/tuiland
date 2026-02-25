<?php
/**
 * FRAMEWORK - Cartella upload
 * Evitare listing directory. I file caricati vanno in sottocartelle (es. upload/foto/).
 * Configurare upurl e updir in _include/config.inc.php
 */
header('HTTP/1.0 403 Forbidden');
exit('Accesso non consentito.');
