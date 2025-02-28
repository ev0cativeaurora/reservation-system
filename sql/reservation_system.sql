-- Drop the database if it exists (optional)
DROP DATABASE IF EXISTS `reservation_system`;

-- Create the database
CREATE DATABASE `reservation_system`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

-- Switch to the new database
USE `reservation_system`;

-- Create the `users` table
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `prenom` VARCHAR(50) NOT NULL,
  `nom` VARCHAR(50) NOT NULL,
  `date_naissance` DATE NULL,
  `adresse` VARCHAR(100) NULL,
  `telephone` VARCHAR(20) NULL,
  `email` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `email_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `email_unique` (`email`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- Create the `rendezvous` table (appointments)
CREATE TABLE `rendezvous` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `date_rdv` DATE NOT NULL,
  `heure_debut` TIME NOT NULL,
  `heure_fin` TIME NOT NULL,
  `motif` VARCHAR(100) NULL,
  `notes` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_rdv_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- Create a table for storing email verification tokens
CREATE TABLE `verification_tokens` (
  `token_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `token` VARCHAR(64) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_token_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- Create a table for contact form messages
CREATE TABLE `contact_messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nom` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `sujet` VARCHAR(100) NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- Create a table for email logs
CREATE TABLE `email_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_email_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- Add indexes for better performance
CREATE INDEX `idx_users_email` ON `users`(`email`);
CREATE INDEX `idx_rendezvous_date` ON `rendezvous`(`date_rdv`);
CREATE INDEX `idx_rendezvous_user_date` ON `rendezvous`(`user_id`, `date_rdv`);
CREATE INDEX `idx_verification_tokens_user` ON `verification_tokens`(`user_id`, `token`);
CREATE INDEX `idx_email_logs_user` ON `email_logs`(`user_id`);
CREATE INDEX `idx_email_logs_status` ON `email_logs`(`status`);

-- Optional: Insert some test data for development
-- Insert a test user (password: 'password123')
INSERT INTO `users` (`prenom`, `nom`, `email`, `password`, `email_verified`, `created_at`)
VALUES 
('John', 'Doe', 'john.doe@example.com', '$2y$10$C8Kfu5nqgiCIhJj.YV3HXe2Wl/LIVTgJTBsC6YPpXXRQIy3OhWUsq', 1, NOW());

-- Insert some test appointments
INSERT INTO `rendezvous` (`user_id`, `date_rdv`, `heure_debut`, `heure_fin`, `motif`, `notes`, `created_at`)
VALUES
(1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00', '11:00:00', 'Consultation', 'Notes de test pour le premier rendez-vous', NOW()),
(1, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '14:00:00', '15:00:00', 'Suivi', 'Notes de test pour le deuxième rendez-vous', NOW()),
(1, DATE_SUB(CURDATE(), INTERVAL 2 DAY), '09:00:00', '10:00:00', 'Examen', 'Rendez-vous passé', NOW());

-- Insert a test contact message
INSERT INTO `contact_messages` (`nom`, `email`, `sujet`, `message`, `created_at`)
VALUES
('Jane Smith', 'jane.smith@example.com', 'Demande de renseignements', 'Bonjour, je souhaiterais avoir plus d\'informations sur vos services.', NOW());