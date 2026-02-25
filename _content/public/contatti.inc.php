<?php
/**
 * FRAMEWORK - Esempio pagina con form
 * Form → POST a funzioni.php (ACT=CONTATTI) → redirect a ?ACT=MESSAGGIO&MESS=...
 * In funzioni.php il case CONTATTI valida, (opzionale) invia email, poi header("Location: ...").
 */
$bp = $CONF["base_path"] ?? '';
$precompila = [];
if (isset($_GET['err']) && $_GET['err'] === '1') {
    $precompila = $_SESSION['contatti_post'] ?? [];
}
?>
<div class="content-central content-central-inner">
	<h2 class="content-central-title"><?php echo htmlspecialchars(t('Contatti (esempio form)')); ?></h2>
	<p><?php echo htmlspecialchars(t('Compila il form e invia: verrai reindirizzato alla pagina risultato.')); ?></p>

	<form method="post" action="<?php echo htmlspecialchars($bp); ?>/funzioni.php" class="form-esempio">
		<input type="hidden" name="ACT" value="CONTATTI"/>
		<p>
			<label for="contatti_nome"><?php echo htmlspecialchars(t('Nome *')); ?></label><br/>
			<input type="text" id="contatti_nome" name="nome" required maxlength="100" value="<?php echo htmlspecialchars($precompila['nome'] ?? ''); ?>"/>
		</p>
		<p>
			<label for="contatti_email"><?php echo htmlspecialchars(t('Email')); ?> *</label><br/>
			<input type="email" id="contatti_email" name="email" required value="<?php echo htmlspecialchars($precompila['email'] ?? ''); ?>"/>
		</p>
		<p>
			<label for="contatti_messaggio"><?php echo htmlspecialchars(t('Messaggio *')); ?></label><br/>
			<textarea id="contatti_messaggio" name="messaggio" rows="5" required><?php echo htmlspecialchars($precompila['messaggio'] ?? ''); ?></textarea>
		</p>
		<p class="form-actions">
			<button type="submit" class="btn btn-primary"><?php echo htmlspecialchars(t('Invia')); ?></button>
			<a href="<?php echo htmlspecialchars($bp); ?>/" class="btn btn-secondary"><?php echo htmlspecialchars(t('Annulla')); ?></a>
		</p>
	</form>

	<p class="form-esempio-notes"><small>Flusso: questo form invia a <code>funzioni.php</code> con <code>ACT=CONTATTI</code>. In funzioni.php il case valida i campi, poi fa <code>header("Location: ...?ACT=MESSAGGIO&amp;MESS=CONTATTI_OK");</code>. La pagina messaggio mostra il risultato.</small></p>
</div>
