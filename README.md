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
  - Passkeys/WebAuthn support (planned)
- **Frontend**: Bootstrap 5 (Bootswatch Flatly theme), Bootstrap Icons
- **Containerization**: Docker (planned)

## Installation

### Prerequisites
- PHP 8.1+ with extensions: pdo_pgsql, sodium
- Composer
- PostgreSQL 15

### Setup

```bash
# Clone the repository
git clone <repository-url>
cd event-reservation

# Install dependencies
composer install

# Configure database in .env
DATABASE_URL="postgresql://postgres:password@127.0.0.1:5432/event_reservation"

# Create database and run migrations
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Load sample data (optional)
php bin/console doctrine:fixtures:load

# Generate JWT keys
php bin/console lexik:jwt:generate-keypair

# Start development server
php -S localhost:8000 -t public
```

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
