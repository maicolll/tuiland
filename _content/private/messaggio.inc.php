<?php
/**
 * FRAMEWORK - Pagina risultato in area privata (es. dopo invio form)
 * Legge GET MESS= e mostra il messaggio. Link per tornare alla home o al form.
 */
$MESS = $_GET['MESS'] ?? '';
$bp = $CONF["base_path"] ?? '';
$testi = [
    'FEEDBACK_OK'   => 'Feedback inviato con successo.',
    'FEEDBACK_ERRORE' => 'Errore nell\'invio. Riprova.',
    // [QUI altri codici messaggio area riservata]
];
$msg = $testi[$MESS] ?? 'Operazione completata.';
?>
<div class="content-central-inner">
    <p class="messaggio"><?php echo htmlspecialchars($msg); ?></p>
    <p>
        <a href="<?php echo htmlspecialchars($bp); ?>/">Torna alla home</a>
        <?php if ($MESS === 'FEEDBACK_OK' || $MESS === 'FEEDBACK_ERRORE') : ?>
            | <a href="<?php echo htmlspecialchars($bp); ?>/?CONT=FEEDBACK">Invia altro feedback</a>
        <?php endif; ?>
    </p>
</div>
