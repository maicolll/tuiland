-- Log pacchetti content_updates (idempotenza Cursor → PHP senza accesso DB remoto).
-- Eseguire una tantum: mysql ... < _install/schema_content_update_log.sql
-- Oppure: php _install/run_schema_content_update_log.php
-- La tabella viene creata anche al primo apply (bootstrap automatico).

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `content_update_log` (
  `id` varchar(120) NOT NULL COMMENT 'id pacchetto JSON',
  `filename` varchar(255) DEFAULT NULL,
  `source` varchar(120) DEFAULT NULL,
  `status` enum('applied','failed','skipped') NOT NULL DEFAULT 'applied',
  `posts_created` int(11) NOT NULL DEFAULT 0,
  `comments_created` int(11) NOT NULL DEFAULT 0,
  `personality_updated` int(11) NOT NULL DEFAULT 0,
  `error_message` text,
  `applied_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `applied_at` (`applied_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
