# Système de Réservation avec Calendrier

Un système complet de réservation en ligne avec un calendrier interactif, développé en PHP procédural avec MySQL pour la gestion des données.

## Fonctionnalités

### Gestion des Utilisateurs
- Création de compte avec validation des informations
- Connexion et déconnexion sécurisées
- Modification des informations personnelles
- Suppression de compte

### Gestion des Rendez-vous
- Visualisation des disponibilités via un calendrier interactif
- Prise de rendez-vous simples et rapides
- Affichage des rendez-vous personnels
- Annulation de rendez-vous

### Sécurité
- Protection contre les attaques CSRF
- Hachage sécurisé des mots de passe
- Protection contre les attaques XSS et SQL Injection
- Validation des données utilisateur

### Interface Utilisateur
- Design responsive avec Bootstrap
- Mode sombre/clair
- Calendrier interactif pour la sélection des dates
- Formulaire de contact

## Technologies Utilisées

- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Backend**: PHP procédural
- **Base de données**: MySQL
- **Sécurité**: Tokens CSRF, Hachage de mot de passe avec password_hash()

## Installation

1. **Cloner le dépôt**
   ```bash
   git clone https://github.com/votre-utilisateur/reservation-system.git
   cd reservation-system
   ```

2. **Configuration de la base de données**
   - Créez une base de données MySQL
   - Importez le fichier `sql/reservation_system.sql`
   - Configurez les informations de connexion dans `config/config.php`

3. **Configuration du serveur**
   - Assurez-vous que votre serveur web (Apache, Nginx, etc.) est configuré pour pointer vers le dossier `public` comme racine du site
   - Activez le module de réécriture d'URL si nécessaire

4. **Permissions**
   - Assurez-vous que les dossiers appropriés ont les permissions d'écriture nécessaires

## Structure du Projet

```
reservation-system/
├── config/             # Configuration de la base de données
├── includes/           # Fichiers inclus (header, footer, CSRF)
├── public/             # Fichiers accessibles publiquement
│   ├── index.php       # Page d'accueil
│   ├── connexion.php   # Page de connexion
│   └── ...             # Autres pages du site
├── resources/          # Ressources frontend (CSS, JavaScript)
│   ├── css/            # Feuilles de style
│   └── js/             # Scripts JavaScript
├── sql/                # Scripts SQL pour la base de données
└── utils/              # Fonctions utilitaires
```

## Sécurité

Ce projet implémente plusieurs mesures de sécurité :

- **Protection CSRF** : Des tokens uniques sont générés pour chaque formulaire
- **Hachage des mots de passe** : Les mots de passe sont hachés avec `password_hash()`
- **Prévention XSS** : Les données utilisateur sont échappées avant affichage
- **Prévention SQL Injection** : Utilisation de requêtes préparées avec PDO

## Captures d'écran

[Insérez ici des captures d'écran des principales pages de l'application]

## Licence

Ce projet est sous licence MIT. Voir le fichier LICENSE pour plus de détails.