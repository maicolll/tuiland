<?php
/**
 * FRAMEWORK - Gestione form e azioni (POST/GET)
 *
 * I form inviano action="funzioni.php" (o con GET) e un campo ACT (o ACT in POST).
 * In base al valore di ACT si esegue un case: operazioni (DB, invio email, ecc.) e poi
 * header("Location: ...") per reindirizzare (es. alla home, a index.php?ACT=MESSAGGIO&MESS=OK).
 *
 * Pattern:
 *   - case "NOME_AZIONE": legge $_POST/$_GET, valida, esegue, header("Location: ..."); break;
 *   - per azioni che mostrano una pagina si può reindirizzare a index.php?ACT=MESSAGGIO&MESS=...
 *
 * Includere config e lib all'inizio; session_start() se serve sessione.
 */
error_reporting(E_ALL ^ E_NOTICE);
header('Content-type: text/html; charset=utf-8');
session_start();

include(__DIR__ . '/_include/config.inc.php');
include(__DIR__ . '/_include/lib.inc.php');
include(__DIR__ . '/_include/sn.inc.php');

$ACT = $_REQUEST['ACT'] ?? '';
$bp = $CONF["base_path"] ?? '';

switch ($ACT) {

    case "MAGIC":
        $token_raw = trim($_GET['token'] ?? '');
        if ($token_raw === '' || !$con) {
            header("Location: " . ($bp ? $bp . '/' : '/') . "?ACT=MESSAGGIO&MESS=MAGIC_LINK_SCADUTO");
            exit;
        }
        @mysqli_query($con, "CREATE TABLE IF NOT EXISTS magic_tokens (id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, token VARCHAR(64) NOT NULL, email VARCHAR(255) NOT NULL, expires_at DATETIME NOT NULL, used_at DATETIME DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id), UNIQUE KEY token (token), KEY email (email), KEY expires_at (expires_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $token_esc = mysqli_real_escape_string($con, $token_raw);
        $q = mysqli_query($con, "SELECT id, email, expires_at, used_at FROM magic_tokens WHERE token = '$token_esc' LIMIT 1");
        $row = $q && mysqli_num_rows($q) ? mysqli_fetch_assoc($q) : null;
        if (!$row || $row['used_at'] !== null) {
            header("Location: " . ($bp ? $bp . '/' : '/') . "?ACT=MESSAGGIO&MESS=MAGIC_LINK_SCADUTO");
            exit;
        }
        $expires = strtotime($row['expires_at']);
        if ($expires === false || $expires < time()) {
            mysqli_query($con, "UPDATE magic_tokens SET used_at = NOW() WHERE id = " . (int)$row['id'] . " LIMIT 1");
            header("Location: " . ($bp ? $bp . '/' : '/') . "?ACT=MESSAGGIO&MESS=MAGIC_LINK_SCADUTO");
            exit;
        }
        $email = $row['email'];
        $uq = mysqli_query($con, "SELECT id FROM users WHERE email = '" . mysqli_real_escape_string($con, $email) . "' LIMIT 1");
        $user = $uq && mysqli_num_rows($uq) ? mysqli_fetch_assoc($uq) : null;
        mysqli_query($con, "UPDATE magic_tokens SET used_at = NOW() WHERE id = " . (int)$row['id'] . " LIMIT 1");
        if (!$user) {
            header("Location: " . ($bp ? $bp . '/' : '/') . "?ACT=MESSAGGIO&MESS=MAGIC_LINK_SCADUTO");
            exit;
        }
        $_SESSION["ID_SESSION"] = (int) $user['id'];
        header("Location: " . ($bp ? $bp . '/' : '/'));
        exit;

    case "MAGIC_REQUEST":
        $email = trim($_POST['email'] ?? '');
        if ($email === '' || !isValidEmail($email) || !$con) {
            header("Location: " . ($bp ? $bp . '/' : '/') . "?ACT=LOGIN&err=1");
            exit;
        }
        @mysqli_query($con, "CREATE TABLE IF NOT EXISTS magic_tokens (id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, token VARCHAR(64) NOT NULL, email VARCHAR(255) NOT NULL, expires_at DATETIME NOT NULL, used_at DATETIME DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id), UNIQUE KEY token (token), KEY email (email), KEY expires_at (expires_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $email_esc = mysqli_real_escape_string($con, $email);
        $uq = mysqli_query($con, "SELECT id, alias FROM users WHERE email = '$email_esc' LIMIT 1");
        $user = $uq && mysqli_num_rows($uq) ? mysqli_fetch_assoc($uq) : null;
        if (!$user) {
            $alias = substr(explode('@', $email)[0], 0, 100);
            $alias_esc = mysqli_real_escape_string($con, $alias);
            $dummy_hash = mysqli_real_escape_string($con, password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT));
            mysqli_query($con, "INSERT INTO users (email, password_hash, alias, role, approved) VALUES ('$email_esc', '$dummy_hash', '$alias_esc', 'user', 1)");
            if (mysqli_affected_rows($con) === 0 && mysqli_errno($con) !== 1062) {
                header("Location: " . ($bp ? $bp . '/' : '/') . "?ACT=LOGIN&err=2");
                exit;
            }
        }
        $token = bin2hex(random_bytes(32));
        $token_esc = mysqli_real_escape_string($con, $token);
        $expires_at = date('Y-m-d H:i:s', time() + 900);
        $expires_esc = mysqli_real_escape_string($con, $expires_at);
        mysqli_query($con, "INSERT INTO magic_tokens (token, email, expires_at) VALUES ('$token_esc', '$email_esc', '$expires_esc')");
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'tuiland.local';
        $magic_url = $protocol . '://' . $host . ($bp ? $bp . '/' : '/') . 'funzioni.php?ACT=MAGIC&token=' . $token;
        $alias = $user['alias'] ?? substr(explode('@', $email)[0], 0, 100);
        sn_send_magic_link_email($email, $magic_url, $alias);
        header("Location: " . ($bp ? $bp . '/' : '/') . "?ACT=MESSAGGIO&MESS=MAGIC_LINK_INVIATO");
        exit;

    case "LOGIN":
    case "REGISTRAZIONE_DO":
        header("Location: " . ($bp ? $bp . '/' : '/') . "?ACT=LOGIN");
        exit;

    case "CONTATTI":
        // Esempio: form contatti. Validare $_POST, (opzionale) inviare email, poi redirect.
        $err = false;
        $nome     = trim($_POST['nome'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $messaggio = trim($_POST['messaggio'] ?? '');
        if ($nome === '') $err = true;
        if ($email === '' || !isValidEmail($email)) $err = true;
        if ($messaggio === '') $err = true;
        if ($err) {
            $_SESSION['contatti_post'] = $_POST;
            header("Location: " . ($bp ? $bp . '/' : '/') . "?ACT=CONTATTI&err=1");
            exit;
        }
        unset($_SESSION['contatti_post']);
        // [QUI: invio email es. mail($dest, $oggetto, $testo) o spedisci_email(...)]
        header("Location: " . ($bp ? $bp . '/' : '/') . "?ACT=MESSAGGIO&MESS=CONTATTI_OK");
        break;

    case "FEEDBACK_DO":
        // Esempio form da area privata: solo se loggato, poi redirect a CONT=MESSAGGIO
        if (!isset($_SESSION["ID_SESSION"])) {
            header("Location: " . ($bp ? $bp . '/' : '/'));
            exit;
        }
        $oggetto = trim($_POST['oggetto'] ?? '');
        $testo   = trim($_POST['testo'] ?? '');
        $err = ($oggetto === '' || $testo === '');
        // [QUI: salva in DB, invia email, ecc.]
        $mess = $err ? 'FEEDBACK_ERRORE' : 'FEEDBACK_OK';
        header("Location: " . ($bp ? $bp . '/' : '/') . "?CONT=MESSAGGIO&MESS=" . $mess);
        break;

    case "UPDATE_IMPOSTAZIONI":
        if (!isset($_SESSION["ID_SESSION"]) || !$con) {
            header("Location: " . ($bp ? $bp . '/' : '/'));
            exit;
        }
        $dm = $_POST['darkmode'] ?? 'S';
        if (!in_array($dm, ['Y', 'N', 'S'], true)) $dm = 'S';
        $uid = (int) $_SESSION["ID_SESSION"];
        $dm_esc = mysqli_real_escape_string($con, $dm);
        mysqli_query($con, "UPDATE users SET darkmode = '$dm_esc' WHERE id = $uid LIMIT 1");
        header("Location: " . ($bp ? $bp . '/' : '/') . "?CONT=IMPOSTAZIONI&MESS=IMPOSTAZIONI_OK");
        exit;

    case "CAMBIO_PASSWORD":
        if (!isset($_SESSION["ID_SESSION"]) || !$con) {
            header("Location: " . ($bp ? $bp . '/' : '/'));
            exit;
        }
        $pwd_current = $_POST['password_current'] ?? '';
        $pwd_new = $_POST['password_new'] ?? '';
        $pwd_confirm = $_POST['password_confirm'] ?? '';
        $uid = (int) $_SESSION["ID_SESSION"];
        $q = mysqli_query($con, "SELECT password_hash FROM users WHERE id = $uid LIMIT 1");
        $row = $q && mysqli_num_rows($q) ? mysqli_fetch_assoc($q) : null;
        $ok = false;
        if ($row && strlen($pwd_new) >= 6 && $pwd_new === $pwd_confirm && password_verify($pwd_current, $row['password_hash'])) {
            $hash = password_hash($pwd_new, PASSWORD_DEFAULT);
            $hash_esc = mysqli_real_escape_string($con, $hash);
            if (mysqli_query($con, "UPDATE users SET password_hash = '$hash_esc' WHERE id = $uid LIMIT 1")) $ok = true;
        }
        $mess = $ok ? 'PASSWORD_OK' : 'PASSWORD_ERRORE';
        header("Location: " . ($bp ? $bp . '/' : '/') . "?CONT=IMPOSTAZIONI&MESS=" . $mess);
        exit;

    case "TUI_INVIO":
        $message = trim($_POST['message_prompt'] ?? '');
        if ($message !== '' && $con) {
            @mysqli_query($con, "CREATE TABLE IF NOT EXISTS tui_prompts (id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, message TEXT NOT NULL, ip_address VARCHAR(45) DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id), KEY created_at (created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $msg_esc = mysqli_real_escape_string($con, $message);
            $ip = mysqli_real_escape_string($con, $_SERVER['REMOTE_ADDR'] ?? '');
            if (mysqli_query($con, "INSERT INTO tui_prompts (message, ip_address) VALUES ('$msg_esc', '$ip')")) {
                header("Location: " . ($bp ? $bp . '/' : '/') . "?ACT=MESSAGGIO&MESS=THANK_YOU_PROMPT");
            } else {
                header("Location: " . ($bp ? $bp . '/' : '/') . "?ACT=MESSAGGIO&MESS=TUI_ERRORE");
            }
        } else {
            header("Location: " . ($bp ? $bp . '/' : '/') . "?ACT=TUI&err=1");
        }
        exit;

    case "ESEMPIO_DO":
        header("Location: " . ($bp ? $bp . '/' : '/'));
        break;

    default:
        header("Location: " . ($bp ? $bp . '/' : '/'));
        break;
}
