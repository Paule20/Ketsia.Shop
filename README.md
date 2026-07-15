# Ketsia Shop

E-commerce de vêtements (Femme, Homme, Enfants) développé dans le cadre de la certification CDA (Concepteur Développeur d'Applications). Catalogue de produits, panier, wishlist, authentification JWT, commandes, paiement Stripe, formulaire de contact et back-office admin.

## Stack

- **Frontend** : React 19, Vite, React Router, Axios, Stripe.js
- **Backend** : Symfony 7, Doctrine ORM, Doctrine MongoDB ODM, LexikJWTAuthenticationBundle, NelmioCorsBundle
- **Base de données** : MySQL 8 (domaine principal) + MongoDB (messages de contact)
- **Paiement** : Stripe (Checkout)
- **Infrastructure** : Docker Compose (local), Nginx
- **Qualité** : PHPUnit, Vitest, ESLint, SonarCloud
- **CI/CD** : GitHub Actions → déploiement automatique sur Render

## Démo en ligne

- Frontend : https://ketsia-frontend.onrender.com
- API : https://ketsia-backend.onrender.com/api

## Structure du projet

```
backend/     API Symfony (routes /api/*)
frontend/    Application React (catalogue, panier, compte, back-office admin)
docs/        Livrables de certification
scripts/     Scripts de déploiement manuel
```

## Lancer le projet en local avec Docker

```bash
cp .env.example .env
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env   # si présent, sinon voir frontend/README.md

docker compose up -d --build
docker compose exec backend php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec backend php bin/console doctrine:fixtures:load --no-interaction
```

Comptes de test créés par les fixtures :

- Admin : `admin@ketsia.shop` / `Admin1234!`
- Utilisateur : `user@ketsia.shop` / `User1234!`

URLs :

- Frontend : http://localhost:5173
- API : http://localhost:8000
- phpMyAdmin : http://localhost:8080

## Tests

Backend :

```bash
cd backend
php bin/phpunit
```

Frontend :

```bash
cd frontend
npm run lint
npm run build
npm run test
```

Docker :

```bash
docker compose -f compose.yaml build
```

## CI/CD

Le workflow GitHub Actions se trouve dans [`.github/workflows/ci-cd.yaml`](.github/workflows/ci-cd.yaml) et enchaîne, sur chaque push/PR :

1. **frontend** : lint, build, tests Vitest
2. **backend** : tests PHPUnit avec couverture (MySQL de service)
3. **sonarqube** : analyse qualité SonarCloud
4. **docker** : validation du build complet (`docker compose build`)
5. **deploy** *(push sur `main` uniquement)* : déclenche les Deploy Hooks Render (backend + frontend)

## Déploiement (Render)

Chaque service Render (`Ketsia-backend`, `Ketsia-frontend`) est buildé depuis son propre `Dockerfile`. Le conteneur backend fait tourner **nginx + php-fpm** ensemble (via supervisord) pour exposer directement le port HTTP attendu par Render, génère les clés JWT au premier démarrage et applique les migrations Doctrine à chaque redémarrage.

Variables d'environnement requises côté Render pour le service backend :

- `APP_ENV=prod`
- `APP_SECRET`
- `DATABASE_URL`
- `MONGODB_URI` (MongoDB Atlas — messages de contact)
- `MONGODB_DB`
- `DEFAULT_URI`
- `CORS_ALLOW_ORIGIN`
- `STRIPE_SECRET_KEY`
- `JWT_PASSPHRASE`
- `JWT_SECRET_KEY=/var/www/backend/config/jwt/private.pem`
- `JWT_PUBLIC_KEY=/var/www/backend/config/jwt/public.pem`

Côté frontend (variable injectée **au build**, pas au runtime) :

- `VITE_API_URL` (URL publique du backend)
- `VITE_STRIPE_PUBLISHABLE_KEY`

Procédure détaillée et scripts de déploiement manuel : [`docs/deployment.md`](docs/deployment.md), [`scripts/deploy.sh`](scripts/deploy.sh), [`scripts/deploy.ps1`](scripts/deploy.ps1).

## Livrables certification

- Plan de tests : [`docs/test-plan.md`](docs/test-plan.md)
- Journal de développement : [`docs/development-journal.md`](docs/development-journal.md)
- Évaluation des compétences : [`docs/skill-assessment.md`](docs/skill-assessment.md)
- Checklist conception : [`docs/conception-checklist.md`](docs/conception-checklist.md)

## Note de conformité

## Note de conformité

Ce projet met en œuvre les principales compétences attendues en conception, développement, qualité logicielle et déploiement d'une application web moderne. Les fonctionnalités ont été adaptées au domaine du e-commerce tout en conservant une architecture et des méthodes de développement. 

Les livrables de conception (analyse des besoins, modélisation, documentation technique et fonctionnelle) décrivent les processus métier propres à une boutique en ligne, notamment la gestion des produits, des utilisateurs, des commandes, du panier et des paiements. Les éléments spécifiques à un autre domaine fonctionnel ont été remplacés par leurs équivalents dans le contexte e-commerce afin d'assurer la cohérence de l'ensemble du projet.

