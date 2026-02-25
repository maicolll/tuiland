<?php
/**
 * FRAMEWORK - Esempio form in area privata
 * Form → POST a funzioni.php (ACT=FEEDBACK_DO) → redirect a ?CONT=MESSAGGIO&MESS=FEEDBACK_OK
 */
$bp = $CONF["base_path"] ?? '';
?>
<div class="content-central-inner">
	<h2 id="titolo1">Feedback (esempio form area riservata)</h2>
	<p>Invio da area privata: stesso pattern (form → funzioni.php → redirect alla pagina risultato).</p>

	<form method="post" action="<?php echo htmlspecialchars($bp); ?>/funzioni.php" class="form-esempio">
		<input type="hidden" name="ACT" value="FEEDBACK_DO"/>
		<p>
			<label for="feedback_oggetto">Oggetto *</label><br/>
			<input type="text" id="feedback_oggetto" name="oggetto" required maxlength="200"/>
		</p>
		<p>
			<label for="feedback_testo">Testo *</label><br/>
			<textarea id="feedback_testo" name="testo" rows="4" required></textarea>
		</p>
		<p>
			<button type="submit">Invia feedback</button>
			<a href="<?php echo htmlspecialchars($bp); ?>/" class="btn-link">Annulla</a>
		</p>
	</form>

	<p class="form-esempio-notes"><small>In funzioni.php il case <code>FEEDBACK_DO</code> esegue l'operazione e fa <code>header("Location: ...?CONT=MESSAGGIO&amp;MESS=FEEDBACK_OK");</code>. Si viene reindirizzati nell'area privata alla pagina messaggio.</small></p>
</div>
