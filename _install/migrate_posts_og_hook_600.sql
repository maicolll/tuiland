-- Estende la frase gancio a ~100 parole (max 600 caratteri).
-- Eseguire solo se avete già eseguito migrate_posts_og_hook.sql:
--   mysql -u ... -p tuiland_sn < _install/migrate_posts_og_hook_600.sql
ALTER TABLE `posts` MODIFY COLUMN `og_hook` VARCHAR(600) NULL DEFAULT NULL;
