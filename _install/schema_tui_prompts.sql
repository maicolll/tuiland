-- Prompt inviati dagli utenti (Textual User Intelligence) – sito antico textualuserintelligence_message_prompt
-- Eseguire una tantum se non presente

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `tui_prompts` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `message` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
