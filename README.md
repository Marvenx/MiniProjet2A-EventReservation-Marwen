# Event Reservation System - EventHub

---

## 🇫🇷 Description du Projet

**EventHub** est une application web moderne de gestion de réservations d'événements. Elle permet aux utilisateurs de parcourir les événements disponibles, consulter les détails (date, lieu, places disponibles) et effectuer des réservations sécurisées. Les administrateurs disposent d'un tableau de bord complet pour gérer les événements, consulter les réservations et maintenir le système.

### Fonctionnalités Principales
- **Pour les utilisateurs**: Navigation d'événements, consultation de détails, système de réservation avec validation de places
- **Pour les administrateurs**: Tableau de bord statistique, gestion complète des événements (CRUD), consultation des réservations
- **Authentification sécurisée**: Connexion par email/mot de passe, Passkeys (WebAuthn/biométrique), JWT API
- **Interface moderne**: Design responsive avec Bootstrap 5, animations AOS, thème Flatly

---

## 🔧 Technologies Utilisées

- **Backend**: Symfony 7.4 (PHP 8.2+)
- **Base de données**: PostgreSQL 15
- **Frontend**: Bootstrap 5.3, Bootstrap Icons, AOS (Animate On Scroll)
- **Authentification**:
  - Session-based (form login)
  - Passkeys/WebAuthn (biométrique)
  - JWT tokens (API)
- **Containerisation**: Docker & Docker Compose
- **Outils**: Composer, Doctrine ORM, Symfony Console

---

## 📋 Consignes d'Installation

### Prérequis
- **Docker** et **Docker Compose**
- **Git**
- Environ 2GB d'espace disque

---

### 🐳 Installation avec Docker (Recommandé)

Méthode recommandée pour exécuter l'application avec PostgreSQL + Nginx + Mailpit.

#### Démarrage rapide (3 étapes)

**1. Cloner le projet**
```bash
git clone <url-du-repository>
cd event-reservation
```

**2. Exécuter le script de déploiement**

```bash
# Linux/Mac
chmod +x docker-deploy.sh
./docker-deploy.sh init

# Windows (PowerShell)
.\docker-deploy.ps1 init
```

**3. Accéder à l'application**

```
Application: http://localhost:8080
Mailpit (emails): http://localhost:8026
PostgreSQL (host): localhost:5435
```

#### Ce que le script configure en mode `init` ✅
- ✅ Génération des clés JWT
- ✅ Création des conteneurs (PHP 8.2-FPM, Nginx, PostgreSQL 15, Mailpit)
- ✅ Migrations de base de données
- ✅ Chargement des données de démonstration
- ✅ Démarrage de l'application sur le port `8080`

#### Démarrage quotidien (mode `start`)

Après l'initialisation, utilisez le mode `start` (ou `docker compose up -d`) pour relancer le projet sans réexécuter migrations/fixtures.

```bash
# Linux/Mac
./docker-deploy.sh start

# Windows (PowerShell)
.\docker-deploy.ps1 start
```

#### Mode manuel (plus précis avec `.env.docker`)

Cette option force Docker Compose à utiliser les variables de `.env.docker`.

```bash
# Build + start avec l'environnement Docker
docker compose --env-file .env.docker up -d --build

# Initialisation DB (une seule fois ou après reset)
docker compose --env-file .env.docker exec -T php php bin/console doctrine:migrations:migrate --no-interaction
docker compose --env-file .env.docker exec -T php php bin/console doctrine:fixtures:load --no-interaction
```

#### Commandes Docker utiles
```bash
# Afficher les logs
docker compose --env-file .env.docker logs -f

# Accéder au conteneur PHP
docker compose --env-file .env.docker exec php bash

# Arrêter les services
docker compose --env-file .env.docker down

# Redémarrer complètement
docker compose --env-file .env.docker down -v && docker compose --env-file .env.docker up -d --build
```

> Note: `compose.override.yaml` est chargé automatiquement par Docker Compose en local et expose PostgreSQL sur `localhost:5435`.

---

### 💻 Alternative: Installation Locale

Pour développement sans Docker (nécessite PHP et PostgreSQL locaux).

#### Prérequis Locaux
- **PHP 8.2+** avec extensions: `pdo_pgsql`, `sodium`, `zip`, `opcache`
- **Composer** (gestionnaire de dépendances PHP)
- **PostgreSQL 15+**
- **Git**

#### 1. Cloner le projet
```bash
git clone <url-du-repository>
cd event-reservation
```

#### 2. Installer les dépendances
```bash
composer install
```

#### 3. Configurer l'environnement
```bash
# Copier le fichier d'exemple
cp .env.example .env.local
```

Éditer `.env.local` et configurer:
```bash
# Base de données (adapter selon votre config)
DATABASE_URL="postgresql://postgres:password@127.0.0.1:5432/event_reservation?serverVersion=15&charset=utf8"

# Email (optionnel en développement)
MAILER_DSN="gmail://your-email@gmail.com:app-password@default"

# JWT
JWT_PASSPHRASE="your-secret-passphrase-change-in-production"

# Domain
APP_DOMAIN="localhost"
```

#### 4. Créer la base de données et migrations
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

#### 5. Charger les données de démonstration
```bash
php bin/console doctrine:fixtures:load --no-interaction
```

#### 6. Générer les clés JWT
```bash
openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:4096 \
  -aes256 -pass pass:your-passphrase \
  -out config/jwt/private.pem

openssl pkey -in config/jwt/private.pem \
  -pubout -out config/jwt/public.pem \
  -passin pass:your-passphrase
```

#### 7. Lancer le serveur
```bash
php -S localhost:8000 -t public
```

Accès: **http://localhost:8000**

---

## 👤 Comptes de Démonstration

| Email | Mot de passe | Rôle |
|-------|-------------|------|
| user@example.com | user123 | Utilisateur |
| admin@example.com | admin123 | Administrateur |

---

## 🔐 Authentification

### Connexion Utilisateur
1. Accéder à `/login`
2. Entrer email
3. Choisir:
   - **Passkey** (biométrique/Windows Hello)
   - **Mot de passe** (traditionnel)

### Création de Compte
- `/register` - Inscription public (accès utilisateur)
- `/admin/register` - Création admin (protégée, admins uniquement)

### Admin
- `/admin/login` - Connexion administrateur
- `/admin` - Tableau de bord

---

---

## 🇬🇧 English Documentation

# Event Reservation System

A modern web application for managing event reservations built with Symfony 7.

## Features

### User Features
- Browse upcoming events with detailed information
- View event details including date, location, and seat availability
- Make reservations for available events
- Secure authentication via form login or JWT API

### Admin Features
- Dashboard with statistics (events count, reservations count)
- Full CRUD operations on events
- View all reservations across events
- Protected admin area with role-based access

## Technologies

- **Backend**: Symfony 7.4
- **Database**: PostgreSQL 15
- **Authentication**:
  - Form-based login with CSRF protection
  - JWT tokens for API (LexikJWTAuthenticationBundle)
  - Passkeys/WebAuthn support
- **Frontend**: Bootstrap 5 (Bootswatch Flatly theme), Bootstrap Icons
- **Containerization**: Docker

## Installation

### Prerequisites
- **Docker** and **Docker Compose**
- **Git**
- ~2GB disk space

### 🐳 Setup with Docker (Recommended)

Recommended way to run the app with PostgreSQL + Nginx + Mailpit.

#### Quick Start (3 steps)

**1. Clone the repository**
```bash
git clone <repository-url>
cd event-reservation
```

**2. Run the deployment script**

```bash
# Linux/Mac
chmod +x docker-deploy.sh
./docker-deploy.sh init

# Windows (PowerShell)
.\docker-deploy.ps1 init
```

**3. Access the application**

```
Application: http://localhost:8080
Mailpit (emails): http://localhost:8026
PostgreSQL (host): localhost:5435
```

#### What the script configures in `init` mode ✅
- ✅ JWT key generation
- ✅ Container creation (PHP 8.2-FPM, Nginx, PostgreSQL 15, Mailpit)
- ✅ Database migrations
- ✅ Demo data loading
- ✅ App startup on port `8080`

#### Daily Startup (`start` mode)

After first initialization, use `start` mode (or plain `docker compose up -d`) to run the project without rerunning migrations/fixtures.

```bash
# Linux/Mac
./docker-deploy.sh start

# Windows (PowerShell)
.\docker-deploy.ps1 start
```

#### Manual Mode (most precise with `.env.docker`)

This mode forces Docker Compose to use environment values from `.env.docker`.

```bash
# Build + start with Docker environment variables
docker compose --env-file .env.docker up -d --build

# Initialize DB (first run or after reset)
docker compose --env-file .env.docker exec -T php php bin/console doctrine:migrations:migrate --no-interaction
docker compose --env-file .env.docker exec -T php php bin/console doctrine:fixtures:load --no-interaction
```

#### Useful Docker commands
```bash
# View logs
docker compose --env-file .env.docker logs -f

# Access PHP container
docker compose --env-file .env.docker exec php bash

# Stop services
docker compose --env-file .env.docker down

# Full restart
docker compose --env-file .env.docker down -v && docker compose --env-file .env.docker up -d --build
```

> Note: `compose.override.yaml` is auto-loaded by Docker Compose in local development and publishes PostgreSQL on `localhost:5435`.

---

### 💻 Alternative: Local Installation

For development without Docker (requires local PHP and PostgreSQL).

#### Local Prerequisites
- PHP 8.2+ with extensions: pdo_pgsql, sodium, zip, opcache
- Composer
- PostgreSQL 15+
- Git

#### 1. Clone the repository
```bash
git clone <repository-url>
cd event-reservation
```

#### 2. Install dependencies
```bash
composer install
```

#### 3. Configure environment
```bash
# Copy environment template
cp .env.example .env.local
```

Edit `.env.local`:
```bash
# Database (adapt to your config)
DATABASE_URL="postgresql://postgres:password@127.0.0.1:5432/event_reservation?serverVersion=15&charset=utf8"

# Email (optional for development)
MAILER_DSN="gmail://your-email@gmail.com:app-password@default"

# JWT
JWT_PASSPHRASE="your-secret-passphrase-change-in-production"

# Domain
APP_DOMAIN="localhost"
```

#### 4. Create database and migrations
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

#### 5. Load demo data
```bash
php bin/console doctrine:fixtures:load --no-interaction
```

#### 6. Generate JWT keys
```bash
openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:4096 \
  -aes256 -pass pass:your-passphrase \
  -out config/jwt/private.pem

openssl pkey -in config/jwt/private.pem \
  -pubout -out config/jwt/public.pem \
  -passin pass:your-passphrase
```

#### 7. Start development server
```bash
php -S localhost:8000 -t public
```

Access: **http://localhost:8000**

---

### Demo Accounts

| Email | Password | Role |
|-------|----------|------|
| admin@example.com | admin123 | ROLE_ADMIN |
| user@example.com | user123 | ROLE_USER |

## API Endpoints

### Authentication
```bash
# Login (get JWT token)
POST /api/login
Content-Type: application/json
{"username": "admin@example.com", "password": "admin123"}

# Get current user
GET /api/me
Authorization: Bearer <token>
```

## Project Structure

```
src/
├── Controller/
│   ├── AdminController.php    # Admin dashboard and CRUD
│   ├── ApiController.php      # API endpoints
│   ├── EventController.php    # Public event pages
│   ├── ReservationController.php
│   └── SecurityController.php # Login/logout
├── Entity/
│   ├── Event.php
│   ├── Reservation.php
│   ├── User.php
│   └── WebauthnCredential.php
├── Form/
│   ├── EventType.php
│   └── ReservationType.php
└── Repository/
```

## Author

FIA3-GL - ISSAT Sousse - 2025/2026
