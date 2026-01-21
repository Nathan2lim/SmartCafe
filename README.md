# SmartCafe API

API REST de gestion de café avec système de commandes, gestion de stocks et programme de fidélité.

## Version

**v1.0.0**

## Technologies

| Composant | Technologie |
|-----------|-------------|
| Framework | Symfony 8.0 |
| PHP | >= 8.2 (Docker: 8.4-FPM) |
| Base de données | PostgreSQL 15 |
| API | API Platform 4.2 |
| Authentification | JWT (Lexik) + Refresh Token |
| Conteneurisation | Docker & Docker Compose |
| Serveur Web | Nginx |

## Lancement du projet

### Prérequis

- Docker et Docker Compose installés

### Démarrage rapide

```bash
# Initialisation complète (build + démarrage + BDD + migrations)
make init

# Charger les données de test (optionnel)
make db-seed

# Générer les clés JWT
make jwt-generate
```

### Commandes utiles

```bash
make up           # Démarrer les containers
make down         # Arrêter les containers
make restart      # Redémarrer
make logs         # Voir les logs
make shell        # Accès shell PHP
make test         # Lancer les tests
make qa           # Qualité (tests + phpstan + cs)
```

## Accès

| Ressource | URL |
|-----------|-----|
| API | http://localhost:8080/api |
| Swagger UI | http://localhost:8080/api/docs |
| Documentation technique | [docs/technical.html](docs/technical.html) |

## Comptes de test

| Rôle | Email | Mot de passe |
|------|-------|--------------|
| Admin | admin@smartcafe.fr | admin123 |
| User | john.doe@example.com | password123 |

## Structure du projet

```
SmartCafe/
├── api/                 # Backend Symfony
│   ├── src/
│   │   ├── Controller/  # Controllers
│   │   ├── Entity/      # Entités Doctrine
│   │   ├── Service/     # Services métier
│   │   ├── State/       # Processors API Platform
│   │   └── ...
│   └── config/          # Configuration
├── docker/              # Config Docker
├── docs/                # Documentation
│   └── technical.html   # Doc technique complète
├── docker-compose.yml
├── Makefile
└── README.md
```
