# Installation

Lambda CMS ships with a browser-based installation wizard that walks you through all setup steps in about two minutes.

## 1. Clone the repository

```bash
git clone https://github.com/mariusberget92/lambda-cms.git
cd lambda-cms
```

## 2. Install PHP dependencies

```bash
composer install
```

## 3. Install JavaScript dependencies and build assets

```bash
npm install
npm run build
```

## 4. Create the environment file

```bash
cp .env.example .env
php artisan key:generate
```

## 5. Set directory permissions

```bash
chmod -R 775 storage bootstrap/cache
```

## 6. Run the installation wizard

Start your web server (or use the built-in development server):

```bash
php artisan serve
```

Then visit `http://localhost:8000` in your browser. You will be automatically redirected to the installation wizard, which guides you through five steps:

### Step 1 — Database

Choose **SQLite** (zero-config, recommended for most setups) or **MySQL**. For MySQL, enter your connection details; Lambda CMS will test the connection before proceeding.

### Step 2 — Site settings

Enter your site name, URL, and timezone. These values populate the Settings panel and can be changed later.

### Step 3 — Admin account

Create the first administrator account. This account has full access to the admin panel and cannot be deleted.

### Step 4 — Mail (optional)

Configure your mail driver (SMTP, Mailgun, or log). You can skip this step and configure mail later in **Settings → Mail**.

### Step 5 — Genre / starter content

Choose a content genre (tech, food, travel, gaming, AI, etc.) to pre-seed 10 themed posts with realistic content. Choosing **Blank** skips seeding.

After completing all steps, Lambda CMS runs migrations, seeds the five system templates, and redirects you to the login page.

## Development server

For local development, run Vite's dev server alongside Laravel:

```bash
# Terminal 1 — Laravel
php artisan serve

# Terminal 2 — Vite HMR
npm run dev
```

## Scheduled publishing

To enable automatic publishing of scheduled posts, add the Laravel scheduler to your cron:

```bash
* * * * * cd /path/to/lambda-cms && php artisan schedule:run >> /dev/null 2>&1
```

The `publish:scheduled-posts` command runs every minute and publishes any posts whose `published_at` timestamp has passed.
