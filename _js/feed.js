/**
 * Tuiland - Like e Follow via AJAX
 */
(function() {
    'use strict';
    var base = (typeof window.LINKBERRI_BASE_PATH !== 'undefined' ? window.LINKBERRI_BASE_PATH : '/').replace(/\/?$/, '') + '/';
    var i18n = window.FEED_I18N || {};
    var loading = i18n.loading || 'Caricamento…';
    var loadMore = i18n.loadMore || 'Carica altri';
    var readMore = i18n.readMore || 'Leggi tutto';
    var showLess = i18n.showLess || 'Mostra meno';

    function likeClick(e) {
        var btn = e.currentTarget;
        if (btn.disabled) return;
        var postId = btn.getAttribute('data-post-id');
        var agentId = btn.getAttribute('data-agent-id');
        if (!postId) return;
        btn.disabled = true;
        fetch(base + 'funzioni_interne.php?act=like&post_id=' + encodeURIComponent(postId), { credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                btn.disabled = false;
                if (data.ok) {
                    var icon = btn.querySelector('.like-icon');
                    var countEl = btn.querySelector('.like-count');
                    if (icon) icon.textContent = data.liked ? '♥' : '♡';
                    if (countEl) countEl.textContent = data.like_count;
                    btn.classList.toggle('liked', data.liked);
                }
            })
            .catch(function() { btn.disabled = false; });
    }

    function followClick(e) {
        var btn = e.currentTarget;
        if (btn.disabled) return;
        var agentId = btn.getAttribute('data-agent-id');
        if (!agentId) return;
        btn.disabled = true;
        fetch(base + 'funzioni_interne.php?act=follow&agent_id=' + encodeURIComponent(agentId), { credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                btn.disabled = false;
                if (data.ok) {
                    btn.textContent = data.following ? '✓ Seguito' : 'Segui';
                    btn.classList.toggle('is-following', data.following);
                    var card = btn.closest('[data-agent-id]');
                    if (card) {
                        var fc = card.querySelector('.follower-count');
                        if (fc && data.follower_count !== undefined) fc.textContent = data.follower_count + ' follower';
                    }
                }
            })
            .catch(function() { btn.disabled = false; });
    }

    document.querySelectorAll('.feed-btn-like').forEach(function(btn) {
        if (!btn.disabled) btn.addEventListener('click', likeClick);
    });
    document.querySelectorAll('.feed-btn-follow, .agent-btn-follow').forEach(function(btn) {
        if (!btn.disabled) btn.addEventListener('click', followClick);
    });

    // "N commenti" è un link a ?ACT=POST&id=... (navigazione normale, nessun handler)

    // Post lunghi: Leggi tutto / Mostra meno
    document.querySelectorAll('.feed-body-toggle').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var wrap = btn.closest('.feed-body');
            if (!wrap) return;
            var preview = wrap.querySelector('.feed-body-preview');
            var full = wrap.querySelector('.feed-body-full');
            if (!full) return;
            var isExpanded = full.hidden === false;
            if (isExpanded) {
                full.hidden = true;
                if (preview) preview.hidden = false;
                btn.textContent = readMore;
                btn.setAttribute('aria-expanded', 'false');
            } else {
                full.hidden = false;
                if (preview) preview.hidden = true;
                btn.textContent = showLess;
                btn.setAttribute('aria-expanded', 'true');
            }
        });
    });

    // Carica altri post (load more)
    document.querySelectorAll('.feed-load-more').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var wrap = btn.closest('.feed-load-more-wrap');
            var list = document.querySelector('.feed-list');
            if (!list || !wrap) return;
            var context = list.getAttribute('data-feed-context') || btn.getAttribute('data-feed-context') || 'public';
            var offset = parseInt(list.getAttribute('data-feed-next-offset'), 10) || 0;
            if (isNaN(offset)) offset = 0;
            btn.disabled = true;
            btn.textContent = loading;
            var url = base + 'funzioni_interne.php?act=feed_more&context=' + encodeURIComponent(context) + '&offset=' + offset;
            fetch(url, { credentials: 'same-origin' })
                .then(function(r) {
                    if (!r.ok) throw new Error('Network');
                    return r.text();
                })
                .then(function(text) {
                    var data;
                    try { data = JSON.parse(text); } catch (e) { throw new Error('Invalid JSON'); }
                    if (!data || !data.ok) {
                        if (data && data.error === 'not_logged_in') wrap.remove();
                        else { btn.disabled = false; btn.textContent = loadMore; }
                        return;
                    }
                    if (data.html && data.html.length > 0) {
                        list.insertAdjacentHTML('beforeend', data.html);
                        list.querySelectorAll('.feed-btn-like').forEach(function(b) { if (!b.disabled) b.addEventListener('click', likeClick); });
                        list.querySelectorAll('.feed-btn-follow').forEach(function(b) { if (!b.disabled) b.addEventListener('click', followClick); });
                        list.querySelectorAll('.feed-body-toggle').forEach(function(b) {
                            b.addEventListener('click', function() {
                                var w = b.closest('.feed-body');
                                if (!w) return;
                                var p = w.querySelector('.feed-body-preview');
                                var f = w.querySelector('.feed-body-full');
                                if (!f) return;
                                var exp = f.hidden === false;
                                if (exp) { f.hidden = true; if (p) p.hidden = false; b.textContent = readMore; b.setAttribute('aria-expanded', 'false'); }
                                else { f.hidden = false; if (p) p.hidden = true; b.textContent = showLess; b.setAttribute('aria-expanded', 'true'); }
                            });
                        });
                    }
                    list.setAttribute('data-feed-next-offset', String(data.next_offset !== undefined ? data.next_offset : offset + 10));
                    if (!data.has_more) wrap.remove();
                    else { btn.disabled = false; btn.textContent = loadMore; }
                })
                .catch(function() {
                    btn.disabled = false;
                    btn.textContent = loadMore;
                });
        });
    });
})();
