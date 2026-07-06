# Plan de tests

## Objectif

Verifier que les parcours essentiels de Ketsia Shop fonctionnent et que la stack reste deployable rapidement.

## Environnement

- Frontend : React + Vite
- Backend : Symfony 7
- Base : MySQL 8
- Automatisation : GitHub Actions
- Execution locale : Docker Compose

## Tests automatises existants

| Zone | Test | Resultat attendu |
| --- | --- | --- |
| Backend | `php bin/phpunit` | Tous les tests passent |
| API produits | `GET /api/products` | Statut 200 |
| Authentification | inscription / connexion | Token JWT retourne |
| Commande | creation commande connectee | Statut 201, stock diminue |
| Commande | stock insuffisant | Statut 422 |
| Commande | utilisateur non connecte | Statut 401 |
| Frontend | `npm run lint` | Aucun probleme bloquant |
| Frontend | `npm run build` | Build de production genere |
| Docker | `docker compose build` | Images backend et frontend construites |

## Tests manuels a realiser avant rendu

| Parcours | Donnees | Resultat attendu |
| --- | --- | --- |
| Inscription | nouvel email | Compte cree |
| Connexion | compte existant | Acces aux pages protegees |
| Catalogue | categories et produits | Liste visible et filtrable |
| Panier | ajout produit | Quantite et total corrects |
| Paiement | parcours Stripe test | Redirection succes |
| Admin produits | creation / edition / suppression | Donnees mises a jour |
| Admin commandes | changement de statut | Statut persiste |
| Responsive | mobile, tablette, desktop | Interface lisible et utilisable |

## Tests de securite minimum

- Acces admin refuse sans role `ROLE_ADMIN`.
- Token JWT obligatoire pour les commandes utilisateur.
- Donnees sensibles absentes du depot (`.env`, cles privees, mots de passe reels).
- CORS limite aux URLs attendues.

## Suivi des resultats

Ajouter une capture GitHub Actions verte dans le dossier de conception et noter les problemes rencontres dans `docs/development-journal.md`.
