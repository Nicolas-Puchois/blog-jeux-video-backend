# Blog Jeux Vidéo - Backend API

## Description

API REST pour le blog de jeux vidéo permettant la gestion des articles, utilisateurs et authentification.

## Prérequis

- PHP 8.0 ou supérieur
- MySQL 5.7 ou supérieur
- Composer
- Extension PHP :
  - PDO
  - mbstring
  - json
  - fileinfo

## Installation

1. Cloner le repository

```bash
git clone https://github.com/Nicolas-Puchois/blog-jeux-video-backend.git
cd blog-jeux-video-backend
```

2. Installer les dépendances

```bash
composer install
```

3. Configurer l'environnement

- Copier le fichier `.env.example` vers `.env`
- Modifier les variables d'environnement :
  ```
  DB_HOST=localhost
  DB_NAME=infodotgame
  DB_USER=votre_utilisateur
  DB_PASSWORD=votre_mot_de_passe
  DB_PORT=3306
  JWT_SECRET_KEY=votre_clé_jwt
  RECAPTCHA_SECRET_KEY=votre_clé_recaptcha
  CSRF_SECRET=votre_clé_csrf
  ```

4. Initialiser la base de données

```bash
mysql -u root -p < database/init-infodotgame.sql
```

5. Lancer le serveur de développement

```bash
php -S localhost:8000 -t public
```

## Structure du Projet

```
src/
├── controller/     # Contrôleurs de l'application
├── core/          # Composants principaux (Router, Database)
├── middleware/    # Middlewares (CORS, CSRF)
├── model/         # Modèles de données
├── repository/    # Couche d'accès aux données
├── services/      # Services (JWT, Mail, Recaptcha)
└── Utils/         # Utilitaires
```

## Points d'API

### Utilisateurs

- `POST /api/register` - Inscription
- `POST /api/login` - Connexion
- `GET /api/valider-email` - Validation email

### Articles

- `GET /api/articles` - Liste des articles
- `GET /api/articles/{id}` - Détail d'un article
- `POST /api/articles` - Création d'un article
- `PUT /api/articles/{id}` - Modification d'un article
- `DELETE /api/articles/{id}` - Suppression d'un article
- `POST /api/articles/{id}/image` - Upload d'image

### Sécurité

- `POST /verify-recaptcha` - Vérification reCAPTCHA

## Sécurité

- Protection CSRF
- Validation reCAPTCHA
- Tokens JWT
- Sanitization des entrées
- Protection XSS
- Validation des emails

## Docker

Pour lancer avec Docker :

```bash
cd docker
docker-compose up -d
```

## Tests

```bash
composer test
```

## Serveur

```bash
composer start
```

## Contributions

1. Fork le projet
2. Créer une branche (`git checkout -b feature/ma-feature`)
3. Commit les changements (`git commit -m 'Ajout de ma feature'`)
4. Push sur la branche (`git push origin feature/ma-feature`)
5. Créer une Pull Request

## Licence
