# Documentation de deploiement

## Role de Docker

Docker permet de lancer la meme application sur toutes les machines : frontend React, backend Symfony, base MySQL, base MongoDB et serveur Nginx. Le correcteur peut donc tester le projet sans reconstruire manuellement chaque service.

## Lancement local

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec backend php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec backend php bin/console doctrine:fixtures:load --no-interaction
```

Services disponibles :

- Frontend : http://localhost:5173
- API : http://localhost:8000
- phpMyAdmin : http://localhost:8080

## Pipeline CI/CD

Le fichier `.github/workflows/ci.yaml` execute automatiquement :

- installation des dependances frontend ;
- lint frontend ;
- build frontend ;
- installation des dependances backend ;
- verification de syntaxe PHP ;
- migration de la base de test ;
- tests PHPUnit ;
- build Docker de la stack complete.

## Deploiement continu

Le script `scripts/deploy.sh` automatise la mise a jour sur un serveur Linux :

1. recuperation du dernier code avec Git ;
2. recuperation ou reconstruction des images Docker ;
3. redemarrage des conteneurs ;
4. execution des migrations ;
5. nettoyage du cache Symfony.

Sur Windows, `scripts/deploy.ps1` fournit l'equivalent PowerShell.

## Variables a configurer sur le serveur

Copier `.env.example` vers `.env`, puis modifier au minimum :

- `APP_SECRET`
- `JWT_PASSPHRASE`
- `DB_PASSWORD`
- `DB_ROOT_PASSWORD`
- `MONGODB_URI`
- `MONGODB_DB`
- `VITE_API_URL`
- `CORS_ALLOW_ORIGIN`

## MongoDB (messages de contact)

Les messages du formulaire de contact sont stockes dans MongoDB (demonstration NoSQL), separement de MySQL qui gere le reste du domaine (utilisateurs, produits, commandes).

- En local : service `mongo` du `compose.yaml` racine, accessible sur `localhost:${MONGO_PORT:-27018}`.
- En production : MongoDB Atlas (offre gratuite M0), car Render ne propose pas de MongoDB manage et chaque service Render est buildé depuis son propre Dockerfile (le `compose.yaml` ne sert qu'en local).

Mise en place cote Atlas :

1. Creer un cluster gratuit (M0) sur https://www.mongodb.com/atlas.
2. Autoriser l'acces reseau depuis n'importe quelle IP (`0.0.0.0/0`) — acceptable pour un projet de certification sur offre gratuite.
3. Recuperer la chaine de connexion SRV (`mongodb+srv://USER:PASSWORD@cluster0.xxxxx.mongodb.net`).
4. Definir `MONGODB_URI` (chaine de connexion) et `MONGODB_DB` (nom de la base) comme variables d'environnement du service backend sur Render, au meme titre que `DATABASE_URL`.

## Verification apres deploiement

```bash
docker compose ps
docker compose logs --tail=100
curl http://localhost:8000/api/categories
```
