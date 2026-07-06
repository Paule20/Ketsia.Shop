# Evaluation des competences

## Niveau global

Niveau estime : intermediaire avance, environ 70%.

Tu as deja une vraie application fonctionnelle avec backend, frontend, authentification, administration, tests et une base Docker. Ce qui manquait surtout etait la partie "preuve de certification" : CI/CD, documentation de deploiement, plan de tests, dossier de conception et journal de developpement.

## Detail par competence

| Competence | Niveau actuel | Statut |
| --- | --- | --- |
| C1 - Environnement de travail | 75% | Docker et stack technique presents, documentation globale ajoutee |
| C2 - Interfaces utilisateur | 65% | React present, mais il faut ajouter captures, tests composants et verification responsive |
| C5 - Analyse et maquettes | 35% | Il manque encore zoning, wireframes, Figma, schemas d'enchainement |
| C9 - Plans de tests | 70% | Tests backend presents, plan de test ajoute, tests frontend a renforcer |
| C10 - Deploiement | 65% | Procedure et scripts ajoutes, il reste a prouver un deploiement reel |
| C11 - DevOps | 70% | CI ajoutee, Docker complet ajoute, il faut obtenir une execution GitHub Actions verte |
| T2 - Resolution de probleme | 65% | Methode ajoutee dans le journal, a alimenter avec tes vrais blocages |
| T3 - Apprendre en continu | 50% | Section veille ajoutee, il faut citer 3 a 5 sources exploitees |

## Priorites pour valider

1. Pousser le projet sur GitHub et obtenir une CI verte.
2. Lancer `docker compose up -d --build` depuis la racine et capturer les services.
3. Completer le dossier de conception avec maquettes, UML, schema BDD et captures.
4. Ajouter au moins quelques tests frontend ou tests d'integration bout en bout.
5. Faire une page de synthese avec liens GitHub, Docker, Figma et URL de demo.

## Risques restants

- Le projet myBank demande une application de depenses bancaires, alors que Ketsia Shop est une boutique e-commerce. Pour la certification DevOps, les preuves techniques peuvent aider, mais pour la conformite fonctionnelle il faudra soit justifier l'adaptation, soit creer une branche/projet myBank avec operations et categories.
- Les secrets JWT ne doivent pas etre livres avec de vraies valeurs.
- Une CI non executee ou rouge comptera comme preuve faible.
