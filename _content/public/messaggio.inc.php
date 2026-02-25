<?php
/**
 * FRAMEWORK - Pagina risultato (es. dopo invio form)
 * Legge GET MESS= e mostra il testo corrispondente. Link per tornare alla home o al form.
 */
$MESS = $_GET['MESS'] ?? '';
$bp = $CONF["base_path"] ?? '';
$testi = [
    'CONTATTI_OK'   => 'Messaggio inviato con successo.',
    'CONTATTI_ERRORE' => 'Si è verificato un errore. Controlla i campi e riprova.',
    'LOGIN_ERRORE'  => 'Accesso non riuscito. Usa il link che ti abbiamo inviato per email. Se ti sei appena registrato, attendi l\'approvazione da parte dell\'amministratore.',
    'REGISTRAZIONE_IN_ATTESA' => 'Registrazione completata. Riceverai l\'accesso dopo l\'approvazione da parte di un amministratore.',
    'REGISTRAZIONE_EMAIL_GIA' => 'Questa email è già registrata.',
    'REGISTRAZIONE_ERRORE' => 'Errore durante la registrazione. Riprova più tardi.',
    'MAGIC_LINK_INVIATO' => 'Ti abbiamo inviato un link di accesso alla tua email. Clicca il link per entrare (è valido 15 minuti). Controlla anche la cartella spam.',
    'MAGIC_LINK_SCADUTO' => 'Questo link non è più valido o è già stato usato. Richiedi un nuovo link dalla pagina Accedi.',
    'MAGIC_ACCOUNT_NON_APPROVATO' => 'Il tuo account è in attesa di approvazione da parte di un amministratore. Riceverai un\'email quando sarà attivato.',
    'THANK_YOU_PROMPT' => 'La tua TUI è stata inviata. Il team creativo e le IA la esamineranno con cura. Le idee più ispirate prenderanno vita nell\'universo TuiLand — in un post, in un dialogo o in una nuova avventura. Continua a seguire le storie per scoprire se la tua TUI ha acceso un fuoco.',
    'TUI_ERRORE' => 'Si è verificato un errore durante l\'invio del prompt. Riprova più tardi.',
];
$msg = $testi[$MESS] ?? 'Operazione completata.';
?>
<div class="content-central content-central-inner">
    <p class="messaggio"><?php echo htmlspecialchars(t($msg)); ?></p>
    <p>
        <a href="<?php echo htmlspecialchars($bp); ?>/"><?php echo htmlspecialchars(t('Torna alla home')); ?></a>
        <?php if ($MESS === 'CONTATTI_OK' || $MESS === 'CONTATTI_ERRORE') : ?>
            | <a href="<?php echo htmlspecialchars($bp); ?>/?ACT=CONTATTI"><?php echo htmlspecialchars(t('Invia un altro messaggio')); ?></a>
        <?php endif; ?>
        <?php if ($MESS === 'MAGIC_LINK_INVIATO' || $MESS === 'MAGIC_LINK_SCADUTO' || $MESS === 'MAGIC_ACCOUNT_NON_APPROVATO') : ?>
            | <a href="<?php echo htmlspecialchars($bp); ?>/?ACT=LOGIN"><?php echo htmlspecialchars(t('Accedi')); ?></a>
        <?php endif; ?>
        <?php if ($MESS === 'THANK_YOU_PROMPT' || $MESS === 'TUI_ERRORE') : ?>
            | <a href="<?php echo htmlspecialchars($bp); ?>/?ACT=TUI"><?php echo htmlspecialchars(t('Invia un altro prompt')); ?></a>
        <?php endif; ?>
    </p>
</div>
