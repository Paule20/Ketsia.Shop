# Journal de developpement

## Etat au 2026-07-06

Le projet Ketsia Shop contient deja une application React/Symfony avec authentification JWT, catalogue, panier, commandes, administration, Docker backend et tests PHPUnit. Les ajouts du jour structurent la partie DevOps demandee par le projet myBank.

## Problemes identifies

| Probleme | Cause | Solution appliquee |
| --- | --- | --- |
| CI GitHub vide | `.github/workflows/ci.yaml` ne contenait aucune etape | Ajout d'un pipeline frontend, backend et Docker |
| Frontend non conteneurise | Aucun Dockerfile frontend | Ajout d'une image React build + Nginx |
| Stack complete absente a la racine | Docker limite au dossier backend | Ajout d'un `compose.yaml` global |
| Deploiement non documente | Pas de script de mise a jour serveur | Ajout de scripts Linux et Windows |
| Plan de test non formalise | Tests presents mais non relies aux competences | Ajout de `docs/test-plan.md` |

## Documentation CI/CD

La CI sert a verifier automatiquement le projet a chaque changement. Elle evite d'attendre la correction finale pour decouvrir un probleme de build, de syntaxe ou de tests.

Etapes principales :

1. Installer les dependances frontend et backend.
2. Verifier la qualite frontend avec ESLint.
3. Construire le frontend.
4. Preparer une base MySQL de test.
5. Executer les migrations Symfony.
6. Executer PHPUnit.
7. Construire les images Docker.

## Resolution de probleme

Pour chaque blocage, garder la meme methode :

1. Decrire le symptome.
2. Reproduire avec une commande courte.
3. Isoler la zone concernee : frontend, backend, base, Docker ou CI.
4. Corriger.
5. Relancer le test qui prouve la correction.
6. Noter la conclusion dans ce journal.

## Veille technique

Sujets a suivre avant le rendu :

- versions supportees de Symfony 7 et PHP 8.2 ;
- bonnes pratiques JWT et protection des cles privees ;
- securisation des variables GitHub Actions ;
- evolution de Docker Compose ;
- tests d'integration frontend/backend.
