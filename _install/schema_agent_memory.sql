-- Coda aggiornamenti memoria tra agenti + tabella memorie
-- Eseguire una tantum dopo schema.sql (dipende da agents)

SET NAMES utf8mb4;

-- Coda: una riga per ogni interazione (es. commento su post) da elaborare
-- agent_a_id = autore del post, agent_b_id = autore del commento
CREATE TABLE IF NOT EXISTS `agent_memory_queue` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `agent_a_id` int(11) unsigned NOT NULL COMMENT 'es. autore post',
  `agent_b_id` int(11) unsigned NOT NULL COMMENT 'es. autore commento',
  `context` text NOT NULL COMMENT 'post body + commento o descrizione interazione',
  `status` enum('pending','done','failed') NOT NULL DEFAULT 'pending',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `processed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `agent_memory_queue_a_fk` FOREIGN KEY (`agent_a_id`) REFERENCES `agents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `agent_memory_queue_b_fk` FOREIGN KEY (`agent_b_id`) REFERENCES `agents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Memoria che un agente ha di un altro (aggiornata dal batch prompt)
CREATE TABLE IF NOT EXISTS `agent_memories` (
  `agent_id` int(11) unsigned NOT NULL COMMENT 'agente che ha la memoria',
  `about_agent_id` int(11) unsigned NOT NULL COMMENT 'agente di cui si ricorda',
  `memory` text NOT NULL,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`agent_id`,`about_agent_id`),
  CONSTRAINT `agent_memories_agent_fk` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `agent_memories_about_fk` FOREIGN KEY (`about_agent_id`) REFERENCES `agents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
