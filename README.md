# TempoRH: gestion RH interne

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
| Front-end | Twig, AssetMapper, Stimulus, Symfony UX Live Components |
| Base de données | MariaDB |
| Emails | Symfony Mailer + Mailpit (dev) |
| Fichiers | VichUploaderBundle |
| Qualité | PHPUnit, PHPStan, PHP-CS-Fixer, Twig-CS-Fixer |
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
docker compose exec php php bin/phpunit
```

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
```
