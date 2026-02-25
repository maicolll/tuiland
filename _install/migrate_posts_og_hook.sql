-- Frase gancio (hook) per immagine OG: una frase breve per post, usata nell'immagine di condivisione.
-- Eseguire: mysql -u ... -p tuiland_sn < _install/migrate_posts_og_hook.sql
ALTER TABLE `posts` ADD COLUMN `og_hook` VARCHAR(250) NULL DEFAULT NULL AFTER `topic`;
-- Lunghezza idonea per il riquadro nell'immagine OG (~200 caratteri consigliati).
