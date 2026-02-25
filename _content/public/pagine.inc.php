<?php
/**
 * Pagine statiche: Privacy, Condizioni (in 3 lingue via t()).
 * $pagina_statica = 'privacy' | 'condizioni' | '' (elenco/link).
 */
$pagina_statica = isset($pagina_statica) ? trim($pagina_statica) : '';
$bp = $CONF["base_path"] ?? '';
?>
<div class="content-central content-central-inner prose dark:prose-invert max-w-none">
<?php if ($pagina_statica === 'privacy'): ?>
	<?php echo t('PAGINA_PRIVACY_HTML'); ?>
<?php elseif ($pagina_statica === 'condizioni'): ?>
	<?php echo t('PAGINA_CONDIZIONI_HTML'); ?>
<?php else: ?>
	<p class="text-gray-700 dark:text-gray-300 mb-6"><?php echo htmlspecialchars(t('[QUI contenuto pagine statiche - es. Chi siamo, Privacy, Condizioni]')); ?></p>
	<p class="flex flex-wrap gap-4">
		<a href="<?php echo htmlspecialchars(url_lang($bp . '/?ACT=PAGINE&pagina=privacy')); ?>" class="text-blue-600 dark:text-blue-400 hover:underline font-medium"><?php echo htmlspecialchars(t('Privacy')); ?></a>
		<a href="<?php echo htmlspecialchars(url_lang($bp . '/?ACT=PAGINE&pagina=condizioni')); ?>" class="text-blue-600 dark:text-blue-400 hover:underline font-medium"><?php echo htmlspecialchars(t('Condizioni')); ?></a>
	</p>
<?php endif; ?>
</div>
