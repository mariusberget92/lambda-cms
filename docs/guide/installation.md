# Installation

## Requirements

- PHP 8.2 or higher (with `gd` and `sqlite3` extensions)
- Composer 2
- Node.js 20+ and npm
- SQLite (built into PHP) or MySQL 8+

## Install

Clone the repository and install dependencies:

```bash
git clone https://github.com/mariusberget92/lambda-cms.git
cd lambda-cms

composer install
npm install
```

Generate the application key and build assets:

```bash
cp .env.example .env
php artisan key:generate
npm run build
```

## Run the Setup Wizard

Start a local server:

```bash
php artisan serve
```

Navigate to your site in a browser. Lambda CMS detects that it hasn't been installed and redirects you to `/install`. The five-step wizard walks you through:

1. **Database** — choose SQLite (zero config) or MySQL and verify the connection
2. **Site** — set your site name and URL
3. **Admin** — create your administrator account
4. **Mail** — configure SMTP or leave as `log` driver for local development
5. **Genre** — pick a content theme to pre-seed demo posts, or start empty

After the wizard completes, you're redirected to the login page. Sign in with the admin credentials you just created.

## Local Development

For active development, run the Vite dev server alongside Laravel:

```bash
# Terminal 1
php artisan serve

# Terminal 2
npm run dev
```

Hot module replacement is enabled. Changes to Vue components and CSS refresh instantly.

## Environment Variables

The key variables to set in `.env`:

| Variable | Description |
|---|---|
| `APP_NAME` | Your site name |
| `APP_URL` | Full URL including protocol, e.g. `https://myblog.com` |
| `DB_CONNECTION` | `sqlite` (default) or `mysql` |
| `MAIL_MAILER` | `smtp`, `log`, or `mailgun` |
| `QUEUE_CONNECTION` | `database` (default) or `sync` for development |

For a full list, see `.env.example` in the repository root.
