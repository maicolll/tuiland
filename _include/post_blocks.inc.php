<?php
/**
 * Render blocchi contenuto post (testo con formattazione, immagine, video, audio, link).
 * Richiede $blocks = array di blocchi da sn_post_blocks($post).
 * Se $post_blocks_thumbnails = true (es. nel feed): immagini, video, audio e link sono resi come thumbnail in una riga.
 */
if (!isset($blocks) || !is_array($blocks)) $blocks = [];
$thumbnails = !empty($post_blocks_thumbnails);
$media_only = !empty($post_blocks_media_only);
$text_only = !empty($post_blocks_text_only);
$safe_url = function ($url) {
    $u = trim($url);
    if ($u === '') return '';
    if (strpos($u, 'http://') === 0 || strpos($u, 'https://') === 0) return $u;
    // Path assoluti dalla root del sito (es. /upload/post_imgs/xxx.jpg)
    if (strpos($u, '/') === 0 && strpos($u, 'javascript:') === false) return $u;
    return '';
};
$media_blocks = [];
foreach ($blocks as $b) {
    $t = isset($b['type']) ? $b['type'] : 'text';
    if ($t !== 'text' && in_array($t, ['image', 'video', 'audio', 'link'], true)) $media_blocks[] = $b;
}
// Solo thumbnail (es. feed post abbreviato: mostra subito le foto)
if ($media_only) {
    if ($thumbnails && !empty($media_blocks)) {
?>
        <div class="post-attachments-thumbnails flex flex-wrap gap-2 mt-2">
<?php
foreach ($media_blocks as $b):
    $type = isset($b['type']) ? $b['type'] : 'text';
    if ($type === 'image'):
        $url = $safe_url(isset($b['url']) ? $b['url'] : '');
        if ($url === '') continue;
?>
            <a href="<?php echo htmlspecialchars($url); ?>" target="_blank" rel="noopener noreferrer" class="post-thumb post-thumb-image block w-60 h-60 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700 shrink-0" title="<?php echo htmlspecialchars(t('Immagine')); ?>"><img src="<?php echo htmlspecialchars($url); ?>" alt="" class="w-full h-full object-cover" loading="lazy"/></a>
<?php
    elseif ($type === 'video'):
        $url = isset($b['url']) ? trim($b['url']) : '';
        if ($url === '') continue;
        $thumb_src = '';
        if (preg_match('#(?:youtube\.com/watch\?v=|youtu\.be/)([a-zA-Z0-9_-]+)#', $url, $m)) {
            $thumb_src = 'https://img.youtube.com/vi/' . $m[1] . '/mqdefault.jpg';
        }
        if ($thumb_src !== ''):
?>
            <a href="<?php echo htmlspecialchars($url); ?>" target="_blank" rel="noopener noreferrer" class="post-thumb post-thumb-video block w-60 h-60 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700 shrink-0" title="<?php echo htmlspecialchars(t('Video')); ?>"><img src="<?php echo htmlspecialchars($thumb_src); ?>" alt="" class="w-full h-full object-cover" loading="lazy"/></a>
<?php
        else:
            $video_url = $safe_url($url);
            if ($video_url !== ''):
?>
            <a href="<?php echo htmlspecialchars($url); ?>" target="_blank" rel="noopener noreferrer" class="post-thumb post-thumb-video flex items-center justify-center w-60 h-60 rounded-lg bg-gray-200 dark:bg-gray-600 shrink-0 text-gray-500 dark:text-gray-400 text-xs hover:bg-gray-300 dark:hover:bg-gray-500" title="<?php echo htmlspecialchars(t('Video')); ?>">▶</a>
<?php
            endif;
        endif;
    elseif ($type === 'audio'):
        $url = $safe_url(isset($b['url']) ? $b['url'] : '');
        if ($url === '') continue;
?>
            <span class="post-thumb post-thumb-audio flex items-center justify-center w-60 h-60 rounded-lg bg-gray-200 dark:bg-gray-600 shrink-0 text-gray-500 dark:text-gray-400" title="<?php echo htmlspecialchars(t('Audio')); ?>">♪</span>
<?php
    elseif ($type === 'link'):
        $url = $safe_url(isset($b['url']) ? $b['url'] : '');
        if ($url === '') continue;
        $title = isset($b['title']) ? trim($b['title']) : $url;
        $title_short = (function_exists('mb_strlen') && mb_strlen($title) > 18) ? (function_exists('mb_substr') ? mb_substr($title, 0, 15) : substr($title, 0, 15)) . '…' : $title;
?>
            <a href="<?php echo htmlspecialchars($url); ?>" target="_blank" rel="noopener noreferrer" class="post-thumb post-thumb-link inline-flex items-center max-w-[10rem] px-2 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs truncate hover:bg-gray-200 dark:hover:bg-gray-600" title="<?php echo htmlspecialchars($title); ?>">🔗 <?php echo htmlspecialchars($title_short); ?></a>
<?php
    endif;
endforeach;
?>
        </div>
<?php
    }
    return;
}
foreach ($blocks as $b):
    $type = isset($b['type']) ? $b['type'] : 'text';
    if ($text_only && $type !== 'text') continue;
    if ($type === 'text'):
        $text = isset($b['text']) ? $b['text'] : '';
        if ($text === '') continue;
        $html = function_exists('sn_post_text_to_html') ? sn_post_text_to_html($text) : nl2br(htmlspecialchars($text));
?>
        <div class="post-block post-block-text prose dark:prose-invert max-w-none"><?php echo $html; ?></div>
<?php
    elseif (!$thumbnails):
        // Full size (pagina singolo post)
        if ($type === 'image'):
            $url = $safe_url(isset($b['url']) ? $b['url'] : '');
            if ($url === '') continue;
?>
        <figure class="post-block post-block-image mt-2"><img src="<?php echo htmlspecialchars($url); ?>" alt="" class="max-w-full h-auto rounded-lg" loading="lazy"/></figure>
<?php
        elseif ($type === 'video'):
            $url = isset($b['url']) ? trim($b['url']) : '';
            if ($url === '') continue;
            if (preg_match('#(?:youtube\.com/watch\?v=|youtu\.be/)([a-zA-Z0-9_-]+)#', $url, $m)) {
                $embed = 'https://www.youtube.com/embed/' . $m[1];
?>
        <figure class="post-block post-block-video mt-2"><div class="aspect-video rounded-lg overflow-hidden"><iframe src="<?php echo htmlspecialchars($embed); ?>" title="Video" allowfullscreen class="w-full h-full"></iframe></div></figure>
<?php
            } elseif (preg_match('#vimeo\.com/(?:video/)?(\d+)#', $url, $m)) {
                $embed = 'https://player.vimeo.com/video/' . $m[1];
?>
        <figure class="post-block post-block-video mt-2"><div class="aspect-video rounded-lg overflow-hidden"><iframe src="<?php echo htmlspecialchars($embed); ?>" title="Video" allowfullscreen class="w-full h-full"></iframe></div></figure>
<?php
            } else {
                $video_url = $safe_url($url);
                if ($video_url === '') continue;
?>
        <figure class="post-block post-block-video mt-2"><video src="<?php echo htmlspecialchars($video_url); ?>" controls class="max-w-full rounded-lg" preload="metadata">Video</video></figure>
<?php
            }
        elseif ($type === 'audio'):
            $url = $safe_url(isset($b['url']) ? $b['url'] : '');
            if ($url === '') continue;
?>
        <figure class="post-block post-block-audio mt-2"><audio src="<?php echo htmlspecialchars($url); ?>" controls class="w-full" preload="metadata">Audio</audio></figure>
<?php
        elseif ($type === 'link'):
            $url = $safe_url(isset($b['url']) ? $b['url'] : '');
            if ($url === '') continue;
            $title = isset($b['title']) ? trim($b['title']) : $url;
?>
        <p class="post-block post-block-link mt-2"><a href="<?php echo htmlspecialchars($url); ?>" target="_blank" rel="noopener noreferrer" class="text-blue-600 dark:text-blue-400 hover:underline"><?php echo htmlspecialchars($title); ?></a></p>
<?php
        endif;
    endif;
endforeach;
if ($thumbnails && !empty($media_blocks) && !$text_only):
?>
        <div class="post-attachments-thumbnails flex flex-wrap gap-2 mt-2">
<?php
foreach ($media_blocks as $b):
    $type = isset($b['type']) ? $b['type'] : 'text';
    if ($type === 'image'):
        $url = $safe_url(isset($b['url']) ? $b['url'] : '');
        if ($url === '') continue;
?>
            <a href="<?php echo htmlspecialchars($url); ?>" target="_blank" rel="noopener noreferrer" class="post-thumb post-thumb-image block w-60 h-60 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700 shrink-0" title="<?php echo htmlspecialchars(t('Immagine')); ?>"><img src="<?php echo htmlspecialchars($url); ?>" alt="" class="w-full h-full object-cover" loading="lazy"/></a>
<?php
    elseif ($type === 'video'):
        $url = isset($b['url']) ? trim($b['url']) : '';
        if ($url === '') continue;
        $thumb_src = '';
        if (preg_match('#(?:youtube\.com/watch\?v=|youtu\.be/)([a-zA-Z0-9_-]+)#', $url, $m)) {
            $thumb_src = 'https://img.youtube.com/vi/' . $m[1] . '/mqdefault.jpg';
        } else {
            $thumb_src = '';
        }
        if ($thumb_src !== ''):
?>
            <a href="<?php echo htmlspecialchars($url); ?>" target="_blank" rel="noopener noreferrer" class="post-thumb post-thumb-video block w-60 h-60 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700 shrink-0" title="<?php echo htmlspecialchars(t('Video')); ?>"><img src="<?php echo htmlspecialchars($thumb_src); ?>" alt="" class="w-full h-full object-cover" loading="lazy"/></a>
<?php
        else:
            $video_url = $safe_url($url);
            if ($video_url !== ''):
?>
            <a href="<?php echo htmlspecialchars($url); ?>" target="_blank" rel="noopener noreferrer" class="post-thumb post-thumb-video flex items-center justify-center w-60 h-60 rounded-lg bg-gray-200 dark:bg-gray-600 shrink-0 text-gray-500 dark:text-gray-400 text-xs hover:bg-gray-300 dark:hover:bg-gray-500" title="<?php echo htmlspecialchars(t('Video')); ?>">▶</a>
<?php
            endif;
        endif;
    elseif ($type === 'audio'):
        $url = $safe_url(isset($b['url']) ? $b['url'] : '');
        if ($url === '') continue;
?>
            <span class="post-thumb post-thumb-audio flex items-center justify-center w-60 h-60 rounded-lg bg-gray-200 dark:bg-gray-600 shrink-0 text-gray-500 dark:text-gray-400" title="<?php echo htmlspecialchars(t('Audio')); ?>">♪</span>
<?php
    elseif ($type === 'link'):
        $url = $safe_url(isset($b['url']) ? $b['url'] : '');
        if ($url === '') continue;
        $title = isset($b['title']) ? trim($b['title']) : $url;
        $title_short = (function_exists('mb_strlen') && mb_strlen($title) > 18) ? (function_exists('mb_substr') ? mb_substr($title, 0, 15) : substr($title, 0, 15)) . '…' : $title;
?>
            <a href="<?php echo htmlspecialchars($url); ?>" target="_blank" rel="noopener noreferrer" class="post-thumb post-thumb-link inline-flex items-center max-w-[10rem] px-2 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs truncate hover:bg-gray-200 dark:hover:bg-gray-600" title="<?php echo htmlspecialchars($title); ?>">🔗 <?php echo htmlspecialchars($title_short); ?></a>
<?php
    endif;
endforeach;
?>
        </div>
<?php
endif;
