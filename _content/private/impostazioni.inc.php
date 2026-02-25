<?php
/**
 * Impostazioni: tema (dark mode) e cambio password.
 */
$bp = $CONF["base_path"] ?? '';
$mess = $_GET['MESS'] ?? '';
$my_darkmode = $my_darkmode ?? 'S';
?>
<div class="content-central content-central-inner">
    <h2 class="text-xl font-semibold mb-4">Impostazioni</h2>

    <?php if ($mess === 'IMPOSTAZIONI_OK'): ?>
    <p class="mb-4 p-3 rounded bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 text-sm">Impostazioni salvate.</p>
    <?php endif; ?>
    <?php if ($mess === 'PASSWORD_OK'): ?>
    <p class="mb-4 p-3 rounded bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 text-sm">Password aggiornata.</p>
    <?php endif; ?>
    <?php if ($mess === 'PASSWORD_ERRORE'): ?>
    <p class="mb-4 p-3 rounded bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 text-sm">Password attuale non corretta o nuova password non valida.</p>
    <?php endif; ?>

    <div class="rounded-xl bg-white dark:bg-gray-800/80 p-6 mb-6">
        <h3 class="text-lg font-medium mb-3">Tema</h3>
        <form method="post" action="<?php echo htmlspecialchars($bp); ?>/funzioni.php" class="space-y-2">
            <input type="hidden" name="ACT" value="UPDATE_IMPOSTAZIONI"/>
            <div class="flex flex-wrap gap-4">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="darkmode" value="N" <?php echo $my_darkmode === 'N' ? 'checked' : ''; ?>/>
                    <span>Chiaro</span>
                </label>
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="darkmode" value="Y" <?php echo $my_darkmode === 'Y' ? 'checked' : ''; ?>/>
                    <span>Scuro</span>
                </label>
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="darkmode" value="S" <?php echo $my_darkmode === 'S' || $my_darkmode === null ? 'checked' : ''; ?>/>
                    <span>Segui sistema</span>
                </label>
            </div>
            <p class="pt-2"><button type="submit" class="px-4 py-2 rounded bg-[var(--color-nav-bg)] text-white text-sm font-medium">Salva tema</button></p>
        </form>
    </div>

    <div class="rounded-xl bg-white dark:bg-gray-800/80 p-6">
        <h3 class="text-lg font-medium mb-3">Cambio password</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Se accedi tramite il link inviato per email, non hai una password: per entrare richiedi un nuovo link dalla pagina <a href="<?php echo htmlspecialchars($bp); ?>/?ACT=LOGIN" class="text-blue-600 dark:text-blue-400 underline">Accedi</a>. Qui puoi impostare una password (opzionale) se ne avevi già una.</p>
        <form method="post" action="<?php echo htmlspecialchars($bp); ?>/funzioni.php" class="space-y-3 max-w-sm">
            <input type="hidden" name="ACT" value="CAMBIO_PASSWORD"/>
            <p>
                <label for="pwd_current" class="block text-sm font-medium mb-1">Password attuale</label>
                <input type="password" id="pwd_current" name="password_current" required autocomplete="current-password" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800"/>
            </p>
            <p>
                <label for="pwd_new" class="block text-sm font-medium mb-1">Nuova password (min. 6 caratteri)</label>
                <input type="password" id="pwd_new" name="password_new" required minlength="6" autocomplete="new-password" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800"/>
            </p>
            <p>
                <label for="pwd_confirm" class="block text-sm font-medium mb-1">Conferma nuova password</label>
                <input type="password" id="pwd_confirm" name="password_confirm" required minlength="6" autocomplete="new-password" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800"/>
            </p>
            <p><button type="submit" class="px-4 py-2 rounded bg-gray-700 dark:bg-gray-600 text-white text-sm font-medium">Cambia password</button></p>
        </form>
    </div>
</div>
