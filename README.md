# Outlook Web - Email Management Application

A Laravel-based web application for managing email accounts and sending emails through various providers, with a focus on Zoho Mail integration.

## Features

- **User Authentication**: Complete user registration and login system with Laravel Breeze
- **Email Account Management**: Connect and manage multiple email accounts from different providers
- **Email Sending**: Send emails through connected accounts using Python scripts
- **Email Synchronization**: Fetch and sync emails from connected accounts
- **OAuth Integration**: Secure authentication with email providers (Zoho planned)
- **Modern UI**: Built with Tailwind CSS and Alpine.js for a responsive interface

## Technology Stack

- **Backend**: Laravel 12.x (PHP 8.2+)
- **Frontend**: Tailwind CSS, Alpine.js, Vite
- **Database**: SQLite (default) or MySQL/PostgreSQL
- **Email Processing**: Python scripts for IMAP/SMTP operations
- **Authentication**: Laravel Breeze with OAuth support

## Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js and npm
- Python 3.x (for email operations)
- SQLite (or MySQL/PostgreSQL)

## Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd outlook-web
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Database setup**
   ```bash
   php artisan migrate
   ```

6. **Build frontend assets**
   ```bash
   npm run build
   ```

## Development

### Running the application

For development, you can use the built-in development script that runs multiple services concurrently:

```bash
composer run dev
```

This will start:
- Laravel development server
- Queue worker
- Log viewer (Pail)
- Vite development server

### Manual development setup

Alternatively, you can run services individually:

```bash
# Start Laravel server
php artisan serve

# Start queue worker (in another terminal)
php artisan queue:listen

# Start Vite dev server (in another terminal)
npm run dev
```

### Testing

Run the test suite:

```bash
composer run test
```

## Project Structure

### Key Components

- **Models**: `User`, `EmailAccount` - Core data models
- **Controllers**: 
  - `EmailController` - Handles email sending functionality
  - `AuthController` - Manages OAuth authentication
  - `ProfileController` - User profile management
- **Python Scripts**:
  - `send_mail.py` - SMTP email sending
  - `sync_emails.py` - IMAP email synchronization
- **Database Migrations**: User management, email accounts, and email storage

### Email Provider Support

Currently supports:
- **Zoho Mail** (IMAP/SMTP)

Planned support for additional providers through OAuth integration.

## API Endpoints

### Authentication
- `POST /emails/send` - Send email through connected account
- `GET /auth/{provider}` - OAuth redirect
- `GET /auth/{provider}/callback` - OAuth callback

### Protected Routes
- `GET /dashboard` - Main dashboard
- `GET /profile` - User profile management

## Configuration

### Environment Variables

Key environment variables in `.env`:

```env
APP_NAME="Outlook Web"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database/database.sqlite

# Email provider configurations
# (Add as needed for OAuth setup)
```

### Database

The application uses SQLite by default but can be configured for MySQL or PostgreSQL by updating the database configuration in `.env`.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Run tests to ensure everything works
5. Submit a pull request

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Notes

- The application is designed to work with XAMPP on Windows
- Python scripts are used for email operations to leverage existing email libraries
- OAuth integration for Zoho is planned but not yet implemented
- The application uses Laravel's built-in authentication and session management