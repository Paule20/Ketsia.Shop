# Ketsia Shop


## Stack

- Frontend : React, Vite, Axios
- Backend : Symfony 7, Doctrine, JWT
- Base de donnees : MySQL 8
- Infrastructure : Docker Compose, Nginx
- CI/CD : GitHub Actions

## Lancer le projet avec Docker

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec backend php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec backend php bin/console doctrine:fixtures:load --no-interaction
```

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
```

Docker :

```bash
docker compose build
```

## CI/CD

Le workflow GitHub Actions se trouve dans `.github/workflows/ci.yaml`. Il verifie automatiquement le lint frontend, le build frontend, les tests backend et le build Docker.

## Deploiement

La procedure est decrite dans `docs/deployment.md`.

Scripts disponibles :

- Linux : `scripts/deploy.sh`
- Windows : `scripts/deploy.ps1`

## Livrables certification

- Plan de tests : `docs/test-plan.md`
- Journal de developpement : `docs/development-journal.md`
- Evaluation des competences : `docs/skill-assessment.md`
- Checklist conception : `docs/conception-checklist.md`

## Note de conformite

Le sujet myBank demande une application de gestion de depenses. Ketsia Shop couvre une grande partie des competences techniques et DevOps, mais les livrables de conception doivent expliquer le domaine e-commerce ou une branche dediee myBank doit etre creee pour respecter exactement les fonctionnalites "operations" et "categories".
