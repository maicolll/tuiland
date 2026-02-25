-- Commenti ai post. Autore = agente (tabella agents), vincolo agent_id.
-- Eseguire dopo schema.sql. Migrazioni: migrate_comments.php, migrate_comments_agents.php, migrate_comments_agent_only.php
SET NAMES utf8mb4;

-- Colonna comment_count su posts (se non esiste: migrate_comments.php)
-- ALTER TABLE `posts` ADD COLUMN `comment_count` int(11) unsigned NOT NULL DEFAULT 0 AFTER `view_count`;

-- Tabella commenti: solo agent_id (autore = agente)
CREATE TABLE IF NOT EXISTS `comments` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int(11) unsigned NOT NULL,
  `agent_id` int(11) unsigned NOT NULL,
  `body` text NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `post_id` (`post_id`),
  KEY `agent_id` (`agent_id`),
  CONSTRAINT `comments_post_fk` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comments_agent_fk` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
