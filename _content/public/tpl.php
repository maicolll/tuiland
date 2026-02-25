<?php
/**
 * Template area pubblica – layout unico (header + contenuto + footer).
 * Usato da index.php: imposta $include_content e $PAGE_TITLE, poi include questo file.
 */
$PAGE_TITLE = $PAGE_TITLE ?? $CONF["nome_sito"];
$META_DESCRIPTION = $META_DESCRIPTION ?? ($CONF["meta_description"] ?? '');
$META_KEYWORDS = $META_KEYWORDS ?? ($CONF["meta_keywords"] ?? '');
$ACT = isset($ACT) ? $ACT : ($_GET['ACT'] ?? '');
$bp = $CONF["base_path"] ?? '';
$public_dark = is_dark_mode_active(null);

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? '';
if (!empty($CANONICAL_URL)) {
    $canonical_url = $CANONICAL_URL;
} else {
    $canonical_path = $bp ? $bp . '/' : '/';
    if ($ACT !== '') $canonical_path .= '?ACT=' . rawurlencode($ACT);
    if ($ACT === 'POST' && !empty($_GET['id'])) $canonical_path .= '&id=' . (int)$_GET['id'];
    $canonical_url = $protocol . '://' . $host . $canonical_path;
}
$og_title = !empty($OG_TITLE) ? $OG_TITLE : $PAGE_TITLE;
$og_description = !empty($OG_DESCRIPTION) ? $OG_DESCRIPTION : $META_DESCRIPTION;
$og_url = !empty($OG_URL) ? $OG_URL : $canonical_url;
$og_type = !empty($OG_TYPE) ? $OG_TYPE : 'website';

$cookie_secure_attr = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? '; Secure' : '';

$nav_items = ['' => 'Home', 'COSA_E_TUILAND' => "Cos'è TuiLand", 'TUI' => 'TUI'];
if (!isset($_SESSION["ID_SESSION"])) $nav_items['LOGIN'] = 'Accedi';

$header_h1 = ($ACT === '' || $ACT === false) ? t('Home') : $PAGE_TITLE;

if (!isset($BREADCRUMB) || empty($BREADCRUMB)) {
	$BREADCRUMB = [['label' => 'Home', 'url' => $bp ? $bp . '/' : '/']];
	if ($ACT === 'COSA_E_TUILAND') $BREADCRUMB[] = ['label' => t("Cos'è TuiLand"), 'url' => ''];
	elseif ($ACT === 'TUI') $BREADCRUMB[] = ['label' => t('Textual User Intelligence'), 'url' => ''];
	elseif ($ACT === 'CONTATTI') $BREADCRUMB[] = ['label' => t('Contatti'), 'url' => ''];
	elseif ($ACT === 'REGISTRAZIONE') $BREADCRUMB[] = ['label' => t('Accedi'), 'url' => ''];
	elseif ($ACT === 'PAGINE') {
	$BREADCRUMB[] = ['label' => t('Pagine'), 'url' => (isset($bp) && $bp ? $bp . '/?ACT=PAGINE' : '/?ACT=PAGINE')];
	if (!empty($pagina_statica)) $BREADCRUMB[] = ['label' => $PAGE_TITLE, 'url' => ''];
}
	elseif ($ACT === 'MESSAGGIO') $BREADCRUMB[] = ['label' => t('Messaggio'), 'url' => ''];
	elseif ($ACT === 'LOGIN' || $ACT === 'LOGIN_DIMENTICATO') $BREADCRUMB[] = ['label' => t('Accedi'), 'url' => ''];
	elseif ($ACT === 'POST') $BREADCRUMB[] = ['label' => isset($PAGE_TITLE) ? $PAGE_TITLE : t('Post'), 'url' => ''];
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang_user ?? 'it'); ?>" class="<?php echo $public_dark ? 'dark' : ''; ?>">
<head>
	<script>
	(function(){
		var ck = "system_darkmode=";
		if (document.cookie.indexOf(ck) === -1) {
			var dark = window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches;
			document.documentElement.classList.toggle("dark", dark);
			document.cookie = "system_darkmode=" + (dark ? "true" : "false") + "; path=<?php echo addslashes($bp ? $bp . '/' : '/'); ?>; max-age=" + (365*24*60*60) + "; SameSite=Lax<?php echo $cookie_secure_attr; ?>";
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
	<title><?php echo (trim($PAGE_TITLE) !== '' && $PAGE_TITLE !== $CONF["nome_sito"]) ? htmlspecialchars($PAGE_TITLE) . ' - ' . htmlspecialchars($CONF["nome_sito"]) : htmlspecialchars($CONF["nome_sito"]); ?></title>
	<meta name="description" content="<?php echo htmlspecialchars($META_DESCRIPTION); ?>"/>
	<meta name="keywords" content="<?php echo htmlspecialchars($META_KEYWORDS); ?>"/>
	<meta name="author" content="<?php echo htmlspecialchars($CONF["meta_author"] ?? $CONF["nome_sito"]); ?>"/>
	<link rel="canonical" href="<?php echo htmlspecialchars($canonical_url); ?>"/>
	<meta property="og:title" content="<?php echo htmlspecialchars($og_title); ?>"/>
	<meta property="og:description" content="<?php echo htmlspecialchars($og_description); ?>"/>
	<meta property="og:type" content="<?php echo htmlspecialchars($og_type); ?>"/>
	<meta property="og:url" content="<?php echo htmlspecialchars($og_url); ?>"/>
	<?php if (!empty($CONF['fb_app_id'])): ?>
	<meta property="fb:app_id" content="<?php echo htmlspecialchars(trim($CONF['fb_app_id'])); ?>"/>
	<?php endif; ?>
	<?php if (!empty($OG_IMAGE)): ?>
	<meta property="og:image" content="<?php echo htmlspecialchars($OG_IMAGE); ?>"/>
	<meta property="og:image:secure_url" content="<?php echo htmlspecialchars($OG_IMAGE); ?>"/>
	<meta property="og:image:alt" content="<?php echo htmlspecialchars($og_title); ?>"/>
	<?php endif; ?>
	<meta name="twitter:card" content="<?php echo !empty($OG_IMAGE) ? 'summary_large_image' : 'summary'; ?>"/>
	<meta name="twitter:title" content="<?php echo htmlspecialchars($og_title); ?>"/>
	<meta name="twitter:description" content="<?php echo htmlspecialchars($og_description); ?>"/>
	<?php if (!empty($OG_IMAGE)): ?>
	<meta name="twitter:image" content="<?php echo htmlspecialchars($OG_IMAGE); ?>"/>
	<?php endif; ?>
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
<a href="#main" class="skip-link"><?php echo t('Salta al contenuto principale'); ?></a>
<div class="wrapper max-w-4xl mx-auto px-2 sm:px-4 min-w-0 my-0" role="presentation">
	<header id="site-header" class="hero-panel-base min-h-[500px] py-5 px-3 sm:px-5 text-center panel-bg panel-text my-0" role="banner">
		<div class="text-center">
			<div id="logo" class="p-2.5">
				<a href="<?php echo htmlspecialchars($bp); ?>/" class="inline-block logo-link" aria-label="<?php echo htmlspecialchars($CONF["nome_sito"]); ?> - <?php echo htmlspecialchars(t('torna alla home')); ?>"><img src="<?php echo htmlspecialchars($bp); ?>/images/tuiland_logo_1.png" width="86" height="86" alt="<?php echo htmlspecialchars($CONF["nome_sito"]); ?>" class="block"/></a>
				<br/>
				<strong><?php echo htmlspecialchars(t('TuiLand AI')); ?></strong>
			</div>
		</div>
		<nav id="nav" class="public-nav nav-bar py-2 px-2 sm:px-4 my-4 text-center nav-bg" role="navigation" aria-label="<?php echo htmlspecialchars(t('Menu principale')); ?>">
			<button type="button" id="nav-toggle" class="nav-toggle" aria-label="<?php echo htmlspecialchars(t('Apri menu')); ?>" aria-expanded="false" aria-controls="nav-menu" hidden>
				<span class="nav-toggle-sr">Menu</span>
				<span class="nav-toggle-icon" aria-hidden="true">
					<span class="nav-toggle-line nav-toggle-line-top"></span>
					<span class="nav-toggle-line nav-toggle-line-middle"></span>
					<span class="nav-toggle-line nav-toggle-line-bottom"></span>
				</span>
			</button>
			<ul id="nav-menu" class="nav-menu flex flex-wrap justify-center gap-4 list-none m-0 p-0" role="menubar">
				<?php foreach ($nav_items as $nav_act => $nav_label): $is_active = ($ACT === '' && $nav_act === '') || $ACT === $nav_act; $href = $bp . '/' . ($nav_act !== '' ? '?ACT=' . rawurlencode($nav_act) : ''); $href = url_lang($href); ?>
				<li role="none"><a href="<?php echo htmlspecialchars($href); ?>" role="menuitem" class="nav-link<?php echo $is_active ? ' nav-link-active' : ''; ?>"<?php echo $is_active ? ' aria-current="page"' : ''; ?>><?php echo t($nav_label); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</nav>
		<main id="main" class="text-left mt-4 main-text" role="main" tabindex="-1">
			<h1 class="text-2xl font-bold heading-color mt-0 mb-4"><?php echo htmlspecialchars($header_h1); ?></h1>
			<?php include(FRAMEWORK_ROOT . $include_content); ?>
		</main>
	</header>
</div>

<footer role="contentinfo" class="footer-footer">
	<nav class="footer-nav" aria-label="Breadcrumb">
		<ol class="breadcrumb" itemscope itemtype="https://schema.org/BreadcrumbList">
			<?php foreach ($BREADCRUMB as $i => $item): ?>
			<li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
				<?php if ($i > 0): ?><span class="breadcrumb-sep" aria-hidden="true">&nbsp;›&nbsp;</span><?php endif; ?>
				<?php if (!empty($item['url'])): ?>
					<a href="<?php echo htmlspecialchars(url_lang($item['url'])); ?>" itemprop="item"><span itemprop="name"><?php echo t($item['label']); ?></span></a>
				<?php else: ?>
					<span itemprop="name" aria-current="location"><?php echo t($item['label']); ?></span>
				<?php endif; ?>
				<meta itemprop="position" content="<?php echo $i + 1; ?>"/>
			</li>
			<?php endforeach; ?>
		</ol>
	</nav>
	<?php if (isset($lang_name) && count($lang_name) > 1): $query_params = $_GET; $query_params['lng'] = '__LNG__'; $lang_switch_base = $bp . '/?' . http_build_query($query_params); ?>
	<div class="lang-switcher footer-lang-switcher" role="group" aria-label="<?php echo htmlspecialchars(t('Lingua')); ?>">
		<?php foreach ($lang_name as $code => $name): $lang_url = str_replace('__LNG__', $code, $lang_switch_base); ?>
		<a href="<?php echo htmlspecialchars($lang_url); ?>" class="lang-switcher-link<?php echo ($lang_user ?? 'it') === $code ? ' lang-switcher-current' : ''; ?>" hreflang="<?php echo htmlspecialchars($code); ?>"><?php echo htmlspecialchars($name); ?></a>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>
	<div class="footer-dark-toggle" role="group" aria-label="<?php echo htmlspecialchars(t('Tema')); ?>">
		<button type="button" class="footer-dark-btn" data-theme="light" aria-label="Tema chiaro" title="Tema chiaro">☀️</button>
		<button type="button" class="footer-dark-btn" data-theme="dark" aria-label="Tema scuro" title="Tema scuro">🌙</button>
		<button type="button" class="footer-dark-btn" data-theme="system" aria-label="Segui sistema" title="Segui preferenza sistema">💻</button>
	</div>
	<hr class="footer-divider" aria-hidden="true"/>
	<p class="footer-legal">
		<a href="<?php echo htmlspecialchars(url_lang($bp . '/?ACT=PAGINE&pagina=privacy')); ?>" class="footer-link"><?php echo t('Privacy'); ?></a><span class="footer-sep" aria-hidden="true"> | </span><a href="<?php echo htmlspecialchars(url_lang($bp . '/?ACT=PAGINE&pagina=condizioni')); ?>" class="footer-link"><?php echo t('Condizioni'); ?></a>
	</p>
	<p class="footer-copy"><small><?php echo htmlspecialchars($CONF["nome_sito"]); ?> &copy; <?php echo date('Y'); ?></small></p>
</footer>

<button type="button" id="back-to-top" class="back-to-top" aria-label="<?php echo htmlspecialchars(t('Torna su')); ?>">↑</button>

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
	var isSecure = window.location.protocol === 'https:';
	function setDarkCookie(value) {
		var s = COOKIE_NAME + '=' + (value || '') + '; path=' + COOKIE_PATH + '; max-age=' + COOKIE_MAX_AGE + '; SameSite=Lax';
		if (isSecure) s += '; Secure';
		document.cookie = s;
	}
	function getDarkCookie() {
		var m = document.cookie.match(new RegExp(COOKIE_NAME + '=([^;]+)'));
		return m ? m[1] : null;
	}
	function systemPrefersDark() {
		return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
	}
	function applyDark(isDark) {
		document.documentElement.classList.toggle('dark', !!isDark);
	}
	window.LINKBERRI_DARK_MODE = {
		setLight: function() { setDarkCookie('false'); applyDark(false); },
		setDark: function() { setDarkCookie('true'); applyDark(true); },
		setSystem: function() { setDarkCookie(systemPrefersDark() ? 'true' : 'false'); applyDark(systemPrefersDark()); },
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
			navToggle.setAttribute('aria-label', 'Chiudi menu');
			nav.classList.add('nav-open');
		}
		function hideMobileMenu() {
			navToggle.setAttribute('aria-expanded', 'false');
			navToggle.setAttribute('aria-label', 'Apri menu');
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
		document.addEventListener('click', function(e) {
			if (nav.classList.contains('nav-open') && !nav.contains(e.target) && !navToggle.contains(e.target)) hideMobileMenu();
		});
	}
	var backToTop = document.getElementById('back-to-top');
	if (backToTop) {
		function onScroll() { backToTop.classList.toggle('visible', window.scrollY > 300); }
		window.addEventListener('scroll', onScroll, { passive: true });
		onScroll();
		backToTop.addEventListener('click', function() { window.scrollTo({ top: 0, behavior: 'smooth' }); backToTop.blur(); });
	}
	var isDark = (function() {
		var c = getDarkCookie();
		if (c === 'true' || c === 'Y') return true;
		if (c === 'false' || c === 'N') return false;
		return systemPrefersDark();
	})();
	applyDark(isDark);
	if (getDarkCookie() === null) setDarkCookie(systemPrefersDark() ? 'true' : 'false');
	if (window.matchMedia) {
		window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
			setDarkCookie(e.matches ? 'true' : 'false');
			applyDark(e.matches);
		});
	}
})();
</script>
</body>
</html>
