<?php
/**
 * Contenuto pagina login (header e footer sono inclusi da index.php nel case LOGIN).
 */
$bp = $CONF["base_path"] ?? '';
?>
<div class="content-central content-central-inner max-w-md mx-auto">
	<h2 class="content-central-title text-xl font-semibold mb-4">Accedi</h2>
	<p class="text-gray-600 dark:text-gray-400 mb-4">Inserisci email e password per entrare nel tuo account.</p>
	<form method="post" action="<?php echo htmlspecialchars($bp); ?>/funzioni.php" class="form-login form-esempio space-y-4">
		<input type="hidden" name="ACT" value="LOGIN"/>
		<p><label class="block text-sm font-medium mb-1">Email</label><input type="email" name="login_user" required autocomplete="username" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800"/></p>
		<p><label class="block text-sm font-medium mb-1">Password</label><input type="password" name="login_password" required autocomplete="current-password" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800"/></p>
		<p><button type="submit" class="btn btn-primary px-4 py-2 rounded">Accedi</button></p>
	</form>
	<p class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-600 text-center text-gray-600 dark:text-gray-400">
		Non hai un account? <a href="<?php echo htmlspecialchars($bp); ?>/?ACT=REGISTRAZIONE" class="text-blue-600 dark:text-blue-400 font-medium hover:underline">Registrati</a>
	</p>
	<p class="mt-2 text-sm"><a href="<?php echo htmlspecialchars($bp); ?>/" class="text-gray-500 dark:text-gray-400 hover:underline">← Torna al feed</a></p>
</div>
