# Ketsia.shop — Backend API

API REST Symfony 7 pour la boutique e-commerce Ketsia.shop.  
Projet de certification CDA — École Multimédia.

---

## Stack technique

| Composant | Version |
|-----------|---------|
| PHP | 8.2 |
| Symfony | 7.4 |
| Doctrine ORM | 3.x |
| MySQL | 8.0 |
| LexikJWT | 3.x |
| PHPUnit | 11.x |
| Docker | 24+ |

---

## Prérequis (installation locale sans Docker)

- PHP 8.2+ avec les extensions : `pdo_mysql`, `intl`, `mbstring`, `zip`, `opcache`
- Composer 2.x
- MySQL 8.0
- OpenSSL (pour la génération des clés JWT)

---

## Installation locale (sans Docker)

### 1. Cloner et installer les dépendances

```bash
cd backend/
composer install
```

### 2. Configurer l'environnement

```bash
cp .env.example .env.local
# Editer .env.local : renseigner DATABASE_URL, JWT_PASSPHRASE, APP_SECRET
```

### 3. Générer les clés JWT

```bash
php bin/console lexik:jwt:generate-keypair
# Cree config/jwt/private.pem et config/jwt/public.pem
```

### 4. Créer la base de données et exécuter les migrations

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 5. (Optionnel) Charger les données de test

```bash
php bin/console doctrine:fixtures:load
# Cree : admin@ketsia.shop (Admin1234!) et user@ketsia.shop (User1234!)
# Cree : 4 categories + 8 produits de test
```

### 6. Démarrer le serveur de développement

```bash
symfony server:start
# ou
php -S localhost:8000 -t public/
```

L'API est accessible sur `http://localhost:8000`.

---

## Installation avec Docker (méthode recommandée)

### Prérequis

- Docker Desktop (Windows/Mac) ou Docker Engine + Compose (Linux)

### 1. Préparer les variables d'environnement Docker

```bash
cp .env.docker .env
# .env est lu par docker-compose pour les variables DB_NAME, DB_USER, etc.
# Modifier les valeurs si nécessaire
```

### 2. Générer les clés JWT (une seule fois, avant le build)

```bash
mkdir -p config/jwt
openssl genrsa -out config/jwt/private.pem -aes256 4096
openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem
# Utiliser la même passphrase que JWT_PASSPHRASE dans .env
```

> **Windows** : si OpenSSL n'est pas disponible, utiliser Git Bash ou WSL.

### 3. Lancer les conteneurs

```bash
docker-compose up -d --build
```

Cela démarre :
- **ketsia_nginx** sur `http://localhost:8000` (point d'entrée de l'API)
- **ketsia_backend** (PHP-FPM 8.2 + Symfony)
- **ketsia_database** (MySQL 8.0 sur le port `3307`)

### 4. Exécuter les migrations dans le conteneur

```bash
docker exec ketsia_backend php bin/console doctrine:migrations:migrate --no-interaction
```

### 5. Charger les fixtures (données de test)

```bash
docker exec ketsia_backend php bin/console doctrine:fixtures:load --no-interaction
```

### 6. Vérifier que l'API répond

```bash
curl http://localhost:8000/api/categories
# Réponse attendue : [] (tableau vide si pas de fixtures) ou liste des catégories
```

### Commandes Docker utiles

```bash
# Arrêter les conteneurs
docker-compose down

# Arrêter ET supprimer les volumes (repart de zéro)
docker-compose down -v

# Voir les logs en temps réel
docker-compose logs -f

# Ouvrir un shell dans le conteneur backend
docker exec -it ketsia_backend bash

# Lancer les tests (base _test créée automatiquement)
docker exec ketsia_backend php bin/phpunit
```

---

## Routes API

### Authentification (publiques)

| Méthode | Route | Description |
|---------|-------|-------------|
| POST | `/api/register` | Inscription (`email`, `password`, `firstName`, `lastName`) |
| POST | `/api/login` | Connexion → retourne un token JWT |
| GET | `/api/me` | Profil de l'utilisateur connecté *(JWT requis)* |

### Catalogue (lecture publique)

| Méthode | Route | Description |
|---------|-------|-------------|
| GET | `/api/categories` | Liste des catégories |
| GET | `/api/products` | Liste des produits (filtres : `?category=femme&minPrice=20&maxPrice=100`) |
| GET | `/api/products/{id}` | Détail d'un produit |

### Catalogue (écriture — ROLE_ADMIN)

| Méthode | Route | Description |
|---------|-------|-------------|
| POST | `/api/products` | Créer un produit |
| PUT | `/api/products/{id}` | Modifier un produit |
| DELETE | `/api/products/{id}` | Supprimer un produit |

### Commandes *(JWT requis)*

| Méthode | Route | Description |
|---------|-------|-------------|
| GET | `/api/orders` | Mes commandes |
| GET | `/api/orders/{id}` | Détail d'une commande |
| POST | `/api/orders` | Créer une commande (`items[]`, `shippingAddress`) |

### Administration *(ROLE_ADMIN requis)*

| Méthode | Route | Description |
|---------|-------|-------------|
| GET | `/api/admin/orders` | Toutes les commandes |
| PATCH | `/api/admin/orders/{id}/status` | Changer le statut |
| GET | `/api/admin/users` | Tous les utilisateurs |
| DELETE | `/api/admin/users/{id}` | Supprimer un utilisateur |

---

## Exemple de requête — Connexion

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@ketsia.shop","password":"Admin1234!"}'
```

Réponse :
```json
{ "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..." }
```

## Exemple — Créer une commande

```bash
curl -X POST http://localhost:8000/api/orders \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <votre_token>" \
  -d '{
    "items": [
      {"productId": 1, "quantity": 2},
      {"productId": 3, "quantity": 1}
    ],
    "shippingAddress": "12 rue des Lilas, 75001 Paris"
  }'
```

---

## Tests

```bash
# En local
php bin/phpunit

# Avec Docker
docker exec ketsia_backend php bin/phpunit --testdox
```

Les tests utilisent une base de données séparée (`ketsia_shop_test`) créée automatiquement.

---

## Structure du projet

```
backend/
├── config/
│   ├── jwt/                    ← Clés RSA (gitignore)
│   └── packages/
│       ├── security.yaml       ← Firewalls + access_control
│       └── lexik_jwt_authentication.yaml
├── migrations/                 ← Migration SQL initiale
├── src/
│   ├── Controller/
│   │   ├── AuthController.php
│   │   ├── CategoryController.php
│   │   ├── ProductController.php
│   │   ├── OrderController.php
│   │   └── Admin/
│   │       ├── AdminOrderController.php
│   │       └── AdminUserController.php
│   ├── Entity/                 ← Modèles Doctrine
│   ├── Repository/             ← Requêtes BDD
│   ├── Service/
│   │   ├── UserService.php     ← Logique inscription
│   │   └── OrderService.php    ← Logique commande + stock
│   └── DataFixtures/           ← Données de test
├── tests/
│   └── Controller/             ← Tests fonctionnels PHPUnit
├── Dockerfile
├── docker-compose.yml
├── nginx.conf
├── .env.example
└── README.md
```

---

## Comptes de test (après fixtures)

| Email | Mot de passe | Rôle |
|-------|-------------|------|
| `admin@ketsia.shop` | `Admin1234!` | ROLE_ADMIN |
| `user@ketsia.shop` | `User1234!` | ROLE_USER |
