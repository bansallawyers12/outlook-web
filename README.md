# Outlook Web - Email Management Application

A Laravel-based web application for managing email accounts and sending emails through various providers, with a focus on Zoho Mail integration.

## Purpose

- Centralize email account management for multiple providers in a single UI.
- Synchronize and store messages locally for fast search and offline-friendly access.
- Provide reliable SMTP sending with provider-specific settings and diagnostics.
- Offer a Windows/XAMPP-friendly stack that is easy to run on a developer machine.
- Serve as a reference implementation for integrating OAuth (Zoho), IMAP sync, and SMTP send with Laravel.

## Features

- **User Authentication**: Registration, login, profile management (Laravel Breeze)
- **Authorization**: Policies for per-user access to `EmailAccount` resources
- **Multi-Account**: Connect and manage multiple accounts per user
- **OAuth & Password Auth**: OAuth (Zoho) and classic username/password
- **Connection & Auth Tests**: Validate connectivity and credentials before saving
- **IMAP Email Sync**: Incremental sync with Message-ID de-duplication
- **SMTP Sending**: Provider-aware SMTP via Python script with TLS
- **Attachments**: Store and download attachments to/from `storage/app/attachments`
- **Labels/Tags**: Manage labels and email-label relationships
- **Rich Email Storage**: Persist headers, HTML, text, dates, flags, and metadata
- **Network Diagnostics**: DNS, socket, TLS, IMAP diagnostics with JSON reports
- **Error Handling & Logging**: Detailed logs in `storage/logs/laravel.log`
- **Modern UI**: Tailwind CSS + Alpine.js, Vite HMR for development
- **Queue Friendly**: Ready to run queue workers for background tasks
- **Windows Friendly**: Tested with XAMPP on Windows

## Technology Stack

- **Backend**: Laravel 12.x (PHP 8.2+)
- **Frontend**: Tailwind CSS 3.1+, Alpine.js 3.4+, Vite 7.0+
- **Database**: SQLite (default) or MySQL/PostgreSQL
- **Email Processing**: Python 3.x scripts for IMAP/SMTP operations
- **Authentication**: Laravel Breeze with OAuth support (Laravel Socialite)
- **Testing**: Pest PHP testing framework
- **Development Tools**: Laravel Pail (log viewer), Laravel Pint (code style)

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
   # Create SQLite database file if using SQLite (default)
   # Windows PowerShell example:
   if (!(Test-Path -Path "database/database.sqlite")) { New-Item -ItemType File -Path "database/database.sqlite" | Out-Null }

   # Or bash:
   # touch database/database.sqlite

   # Run migrations
   php artisan migrate
   ```

6. **Link storage (for attachments and public assets)**
   ```bash
   php artisan storage:link
   ```

7. **Build frontend assets**
   ```bash
   npm run build
   ```

8. **Install Python dependencies** (for email operations)
   ```bash
   # Python scripts use standard library modules only
   # No additional pip packages required
   ```

9. **(Optional) Configure OAuth for Zoho**
   - Create a client in your Zoho developer console
   - Set the redirect URL to: `http://localhost:8000/auth/zoho/callback` (adjust host if different)
   - Add the credentials to your `.env` (see Configuration section)

10. **Start services**
    - Development script (recommended):
      ```bash
      composer run dev
      ```
    - Or run individually (in separate terminals):
      ```bash
      php artisan serve
      php artisan queue:listen
      npm run dev
      ```

11. **Configure Scheduler (recommended for periodic syncs)**
    - Windows Task Scheduler example (run every 5 minutes):
      - Program/script: `pwsh`
      - Arguments: `-NoProfile -NonInteractive -Command "cd C:\xampp\htdocs\outlook-web; php artisan schedule:run --verbose --no-interaction"`
      - Start in: `C:\xampp\htdocs\outlook-web`
    - Or use a background PowerShell job or a cron on Linux.

## Development

### Running the application

For development, you can use the built-in development script that runs multiple services concurrently:

```bash
composer run dev
```

This will start:
- Laravel development server (http://localhost:8000)
- Queue worker for background jobs
- Log viewer (Pail) for real-time log monitoring
- Vite development server for hot module replacement

The development script uses `concurrently` to run all services with color-coded output and automatic cleanup on exit.

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

Note: If you're running under XAMPP/Apache, point your virtual host/document root to the `public` directory of this project.

## Usage Guide

### 1) Create or connect an email account
- Go to `Accounts > Create` and choose either:
  - OAuth (Zoho): Click "Connect with Zoho", complete consent, and save
  - Password auth: Enter IMAP/SMTP credentials (for supported providers)
- Use "Test Connection" and/or "Test Authentication" before saving.

### 2) Synchronize emails
- Navigate to `Emails > Sync` for the chosen account.
- Optionally specify folder, limit, or date range.
- Start sync and monitor progress; errors appear in logs and UI.

### 3) View and manage emails
- `Emails` page lists messages with pagination and filters.
- Click a message to view details, headers, HTML, and attachments.
- Download attachments stored under `storage/app/attachments`.

### 4) Labels
- Create and manage labels under `Labels`.
- Assign labels to emails to organize your inbox.

### 5) Send email
- Use `Compose` (or API endpoint) to send via SMTP.
- Ensure your account has valid SMTP settings or OAuth token.

### 6) Diagnostics
- Run network diagnostics:
  ```bash
  python test_network.py imap.zoho.com 993
  ```
- See JSON reports written at repository root (e.g., `network_test_*.json`).

## Processes

- **Sync Process**: Validate network → connect IMAP → fetch headers/bodies → parse parts → store email/attachments → record Message-ID to prevent duplicates.
- **Send Process**: Build SMTP connection with provider settings → TLS → authenticate (password or OAuth token if supported) → send → record result.
- **Labeling Process**: Manage labels in DB and pivot to emails to support many-to-many classification.

### Testing

Run the test suite:

```bash
composer run test
```

## Project Structure

### Key Components

- **Models**: 
  - `User` - User authentication and profile management
  - `EmailAccount` - Email account storage with OAuth and password support
  - `Email` - Synchronized email storage with full metadata
- **Controllers**: 
  - `EmailController` - Handles email sending and synchronization
  - `EmailAccountController` - Manages email account CRUD operations and connection testing
  - `AuthController` - Manages OAuth authentication flows
  - `ProfileController` - User profile management
- **Python Scripts**:
  - `send_mail.py` - SMTP email sending with provider-specific configuration
  - `sync_emails.py` - IMAP email synchronization with comprehensive error handling
  - `test_network.py` - Network diagnostics for troubleshooting connectivity issues
- **Database Migrations**: User management, email accounts, email storage, and authentication tokens
- **Policies**: `EmailAccountPolicy` - Authorization for email account access

### Email Provider Support

Currently supports:
- **Zoho Mail** (IMAP/SMTP)

Planned support for additional providers through OAuth integration.

### Schedules and Queues

- Use Laravel's scheduler (`schedule:run`) to trigger periodic sync jobs.
- Use queue worker (`queue:listen` or `queue:work`) for background tasks.

### Python Scripts

The application uses Python scripts for email operations to leverage existing email libraries:

#### `send_mail.py`
- **Purpose**: Send emails via SMTP
- **Usage**: `python send_mail.py <provider> <email_user> <token> <to> <subject> <body>`
- **Features**: 
  - Provider-specific SMTP configuration
  - TLS encryption support
  - Error handling and timeout management

#### `sync_emails.py`
- **Purpose**: Synchronize emails from IMAP servers
- **Usage**: `python sync_emails.py <provider> <email_user> <token> [folder] [limit] [start_date] [end_date]`
- **Features**:
  - Comprehensive error handling with detailed debugging
  - Network connectivity testing before connection attempts
  - Support for date range filtering
  - Message ID tracking for duplicate prevention
  - Multi-part email body extraction

#### `test_network.py`
- **Purpose**: Network diagnostics for troubleshooting email connectivity
- **Usage**: `python test_network.py <hostname> [port]`
- **Features**:
  - DNS resolution testing
  - Socket connection testing
  - SSL certificate validation
  - IMAP connection testing
  - Detailed diagnostic reports saved to JSON files

## API Endpoints

### Public Routes
- `GET /` - Welcome page
- `GET /auth/{provider}` - OAuth redirect
- `GET /auth/{provider}/callback` - OAuth callback

### Protected Routes (Authentication Required)
- `GET /dashboard` - Main dashboard with email account overview
- `GET /profile` - User profile management
- `PATCH /profile` - Update user profile
- `DELETE /profile` - Delete user account

### Email Account Management
- `GET /accounts` - List user's email accounts
- `GET /accounts/create` - Show create email account form
- `POST /accounts` - Store new email account
- `GET /accounts/{account}` - Show email account details
- `GET /accounts/{account}/edit` - Show edit email account form
- `PATCH /accounts/{account}` - Update email account
- `DELETE /accounts/{account}` - Delete email account
- `POST /accounts/{account}/test-connection` - Test email account connection
- `POST /accounts/{account}/test-authentication` - Test email account authentication

### Email Operations
- `POST /emails/send` - Send email through connected account
- `GET /emails/sync/{accountId}` - Show email sync form
- `POST /emails/sync/{accountId}` - Synchronize emails from account
- `POST /auth/zoho/add` - Add Zoho account via OAuth

### Labels
- `GET /labels` - List labels
- `POST /labels` - Create label
- `PATCH /labels/{label}` - Update label
- `DELETE /labels/{label}` - Delete label
- `POST /labels/{label}/attach/{email}` - Attach label to email
- `DELETE /labels/{label}/detach/{email}` - Detach label from email

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

# Zoho OAuth (example)
ZOHO_CLIENT_ID=
ZOHO_CLIENT_SECRET=
ZOHO_REDIRECT_URI="http://localhost:8000/auth/zoho/callback"

# Queue/worker tuning (optional)
QUEUE_CONNECTION=database
QUEUE_RETRY_AFTER=90

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=debug
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

- The application is designed to work with XAMPP on Windows [[memory:7264487]]
- Python scripts are used for email operations to leverage existing email libraries
- OAuth integration for Zoho is implemented using Laravel Socialite
- The application uses Laravel's built-in authentication and session management
- Email accounts support both OAuth tokens and password authentication
- Network diagnostics are available for troubleshooting email connectivity issues
- All Python scripts use only standard library modules (no external dependencies required)
- The application includes comprehensive error handling and logging for email operations

## Troubleshooting

- "Database file does not exist" with SQLite: ensure `database/database.sqlite` exists and `.env` `DB_DATABASE` points to its absolute path on Windows, e.g. `C:\\xampp\\htdocs\\outlook-web\\database\\database.sqlite`.
- 404 on assets/images: run `php artisan storage:link` and ensure you built assets with `npm run dev` (dev) or `npm run build` (prod).
- Email sync/send issues: check `storage/logs/laravel.log`. Use `python test_network.py imap.zoho.com 993` to validate connectivity.
- OAuth redirect mismatch: verify `APP_URL` and `ZOHO_REDIRECT_URI` match your configured callback in the provider.

## Security

- Keep OAuth secrets in `.env` and never commit them.
- Rotate tokens and passwords regularly; prefer OAuth over password auth when possible.
- Limit access via Laravel policies; ensure accounts are only visible to their owners.

## Backup & Data

- Back up `database/` (SQLite) or your external DB and `storage/` for attachments.
- Logs are in `storage/logs/`; prune or rotate as needed for disk space.

## FAQ

- "Nothing appears after login" → Ensure migrations ran and queues/Vite are running in dev.
- "Attachments not accessible" → Run `php artisan storage:link` and verify file permissions.
- "SMTP auth failed" → Confirm provider settings, app passwords, or OAuth tokens are valid and not expired.