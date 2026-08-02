# symfResto103

## Docker

Résumé de la partie Docker et commandes de test :

- Image PHP (`Dockerfile`): base `php:8.2-fpm`, installe les dépendances système et compile les extensions `pdo_mysql`, `intl`, `zip`, `gd`, `opcache`.
- Utilise `composer` (copie depuis `composer:2.7`) et exécute `composer install` au build.
- Pendant le build, un fichier `.env` temporaire est créé pour permettre le `cache:warmup` de Symfony en `prod` puis supprimé.
- `docker-compose.yml` définit `php`, `nginx`, `db` (MySQL). Le code est monté en volume `.:/var/www/symfony`.
- Important : le montage du volume écrase le contenu copié dans l'image (notamment `vendor/`) si ces fichiers existent localement — gérer `.dockerignore` ou exécuter `composer install` localement si nécessaire.

Commandes pour tester seulement la partie Docker :

1) Construire l'image PHP :
```
docker compose -f docker-compose.yml build php
```

2) Vérifier la visibilité d'une variable d'environnement :
```
docker run --rm -e APP_ENV=prod symfony-restaurant-devops007-php php -r "echo getenv('APP_ENV');"
```

3) Tester `bin/console` dans l'image (méthode sûre) :
- Direct :
```
docker run --rm -e APP_ENV=prod -e DATABASE_URL="mysql://app:app@db:3306/symfresto07?serverVersion=8.0.32&charset=utf8mb4" symfony-restaurant-devops007-php php bin/console cache:warmup --env=prod --no-debug
```
- Interactif (debugging) :
```
docker run --rm -it --entrypoint bash symfony-restaurant-devops007-php
# puis dans le container:
printf 'APP_ENV=prod\nAPP_SECRET=change_me_for_prod\nDATABASE_URL=mysql://app:app@db:3306/symfresto07?serverVersion=8.0.32&charset=utf8mb4\n' > .env
php bin/console cache:warmup --env=prod --no-debug
rm -f .env
exit
```

4) Lancer uniquement le service `php` via Compose :
```
docker compose -f docker-compose.yml up --build php
```

5) Lancer toute la stack :
```
docker compose -f docker-compose.yml up --build
```

Si tu veux, je peux ajouter un paragraphe Jenkins/Terraform plus tard.










Email : admin@restaurant.local
Mot de passe : admin123