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
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
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
  CONSTRAINT `fk_rdv_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- (Optional) Create a table for storing email verification tokens
-- Only include this if you plan to implement email verification
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
