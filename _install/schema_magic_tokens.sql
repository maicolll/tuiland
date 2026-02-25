-- Token per magic link (login passwordless). Eseguire una tantum se non presente.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `magic_tokens` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(64) NOT NULL,
  `email` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `email` (`email`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
