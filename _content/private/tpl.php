<?php
/**
 * Template area privata (utente loggato) – stesso stile della home pubblica (index logout).
 */
$bp = $CONF["base_path"] ?? '';
$CONT = $_GET['CONT'] ?? '';
$private_dark = is_dark_mode_active($my_darkmode ?? null);

// Nav area privata (CONT => etichetta)
$nav_items_private = [
    '' => 'Il tuo feed',
    'ESPLORA' => 'Esplora',
    'PROFILO' => 'Profilo',
    'IMPOSTAZIONI' => 'Impostazioni',
    'FEEDBACK' => 'Feedback',
];
if (isset($my_role) && $my_role === 'admin') {
    $nav_items_private['_admin'] = 'Admin';
}
?>
<!DOCTYPE html>
<html lang="it" class="<?php echo $private_dark ? 'dark' : ''; ?>">
<head>
	<script>
	(function(){
		var ck = "system_darkmode=";
		if (document.cookie.indexOf(ck) === -1) {
			var dark = window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches;
			document.documentElement.classList.toggle("dark", dark);
			document.cookie = "system_darkmode=" + (dark ? "true" : "false") + "; path=<?php echo addslashes($bp ? $bp . '/' : '/'); ?>; max-age=" + (365*24*60*60) + "; SameSite=Lax";
		} else {
			var m = document.cookie.match(/system_darkmode=([^;]+)/);
			document.documentElement.classList.toggle("dark", m && (m[1] === "true" || m[1] === "Y"));
		}
		document.documentElement.classList.add("theme-resolved");
	})();
	</script>
	<meta charset="utf-8"/>
	<meta name="viewport" content="width=device-width, initial-scale=1"/>
	<meta name="color-scheme" content="light dark"/>
	<title><?php echo htmlspecialchars($CONF["nome_sito"] ?? 'Tuiland'); ?></title>
	<script>tailwind = { config: { darkMode: 'class' } }; window.LINKBERRI_BASE_PATH = <?php echo json_encode($bp ? $bp . '/' : '/'); ?>;</script>
	<link rel="shortcut icon" href="<?php echo htmlspecialchars($bp); ?>/favicons/favicon.ico" type="image/x-icon"/>
	<link rel="icon" type="image/png" sizes="32x32" href="<?php echo htmlspecialchars($bp); ?>/favicons/favicon-32x32.png"/>
	<link rel="icon" type="image/png" sizes="16x16" href="<?php echo htmlspecialchars($bp); ?>/favicons/favicon-16x16.png"/>
	<link rel="icon" type="image/png" sizes="96x96" href="<?php echo htmlspecialchars($bp); ?>/favicons/favicon-96x96.png"/>
	<link rel="apple-touch-icon" sizes="180x180" href="<?php echo htmlspecialchars($bp); ?>/favicons/apple-icon-180x180.png"/>
	<link rel="apple-touch-icon" sizes="152x152" href="<?php echo htmlspecialchars($bp); ?>/favicons/apple-icon-152x152.png"/>
	<link rel="apple-touch-icon" sizes="144x144" href="<?php echo htmlspecialchars($bp); ?>/favicons/apple-icon-144x144.png"/>
	<link rel="apple-touch-icon" sizes="120x120" href="<?php echo htmlspecialchars($bp); ?>/favicons/apple-icon-120x120.png"/>
	<link rel="apple-touch-icon" sizes="114x114" href="<?php echo htmlspecialchars($bp); ?>/favicons/apple-icon-114x114.png"/>
	<link rel="apple-touch-icon" sizes="76x76" href="<?php echo htmlspecialchars($bp); ?>/favicons/apple-icon-76x76.png"/>
	<link rel="apple-touch-icon" sizes="72x72" href="<?php echo htmlspecialchars($bp); ?>/favicons/apple-icon-72x72.png"/>
	<link rel="apple-touch-icon" sizes="60x60" href="<?php echo htmlspecialchars($bp); ?>/favicons/apple-icon-60x60.png"/>
	<link rel="apple-touch-icon" sizes="57x57" href="<?php echo htmlspecialchars($bp); ?>/favicons/apple-icon-57x57.png"/>
	<link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($bp); ?>/_css/public.css?v=14"/>
	<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="public body-bg body-text min-h-screen transition-colors duration-200">
<a href="#main" class="skip-link">Salta al contenuto principale</a>
<div class="wrapper max-w-4xl mx-auto px-2 sm:px-4 min-w-0 my-0" role="presentation">
	<header id="site-header" class="hero-panel-base min-h-[500px] py-5 px-3 sm:px-5 text-center panel-bg panel-text my-0" role="banner">
		<div class="text-center">
			<div id="logo" class="p-2.5">
				<a href="<?php echo htmlspecialchars($bp); ?>/" class="inline-block logo-link" aria-label="<?php echo htmlspecialchars($CONF["nome_sito"]); ?> - torna alla home"><img src="<?php echo htmlspecialchars($bp); ?>/images/tuiland_logo_1.png" width="86" height="86" alt="<?php echo htmlspecialchars($CONF["nome_sito"]); ?>" class="block"/></a>
				<br/>
				<strong>TuiLand AI</strong>
			</div>
			<p class="text-sm mt-2 text-gray-600 dark:text-gray-400">Sei online come <strong><?php echo htmlspecialchars($my_alias ?? 'utente'); ?></strong> · <a href="<?php echo htmlspecialchars($bp); ?>/logout.php" class="text-inherit hover:underline">Esci</a></p>
		</div>

		<nav id="nav" class="public-nav nav-bar py-2 px-2 sm:px-4 my-4 text-center nav-bg" role="navigation" aria-label="Menu principale">
			<button type="button" id="nav-toggle" class="nav-toggle" aria-label="Apri menu" aria-expanded="false" aria-controls="nav-menu" hidden>
				<span class="nav-toggle-sr">Menu</span>
				<span class="nav-toggle-icon" aria-hidden="true">
					<span class="nav-toggle-line nav-toggle-line-top"></span>
					<span class="nav-toggle-line nav-toggle-line-middle"></span>
					<span class="nav-toggle-line nav-toggle-line-bottom"></span>
				</span>
			</button>
			<ul id="nav-menu" class="nav-menu flex flex-wrap justify-center gap-4 list-none m-0 p-0" role="menubar">
				<?php foreach ($nav_items_private as $nav_cont => $nav_label):
					$is_active = ($CONT === '' && $nav_cont === '') || $CONT === $nav_cont;
					if ($nav_cont === '_admin') {
						$href = $bp . '/_admin567__/';
						$is_active = false;
					} else {
						$href = $bp . '/' . ($nav_cont !== '' ? '?CONT=' . rawurlencode($nav_cont) : '');
					}
				?>
				<li role="none"><a href="<?php echo htmlspecialchars($href); ?>" role="menuitem" class="nav-link<?php echo $is_active ? ' nav-link-active' : ''; ?>"<?php echo $is_active ? ' aria-current="page"' : ''; ?>><?php echo htmlspecialchars($nav_label); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</nav>

		<main id="main" class="text-left mt-4 main-text" role="main" tabindex="-1">
			<?php include(FRAMEWORK_ROOT . $include_content); ?>
		</main>
	</header>
</div>

<footer role="contentinfo" class="footer-footer">
	<nav class="footer-nav" aria-label="Breadcrumb">
		<ol class="breadcrumb" itemscope itemtype="https://schema.org/BreadcrumbList">
			<li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
				<a href="<?php echo htmlspecialchars($bp); ?>/" itemprop="item"><span itemprop="name">Home</span></a>
				<meta itemprop="position" content="1"/>
			</li>
			<li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
				<span class="breadcrumb-sep" aria-hidden="true">&nbsp;›&nbsp;</span>
				<span itemprop="name" aria-current="location"><?php echo htmlspecialchars($nav_items_private[$CONT] ?? 'Area riservata'); ?></span>
				<meta itemprop="position" content="2"/>
			</li>
		</ol>
	</nav>
	<div class="footer-dark-toggle" role="group" aria-label="Tema">
		<button type="button" class="footer-dark-btn" data-theme="light" aria-label="Tema chiaro" title="Tema chiaro">☀️</button>
		<button type="button" class="footer-dark-btn" data-theme="dark" aria-label="Tema scuro" title="Tema scuro">🌙</button>
		<button type="button" class="footer-dark-btn" data-theme="system" aria-label="Segui sistema" title="Segui preferenza sistema">💻</button>
	</div>
	<hr class="footer-divider" aria-hidden="true"/>
	<p class="footer-legal">
		<a href="<?php echo htmlspecialchars($bp); ?>/?ACT=PAGINE" class="footer-link">Privacy</a><span class="footer-sep" aria-hidden="true"> | </span><a href="<?php echo htmlspecialchars($bp); ?>/?ACT=PAGINE" class="footer-link">Condizioni</a>
	</p>
	<p class="footer-copy"><small><?php echo htmlspecialchars($CONF["nome_sito"]); ?> &copy; <?php echo date('Y'); ?></small></p>
</footer>

<button type="button" id="back-to-top" class="back-to-top" aria-label="Torna su">↑</button>

<script>window.FEED_I18N = <?php echo json_encode([
	'loading' => t('Caricamento…'),
	'loadMore' => t('Carica altri'),
	'readMore' => t('Leggi tutto'),
	'showLess' => t('Mostra meno')
], JSON_UNESCAPED_UNICODE); ?>;</script>
<script src="<?php echo htmlspecialchars($bp); ?>/_js/feed.js" defer></script>
<script>
(function() {
	'use strict';
	var COOKIE_NAME = 'system_darkmode';
	var COOKIE_PATH = typeof window.LINKBERRI_BASE_PATH !== 'undefined' ? window.LINKBERRI_BASE_PATH : '/';
	var COOKIE_MAX_AGE = 365 * 24 * 60 * 60;
	function setDarkCookie(value) {
		var s = COOKIE_NAME + '=' + (value || '') + '; path=' + COOKIE_PATH + '; max-age=' + COOKIE_MAX_AGE + '; SameSite=Lax';
		document.cookie = s;
	}
	function applyDark(isDark) {
		document.documentElement.classList.toggle('dark', !!isDark);
	}
	window.LINKBERRI_DARK_MODE = {
		setLight: function() { setDarkCookie('false'); applyDark(false); },
		setDark: function() { setDarkCookie('true'); applyDark(true); },
		setSystem: function() { var d = window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches; setDarkCookie(d ? 'true' : 'false'); applyDark(d); },
		isDark: function() { return document.documentElement.classList.contains('dark'); }
	};
	document.querySelectorAll('.footer-dark-btn').forEach(function(btn) {
		btn.addEventListener('click', function() {
			var theme = this.getAttribute('data-theme');
			if (theme === 'light') window.LINKBERRI_DARK_MODE.setLight();
			else if (theme === 'dark') window.LINKBERRI_DARK_MODE.setDark();
			else window.LINKBERRI_DARK_MODE.setSystem();
		});
	});
	var nav = document.getElementById('nav');
	var navToggle = document.getElementById('nav-toggle');
	var navMenu = document.getElementById('nav-menu');
	if (nav && navToggle && navMenu) {
		function showMobileMenu() {
			if (!window.matchMedia('(max-width: 767px)').matches) return;
			navToggle.setAttribute('aria-expanded', 'true');
			nav.classList.add('nav-open');
		}
		function hideMobileMenu() {
			navToggle.setAttribute('aria-expanded', 'false');
			nav.classList.remove('nav-open');
		}
		function updateToggleVisibility() {
			if (window.matchMedia('(max-width: 767px)').matches) navToggle.removeAttribute('hidden');
			else { navToggle.setAttribute('hidden', ''); hideMobileMenu(); }
		}
		navToggle.addEventListener('click', function() { nav.classList.contains('nav-open') ? hideMobileMenu() : showMobileMenu(); });
		updateToggleVisibility();
		window.addEventListener('resize', updateToggleVisibility);
		navMenu.querySelectorAll('a').forEach(function(a) { a.addEventListener('click', hideMobileMenu); });
	}
	var backToTop = document.getElementById('back-to-top');
	if (backToTop) {
		function onScroll() { backToTop.classList.toggle('visible', window.scrollY > 300); }
		window.addEventListener('scroll', onScroll, { passive: true });
		onScroll();
		backToTop.addEventListener('click', function() { window.scrollTo({ top: 0, behavior: 'smooth' }); });
	}
})();
</script>
</body>
</html>
