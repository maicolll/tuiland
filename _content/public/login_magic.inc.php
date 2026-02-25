<?php
/**
 * Accedi / Registrati con magic link (passwordless).
 * Un solo form: email. Invio link per email; cliccando si accede.
 */
$bp = $CONF["base_path"] ?? '';
$err = isset($_GET['err']) ? (int)$_GET['err'] : 0;
?>
<div class="content-central content-central-inner max-w-md mx-auto">
	<h2 class="content-central-title text-xl font-semibold mb-4"><?php echo htmlspecialchars(t('Accedi a')); ?> <?php echo htmlspecialchars($CONF['nome_sito'] ?? 'Tuiland'); ?></h2>
	<p class="text-gray-600 dark:text-gray-400 mb-4"><?php echo htmlspecialchars(t('Inserisci la tua email: ti invieremo un link per accedere. Nessuna password da ricordare.')); ?></p>
	<?php if ($err === 1): ?><p class="mb-4 p-3 rounded bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 text-sm"><?php echo htmlspecialchars(t('Inserisci un indirizzo email valido.')); ?></p><?php endif; ?>
	<?php if ($err === 2): ?><p class="mb-4 p-3 rounded bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 text-sm"><?php echo htmlspecialchars(t('Errore durante la richiesta. Riprova.')); ?></p><?php endif; ?>
	<form method="post" action="<?php echo htmlspecialchars($bp); ?>/funzioni.php" class="form-login form-esempio space-y-4">
		<input type="hidden" name="ACT" value="MAGIC_REQUEST"/>
		<p>
			<label for="magic_email" class="block text-sm font-medium mb-1">Email</label>
			<input type="email" id="magic_email" name="email" required autocomplete="email" placeholder="nome@esempio.it" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800"/>
		</p>
		<p><button type="submit" class="btn btn-primary px-4 py-2 rounded"><?php echo htmlspecialchars(t('Invia link di accesso')); ?></button></p>
	</form>
	<p class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-600 text-center text-gray-600 dark:text-gray-400 text-sm">
		<?php echo htmlspecialchars(t('Il link arriverà in pochi secondi. Controlla anche la cartella spam. È valido 15 minuti.')); ?>
	</p>
	<p class="mt-2 text-sm"><a href="<?php echo htmlspecialchars($bp); ?>/" class="text-gray-500 dark:text-gray-400 hover:underline">← <?php echo htmlspecialchars(t('Torna al feed')); ?></a></p>
</div>
