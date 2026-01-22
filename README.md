# SmartCafe API

API REST pour application mobile de coffee shop - Gestion des commandes, produits, extras et programme de fidélité.

## Version

**v1.0.0**

## Environnements

| Environnement | URL |
|---------------|-----|
| Production | http://152.228.131.67/api |
| Local | http://localhost:8080/api |
| Swagger UI (local) | http://localhost:8080/api/docs |

## Technologies

| Composant | Technologie |
|-----------|-------------|
| Framework | Symfony 8.0 |
| PHP | >= 8.2 (Docker: 8.4-FPM) |
| Base de données | PostgreSQL 16 |
| API | API Platform 4.2 |
| Authentification | JWT (Lexik) + Refresh Token |
| Conteneurisation | Docker & Docker Compose |
| Serveur Web | Nginx |
| CI/CD | GitHub Actions |

## CI/CD

Le projet utilise GitHub Actions pour l'intégration et le déploiement continus :

| Workflow | Trigger | Description |
|----------|---------|-------------|
| CI | Push/PR sur main, develop | Tests, PHPStan, PHP-CS-Fixer, Security |
| CD | Push sur main | Build Docker + Déploiement production |

### Statut
- **CI** : Tests unitaires, fonctionnels, analyse statique, code style
- **CD** : Déploiement automatique sur le serveur de production

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

## Documentation

| Document | Chemin |
|----------|--------|
| Documentation technique HTML | [docs/technical.html](docs/technical.html) |
| Documentation technique PDF | [docs/technical.pdf](docs/technical.pdf) |
| Collection Postman | [SmartCafe.postman_collection.json](SmartCafe.postman_collection.json) |

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
│   │   ├── Controller/  # Controllers (AuthController)
│   │   ├── Entity/      # Entités Doctrine (11 entités)
│   │   ├── Service/     # Services métier
│   │   ├── State/       # Processors API Platform
│   │   └── ...
│   ├── tests/           # Tests PHPUnit
│   └── config/          # Configuration
├── docker/              # Config Docker
├── docs/                # Documentation
│   ├── technical.html   # Doc technique HTML
│   └── technical.pdf    # Doc technique PDF
├── .github/workflows/   # CI/CD GitHub Actions
├── docker-compose.yml
├── Makefile
└── README.md
```

## API Endpoints principaux

### Authentification
- `POST /api/login` - Connexion (JWT + cookie refresh)
- `POST /api/token/refresh` - Rafraîchir le token
- `GET /api/auth/me` - Profil utilisateur

### Produits & Extras
- `GET /api/products` - Liste des produits
- `GET /api/extras` - Liste des extras

### Commandes
- `POST /api/orders` - Créer une commande
- `GET /api/auth/me/orders` - Mes commandes

### Fidélité
- `GET /api/loyalty/rewards` - Récompenses disponibles
- `POST /api/loyalty/rewards/{id}/redeem` - Échanger une récompense
- `GET /api/auth/me/loyalty` - Mon compte fidélité

## Licence

Projet académique - M2 2025-2026
