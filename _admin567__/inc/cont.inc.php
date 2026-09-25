<?php
/**
 * FRAMEWORK - Router contenuto admin
 *
 * In base a INC (es. USER, FOTO, STATISTICHE) si include il file che gestisce
 * quella sezione. Il file incluso a sua volta può leggere ACT per mostrare
 * sottopagine (es. INC=USER&ACT=SCHEDA, ACT=CERCA, ACT=DA_CANCELLARE).
 *
 * Pattern: index.php?INC=USER&ACT=SCHEDA&id=123
 *   → qui include inc/user_cont.php
 *   → user_cont.php legge ACT e id e mostra scheda utente o form modifica
 */
$INC = $_REQUEST['INC'] ?? null;

switch ($INC) {
    case "USER":
        include "user_cont.php";
        break;
    case "AGENTS":
        include "agents_cont.php";
        break;
    case "MEMORIES":
        include "memories_cont.php";
        break;
    case "POSTS":
        include "posts_cont.php";
        break;
    case "COMMENTS":
        include "comments_cont.php";
        break;
    case "PROMPT":
        include "prompt_cont.php";
        break;
    case "SETTINGS":
        include "settings_cont.php";
        break;
    case "GUIDE":
        include "guide_cont.php";
        break;
    case "STATISTICHE":
        include "statistiche_cont.php";
        break;
    case "TUI":
        include "tui_cont.php";
        break;
    case "LOGS":
        include "logs_cont.php";
        break;
    case "CONTENT_UPDATES":
        include "content_updates_cont.php";
        break;
    default:
        include "dashboard_cont.php";
        break;
}
