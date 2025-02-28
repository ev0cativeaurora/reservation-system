CREATE TABLE `users` (
    -- ...
);

CREATE TABLE `rendezvous` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `date_rdv` DATE NOT NULL,
    `heure_debut` TIME NOT NULL,
    `heure_fin` TIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
