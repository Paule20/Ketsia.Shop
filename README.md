# Ketsia Shop


## Stack

- Frontend : React, Vite, Axios
- Backend : Symfony 7, Doctrine, JWT
- Base de donnees : MySQL 8
- Infrastructure : Docker Compose, Nginx
- CI/CD : GitHub Actions

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

Le workflow GitHub Actions se trouve dans `.github/workflows/ci.yaml`. Il verifie automatiquement le lint frontend, le build frontend, les tests backend et le build Docker.

## Deploiement

La procedure est decrite dans `docs/deployment.md`.

Scripts disponibles :

- `VITE_API_URL` (URL publique du backend)
- `VITE_STRIPE_PUBLISHABLE_KEY`

Procédure détaillée et scripts de déploiement manuel : [`docs/deployment.md`](docs/deployment.md), [`scripts/deploy.sh`](scripts/deploy.sh), [`scripts/deploy.ps1`](scripts/deploy.ps1).

## Livrables certification

- Plan de tests : `docs/test-plan.md`
- Journal de developpement : `docs/development-journal.md`
- Evaluation des competences : `docs/skill-assessment.md`
- Checklist conception : `docs/conception-checklist.md`

## Note de conformite

Le sujet myBank demande une application de gestion de depenses. Ketsia Shop couvre une grande partie des competences techniques et DevOps, mais les livrables de conception doivent expliquer le domaine e-commerce ou une branche dediee myBank doit etre creee pour respecter exactement les fonctionnalites "operations" et "categories".

