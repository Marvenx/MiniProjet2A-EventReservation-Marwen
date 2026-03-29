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

La méthode Docker la plus simple - tout est configuré automatiquement !

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
./docker-deploy.sh

# Windows (PowerShell)
.\docker-deploy.ps1
```

**3. Accéder à l'application**

```
Application: http://localhost:8080
Adminer (DB): http://localhost:8081
```

#### Ce que le script configure automatiquement ✅
- ✅ Génération des clés JWT
- ✅ Création des conteneurs (PHP 8.2, Nginx, PostgreSQL 15)
- ✅ Migrations de base de données
- ✅ Chargement des données de démonstration
- ✅ Configuration complète de l'environnement

#### Commandes Docker utiles
```bash
# Afficher les logs
docker compose logs -f

# Accéder au conteneur PHP
docker compose exec php bash

# Arrêter les services
docker compose down

# Redémarrer complètement
docker compose down -v && docker compose up -d --build
```

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

Simplest method - everything is automatically configured!

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
./docker-deploy.sh

# Windows (PowerShell)
.\docker-deploy.ps1
```

**3. Access the application**

```
Application: http://localhost:8080
Adminer (Database): http://localhost:8081
```

#### What the script automatically configures ✅
- ✅ JWT key generation
- ✅ Container creation (PHP 8.2, Nginx, PostgreSQL 15)
- ✅ Database migrations
- ✅ Demo data loading
- ✅ Full environment setup

#### Useful Docker commands
```bash
# View logs
docker compose logs -f

# Access PHP container
docker compose exec php bash

# Stop services
docker compose down

# Full restart
docker compose down -v && docker compose up -d --build
```

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
