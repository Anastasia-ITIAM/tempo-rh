# TempoRH: gestion RH interne

[![CI](https://github.com/Anastasia-ITIAM/tempo-rh/actions/workflows/ci.yml/badge.svg)](https://github.com/Anastasia-ITIAM/tempo-rh/actions/workflows/ci.yml)

Application web de gestion RH interne : **feuilles de temps**, **demandes de congés** et **notes de frais**, avec un espace collaborateur et un espace administrateur.

> Projet développé en entreprise, où j'ai été la principale développeuse. Il est publié ici avec l'accord de l'entreprise, dans une version anonymisée : nom, logo, historique, configuration de production et données clients ont été retirés.

## Fonctionnalités

**Espace collaborateur**
- Saisie des feuilles de temps à la semaine (projets, tâches, heures) et soumission pour validation
- Demandes de congés, avec suivi du statut et demande d'annulation
- Notes de frais avec justificatifs
- Notifications in-app et par email à chaque changement de statut
- Gestion du profil et réinitialisation du mot de passe

**Espace administrateur**
- Validation ou rejet des feuilles de temps, avec motif et historique des semaines
- Traitement des congés et des notes de frais (validation, refus, remboursement)
- Gestion des utilisateurs, clients, projets et tâches
- Tableau de bord EasyAdmin

## Stack technique

| Domaine | Outils |
|---|---|
| Back-end | PHP 8.4, Symfony 8, Doctrine ORM 3 |
| Admin | EasyAdmin 4 |
| Front-end | Twig, AssetMapper, JavaScript |
| Base de données | MariaDB |
| Emails | Symfony Mailer + Mailpit (dev) |
| Fichiers | VichUploaderBundle |
| Qualité | PHPUnit, PHPStan, PHP-CS-Fixer, Twig-CS-Fixer, GitHub Actions |
| Environnement | Docker Compose (PHP-FPM, Nginx, MariaDB, phpMyAdmin, Mailpit) |

## Installation

Il faut avoir Docker et Docker Compose installés.

```bash
git clone <url-du-depot> temporh && cd temporh

# Démarrer les conteneurs
docker compose up -d

# Dépendances PHP et assets
make install
make mapper-install

# Base de données (créée automatiquement par Docker) et données de démo
make migrate
docker compose exec php php bin/console doctrine:fixtures:load --no-interaction
```

| Service | URL |
|---|---|
| Application | http://localhost:8090 |
| phpMyAdmin | http://localhost:8091 |
| Mailpit (emails) | http://localhost:8030 |

### Comptes de démo (fixtures)

| Rôle | Email | Mot de passe |
|---|---|---|
| Admin | admin@temporh.example | password |
| Utilisateur | user1@temporh.example | password |

## Qualité du code

```bash
make phpstan   # analyse statique
make phpcs     # style PHP
make twigcs    # style Twig
docker compose exec php php bin/phpunit --testsuite Unit          # tests unitaires
docker compose exec php php bin/phpunit --testsuite Integration   # tests avec base de données
```

**Tests unitaires** (`tests/Unit`) : calcul des jours ouvrés et des demi-journées, contrôle des soldes de congés (CP et RTT), déduction et restitution des soldes, renouvellement annuel des types de congés, calcul du lundi de la semaine pour les feuilles de temps.

**Intégration continue** : à chaque push sur `main` et à chaque pull request, GitHub Actions vérifie le style du code (PHP-CS-Fixer), lance l'analyse statique (PHPStan) et les tests unitaires (PHPUnit).

## Performance

- **Export des congés** : la période, la raison et l'employé sont chargés en une seule requête avec jointures. Avant, Doctrine lançait une requête supplémentaire pour chaque congé exporté (problème N+1). Les congés sont aussi regroupés par employé en un seul passage.
- **Renouvellement annuel des congés** : il s'exécute à chaque ouverture du tableau de bord. La base ne renvoie plus que les types de congés expirés et non archivés (index sur `archived_at, end_date`), au lieu de charger tous les types de congés de tous les employés.
- **Index** sur les feuilles de temps : `(user_id, start_date)` pour retrouver la semaine d'un employé, `(status, start_date)` pour la liste filtrée de l'administration.

## Architecture

```
src/
├── Controller/   # Admin/, User/, Common/ (login, profil, mot de passe)
├── Entity/       # TimeSheet, LeaveRequest, ExpenseReport, User, Notification…
├── Enum/         # statuts métier
├── Service/      # logique métier (validation, notifications…)
├── Repository/
└── Form/
templates/        # vues Twig + emails de notification
tests/
├── Unit/         # tests unitaires (entités et services métier)
└── Integration/  # tests avec base de données
```
