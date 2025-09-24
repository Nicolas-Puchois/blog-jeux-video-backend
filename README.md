# Blog Jeux Vidéo - Backend API

## Description

API REST pour le blog de jeux vidéo permettant la gestion des articles, utilisateurs et authentification. Développée avec PHP 8.0, utilisant MySQL pour la persistance des données et JWT pour l'authentification.

## Prérequis

- PHP 8.0 ou supérieur
- MySQL 5.7 ou supérieur
- Composer
- Extensions PHP requises :
  - PDO
  - mbstring
  - json
  - fileinfo
  - gettext

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

3. Configuration de l'environnement

- Copier `.env.example` vers `.env`
- Configurer les variables :
  ```properties
  DB_HOST=localhost
  DB_NAME=infodotgame
  DB_USER=votre_utilisateur
  DB_PASSWORD=votre_mot_de_passe
  DB_PORT=3315
  JWT_SECRET_KEY=votre_clé_jwt
  RECAPTCHA_SECRET_KEY=votre_clé_recaptcha
  CSRF_SECRET=votre_clé_csrf
  ```

4. Initialiser la base de données

```bash
mysql -u root -p < database/init-infodotgame.sql
```

5. Démarrer le serveur

```bash
php -S localhost:8000 -t public
# ou
./start-backend.bat
```

## Structure du Projet

```
project/
├── src/
│   ├── controller/     # Contrôleurs (Articles, Users)
│   ├── core/          # Noyau (Router, Database)
│   ├── middleware/    # Middlewares (CORS, CSRF)
│   ├── model/         # Modèles de données
│   ├── repository/    # Accès aux données
│   ├── services/      # Services (JWT, Mail)
│   └── Utils/         # Utilitaires
├── public/            # Point d'entrée et assets
└── database/          # Scripts SQL
```

## Points d'API

### Authentification

- `POST /api/register` - Inscription utilisateur

  - Params: email, password, username
  - Retourne: token JWT

- `POST /api/login` - Connexion

  - Params: email, password
  - Retourne: token JWT

- `GET /api/valider-email` - Validation email
  - Params: token
  - Retourne: statut validation

### Articles

- `GET /api/articles` - Liste des articles

  - Query: page, limit
  - Retourne: articles[], total

- `GET /api/articles/{id}` - Détail article

  - Retourne: article

- `POST /api/articles` - Création article

  - Auth: JWT requis
  - CSRF: Requis
  - Body: title, content, introduction, tags[]

- `PUT /api/articles/{id}` - Modification article

  - Auth: JWT requis
  - CSRF: Requis
  - Body: title?, content?, introduction?, tags[]?

- `DELETE /api/articles/{id}` - Suppression article

  - Auth: JWT requis
  - CSRF: Requis

- `POST /api/articles/{id}/image` - Upload image
  - Auth: JWT requis
  - CSRF: Requis
  - Body: image (multipart/form-data)

### Sécurité

- Protection CSRF sur toutes les routes modifiant des données
- Validation reCAPTCHA pour l'inscription
- Authentification JWT
- Sanitization des entrées utilisateur
- Protection XSS
- Validation des emails
- Rate limiting sur les routes sensibles

## Développement

### Logs

Les logs sont disponibles dans :

```
var/log/php_error.log
```

### Tests

```bash
composer test
```

### Démarrage rapide

```bash
composer start
# ou
./start-backend.bat
```

## Déploiement

### Avec Docker

```bash
cd docker
docker-compose up -d
```

### Manuel

1. Configurer un virtual host Apache/Nginx
2. Pointer le document root vers `/public`
3. Configurer les permissions dossiers
4. Créer la base de données
5. Configurer le `.env`

## Maintenance

### Base de données

- Backup : `mysqldump -u root -p infodotgame > backup.sql`
- Mise à jour : `mysql -u root -p infodotgame < database/update-article-table.sql`

### Logs

- Rotation des logs configurée
- Nettoyage automatique > 30 jours

## Contribuer

1. Fork le projet
2. Créer une branche (`git checkout -b feature/ma-feature`)
3. Commit (`git commit -m 'Description'`)
4. Push (`git push origin feature/ma-feature`)
5. Pull Request

## Licence

MIT
