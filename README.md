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
- **Cloud Email Storage**: Automatic AWS S3 storage for email content and attachments
- **EML Viewer**: View emails from original EML files stored in S3 with fallback to database
- **SMTP Sending**: Provider-aware SMTP via Python script with TLS
- **Attachments**: Store and download attachments to/from AWS S3
- **Email Drafts**: Save and manage email drafts for later composition
- **Labels/Tags**: Manage labels and email-label relationships
- **Rich Email Storage**: Persist headers, HTML, text, dates, flags, and metadata
- **Network Diagnostics**: DNS, socket, TLS, IMAP diagnostics with JSON reports
- **Error Handling & Logging**: Detailed logs in `storage/logs/laravel.log`
- **Modern UI**: Tailwind CSS + Alpine.js, Vite HMR for development
- **Queue Friendly**: Ready to run queue workers for background tasks
- **Windows Friendly**: Tested with XAMPP on Windows

### 🚀 **Advanced Dashboard Features**

- **Modern Email Interface**: Clean, professional dashboard with intuitive email management
- **Advanced Search & Filtering**: 
  - Field-specific search (from, to, subject, body)
  - Smart search suggestions with autocomplete
  - Recent search history with localStorage persistence
  - Comprehensive filters (read/unread, attachments, date ranges, flagged emails)
  - Quick filter buttons (Today, This Week, This Month)
  - Saved searches functionality
- **Bulk Email Operations**:
  - Multi-select checkboxes for email selection
  - Bulk mark as read/unread
  - Bulk flag/unflag emails
  - Bulk delete operations
  - Visual confirmation dialogs
- **Enhanced Email Display**:
  - Visual indicators for read/unread status
  - Flagged email indicators
  - Attachment presence indicators
  - EML content loading from AWS S3
  - Content source indicators (S3 vs Database)
  - Responsive email list and viewer layout
- **Performance Optimizations**:
  - Database indexes for faster search queries
  - Efficient query building with proper WHERE clauses
  - Client-side caching for search suggestions
  - Optimized data loading with pagination
- **Responsive Design**:
  - Mobile-first approach with flexible layouts
  - Touch-friendly interface elements
  - Adaptive layouts for all screen sizes
  - Collapsible advanced filter panels

## Technology Stack

- **Backend**: Laravel 12.x (PHP 8.2+)
- **Frontend**: Tailwind CSS 3.1+, Alpine.js 3.4+, Vite 7.0+
- **Database**: SQLite (default) or MySQL/PostgreSQL
- **Cloud Storage**: AWS S3 for email content and attachments
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
- AWS Account with S3 bucket (for cloud storage)

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
   # Install required Python packages
   py -m pip install boto3
   ```

9. **Configure AWS S3** (for cloud storage)
   ```bash
   # Add AWS credentials to your .env file
   AWS_ACCESS_KEY_ID=your_access_key
   AWS_SECRET_ACCESS_KEY=your_secret_key
   AWS_DEFAULT_REGION=your_region
   AWS_BUCKET=your_bucket_name
   ```

10. **(Optional) Configure OAuth for Zoho**
   - Create a client in your Zoho developer console
   - Set the redirect URL to: `http://localhost:8000/auth/zoho/callback` (adjust host if different)
   - Add the credentials to your `.env` (see Configuration section)

11. **Start services**
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

12. **Configure Scheduler (recommended for periodic syncs)**
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
- **Local Storage**: All synced emails are automatically saved to local folders for offline access.

### 3) View and manage emails
- **Dashboard Interface**: Modern email management with advanced search and filtering capabilities
- **Email List**: View emails with visual indicators for read/unread status, attachments, and flags
- **Advanced Search**: 
  - Use the search bar with smart suggestions and recent search history
  - Toggle advanced filters to search specific fields (from, to, subject, body)
  - Apply quick filters for Today, This Week, or This Month
  - Save frequently used searches for quick access
- **Bulk Operations**: 
  - Select multiple emails using checkboxes
  - Perform bulk actions: mark as read/unread, flag, or delete
  - Use "Select All" and "Clear" for easy selection management
- **Email Viewer**: Click any email to view full content, headers, and attachments
- **Responsive Design**: Optimized for desktop, tablet, and mobile devices
- **Cloud Storage**: All emails are stored in AWS S3 with organized folder structure
- **EML Viewer**: View emails from original EML files stored in S3 with automatic fallback to database

### 4) Email Drafts
- Save email compositions as drafts for later editing and sending.
- Access drafts from the compose interface.
- Edit and resume draft emails at any time.
- Delete drafts when no longer needed.

### 5) Labels
- Create and manage labels under `Labels`.
- Assign labels to emails to organize your inbox.

### 6) Send email
- Use `Compose` (or API endpoint) to send via SMTP.
- Save drafts for later completion and editing.
- Reply to emails with pre-filled recipient and subject.
- Ensure your account has valid SMTP settings or OAuth token.

### 7) AWS S3 Cloud Storage
- **Automatic S3 Upload**: Each email is automatically uploaded to AWS S3 as EML files
- **Organized Storage**: Emails are saved in S3 with organized folder structure matching email providers
- **EML Format**: Emails are stored as `.eml` files in RFC 2822 format for maximum compatibility
- **Storage Structure**: `s3://bucket/emails/email-accounts/{email}/{folder}/{message-id}.eml`
- **Attachment Storage**: All email attachments are stored in S3 under `s3://bucket/emails/attachments/`
- **Fallback Support**: If S3 is unavailable, falls back to database content
- **Content Source Indicators**: Visual indicators show whether content is loaded from S3 or database

### 8) EML Viewer Features
- **S3 Content Loading**: Automatically loads email content from EML files stored in S3
- **Smart Parsing**: Handles both single-part and multipart email content
- **Content Source Display**: Shows whether content is loaded from S3 EML files or database
- **Loading States**: Visual feedback during EML content loading
- **Fallback Support**: Seamlessly falls back to database content if S3 is unavailable
- **Original Format**: Displays emails in their original EML format for maximum fidelity

### 9) Advanced Dashboard Features

#### Search and Filtering
- **Smart Search**: Type in the search bar to find emails across multiple fields
- **Field-Specific Search**: Use advanced filters to search only in specific fields (from, to, subject, body)
- **Search Suggestions**: Get intelligent suggestions as you type, including recent searches
- **Quick Filters**: One-click filters for Today, This Week, This Month
- **Saved Searches**: Save complex search queries for quick reuse
- **Date Range Filtering**: Specify custom date ranges for email searches

#### Bulk Operations
- **Multi-Select**: Use checkboxes to select multiple emails at once
- **Bulk Actions**: 
  - Mark as read/unread
  - Flag/unflag emails
  - Delete multiple emails
- **Selection Management**: "Select All" and "Clear" buttons for easy management
- **Visual Feedback**: Clear indication of selected emails and action results

#### Email Management
- **Visual Indicators**: 
  - Bold text for unread emails
  - Flag icons for important emails
  - Attachment icons for emails with files
- **Responsive Layout**: Optimized for desktop, tablet, and mobile viewing
- **Loading States**: Visual feedback during data loading operations
- **Error Handling**: User-friendly error messages and recovery options

### 10) Diagnostics
- Run network diagnostics:
  ```bash
  python test_network.py imap.zoho.com 993
  ```
- See JSON reports written at repository root (e.g., `network_test_*.json`).
- Test local folder functionality:
  ```bash
  php artisan emails:test-folders [account_id]
  ```
- Clear all email data for fresh start:
  ```bash
  php artisan emails:refresh --force
  ```

## Processes

- **Sync Process**: Validate network → connect IMAP → fetch headers/bodies → parse parts → store email/attachments → upload to AWS S3 → save to database → record Message-ID to prevent duplicates.
- **Send Process**: Build SMTP connection with provider settings → TLS → authenticate (password or OAuth token if supported) → send → record result.
- **Draft Process**: Save email composition state → store in database → allow editing and resuming → send when ready.
- **S3 Storage Process**: Create S3 folder structure → upload EML files to S3 → upload attachments to S3 → maintain organized storage.
- **EML Viewer Process**: Load email from database → fetch EML content from S3 → parse EML content → display with source indicators.
- **Labeling Process**: Manage labels in DB and pivot to emails to support many-to-many classification.
- **Refresh Process**: Clear all email data → remove S3 objects → reset sync state → prepare for fresh sync.

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
  - `EmailDraft` - Email draft storage for composing messages
  - `Attachment` - Email attachment management and storage
  - `Label` - Email labeling and categorization system
- **Controllers**: 
  - `EmailController` - Handles email sending, synchronization, draft management, and bulk operations
  - `EmailAccountController` - Manages email account CRUD operations and connection testing
  - `AuthController` - Manages OAuth authentication flows
  - `ProfileController` - User profile management
  - `AttachmentController` - Handles attachment downloads and viewing
  - `LabelController` - Manages email labeling and categorization
- **Services**:
  - `EmailFolderService` - Manages AWS S3 storage and EML file operations
- **Console Commands**:
  - `SyncEmails` - Command-line email synchronization
  - `TestEmailFolders` - Test local folder functionality and email storage
  - `RefreshEmailData` - Clear all email data and reset sync state for fresh start
- **Python Scripts**:
  - `send_mail.py` - SMTP email sending with provider-specific configuration
  - `sync_emails.py` - IMAP email synchronization with AWS S3 upload support
  - `test_network.py` - Network diagnostics for troubleshooting connectivity issues
- **Database Migrations**: User management, email accounts, email storage, authentication tokens, read/unread status, flagged emails, and search indexes
- **Policies**: `EmailAccountPolicy` - Authorization for email account access

### Email Provider Support

Currently supports:
- **Zoho Mail** (IMAP/SMTP)

Planned support for additional providers through OAuth integration.

### AWS S3 Storage Structure

The application automatically creates and maintains a cloud storage structure in AWS S3 for each email account:

```
s3://your-bucket/emails/
├── email-accounts/
│   └── {sanitized-email-address}/
│       ├── Inbox/
│       │   └── {message-id}.eml
│       ├── Sent/
│       ├── Drafts/
│       ├── Trash/
│       ├── Spam/
│       ├── Archive/
│       ├── Important/ (provider-specific)
│       └── All Mail/ (provider-specific)
└── attachments/
    └── {message-id}/
        ├── attachment1.pdf
        └── attachment2.jpg
```

**Features:**
- **Automatic S3 Upload**: EML files and attachments are automatically uploaded to S3
- **Organized Structure**: Folders match email provider structure (Gmail, Outlook, Zoho)
- **EML Format**: Emails stored as `.eml` files in RFC 2822 format for maximum compatibility
- **Cloud Access**: All emails accessible from anywhere with internet connection
- **Scalable Storage**: Unlimited storage capacity with AWS S3
- **Backup & Recovery**: Built-in AWS S3 backup and versioning capabilities

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
- `POST /emails/save-draft` - Save email draft
- `GET /emails/drafts` - List email drafts
- `GET /emails/draft/{id}` - Get specific email draft
- `DELETE /emails/draft/{id}` - Delete email draft
- `GET /emails/reply/{id}` - Get reply data for email
- `GET /emails/content/{id}` - Get email content from EML file in S3
- `GET /emails/compose` - Show email composition form
- `GET /emails/sync/{accountId}` - Show email sync form with advanced filtering
- `POST /emails/sync/{accountId}` - Synchronize emails from account with search parameters
- `POST /emails/bulk-action` - Perform bulk operations on selected emails
- `POST /auth/zoho/add` - Add Zoho account via OAuth

### Attachments
- `GET /attachments/{id}/download` - Download email attachment
- `GET /attachments/{id}/view` - View email attachment in browser

### Labels
- `GET /labels` - List labels
- `POST /labels/apply` - Apply label to email
- `POST /labels/remove` - Remove label from email

### OAuth
- `GET /auth/{provider}` - OAuth redirect
- `GET /auth/{provider}/callback` - OAuth callback

## Configuration

### Environment Variables

Key environment variables in `.env`:

```env
APP_NAME="Outlook Web"
APP_ENV=local
APP_DEBUG=true
APP_URL=https://cc1fa11dc513.ngrok-free.app

DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database/database.sqlite

# Email provider configurations
# (Add as needed for OAuth setup)

# Zoho OAuth (example)
ZOHO_CLIENT_ID=
ZOHO_CLIENT_SECRET=
ZOHO_REDIRECT_URI="http://localhost:8000/auth/zoho/callback"

# AWS S3 Configuration
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=your_region
AWS_BUCKET=your_bucket_name

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
- **AWS S3 Cloud Storage**: All synced emails are automatically saved to AWS S3 as EML files for cloud access
- Network diagnostics are available for troubleshooting email connectivity issues
- All Python scripts use only standard library modules (no external dependencies required)
- The application includes comprehensive error handling and logging for email operations
- Email files are stored in RFC 2822 format for maximum compatibility with email clients

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

- Back up `database/` (SQLite) or your external DB and `storage/` for attachments and local emails.
- **AWS S3 Storage**: All emails and attachments are stored in AWS S3 with built-in backup and versioning.
- **Local Attachments**: Temporary attachments stored in `storage/app/attachments/` and `storage/app/email-attachments/`.
- Logs are in `storage/logs/`; prune or rotate as needed for disk space.

## FAQ

- "Nothing appears after login" → Ensure migrations ran and queues/Vite are running in dev.
- "Attachments not accessible" → Run `php artisan storage:link` and verify file permissions.
- "SMTP auth failed" → Confirm provider settings, app passwords, or OAuth tokens are valid and not expired.
- "Want to start fresh with email data" → Use `php artisan emails:refresh --force` to clear all email data and start over.
- "Local folders not created" → Use `php artisan emails:test-folders` to verify folder functionality.