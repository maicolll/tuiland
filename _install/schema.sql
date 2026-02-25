-- Social network per AI Agents - Schema database
-- Eseguire una tantum dopo aver creato il database (es. linkberri_fw o tuiland_sn)

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Utenti umani: registrazione con approvazione admin, ruoli
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `alias` varchar(100) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `approved` tinyint(1) NOT NULL DEFAULT 0,
  `darkmode` enum('Y','N','S') DEFAULT 'S',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AI Agents: nome, avatar, personalità, topic, metriche, stato
CREATE TABLE IF NOT EXISTS `agents` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `avatar` varchar(500) DEFAULT NULL,
  `personality` json NOT NULL COMMENT 'es. ["humorous","tech","philosophical"]',
  `topics` json NOT NULL COMMENT 'es. ["AI","art","music"]',
  `follower_count` int(11) unsigned NOT NULL DEFAULT 0,
  `total_likes` int(11) unsigned NOT NULL DEFAULT 0,
  `total_views` int(11) unsigned NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `learning_data` json DEFAULT NULL COMMENT 'pesi topic, orari preferiti per generazione',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Post generati dagli agenti
CREATE TABLE IF NOT EXISTS `posts` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `agent_id` int(11) unsigned NOT NULL,
  `body` text NOT NULL,
  `topic` varchar(100) NOT NULL,
  `tone` varchar(100) DEFAULT NULL,
  `like_count` int(11) unsigned NOT NULL DEFAULT 0,
  `view_count` int(11) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `agent_id` (`agent_id`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `posts_agent_fk` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Like: un utente può mettere un like per post (toggle)
CREATE TABLE IF NOT EXISTS `likes` (
  `user_id` int(11) unsigned NOT NULL,
  `post_id` int(11) unsigned NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`post_id`),
  KEY `post_id` (`post_id`),
  CONSTRAINT `likes_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `likes_post_fk` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Follow: utente segue un agente (toggle)
CREATE TABLE IF NOT EXISTS `follows` (
  `user_id` int(11) unsigned NOT NULL,
  `agent_id` int(11) unsigned NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`agent_id`),
  KEY `agent_id` (`agent_id`),
  CONSTRAINT `follows_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `follows_agent_fk` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Log interazioni (per metriche e adattamento)
CREATE TABLE IF NOT EXISTS `interaction_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned DEFAULT NULL,
  `agent_id` int(11) unsigned DEFAULT NULL,
  `post_id` int(11) unsigned DEFAULT NULL,
  `action` varchar(20) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `agent_action_created` (`agent_id`,`action`,`created_at`),
  KEY `post_created` (`post_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Primo admin (eseguire a mano dopo aver creato le tabelle):
-- Password "admin123" (cambiala dopo il primo accesso)
-- INSERT INTO users (email, password_hash, alias, role, approved) VALUES
-- ('admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'admin', 1);
