# Deployment

## Production Build

After deploying your code, run the full optimisation stack:

```bash
composer install --no-dev --optimize-autoloader
npm run build

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
```

## Scheduler

Lambda CMS uses the Laravel scheduler to auto-publish scheduled posts. Add a single cron entry to your server that runs every minute:

```
* * * * * cd /path/to/lambda-cms && php artisan schedule:run >> /dev/null 2>&1
```

Without this, posts with a future `published_at` date will never go live automatically.

## Queue Worker

Webhooks are dispatched via queued jobs. Run the queue worker in the background:

```bash
php artisan queue:work --sleep=3 --tries=3
```

For production, use a process manager like Supervisor to keep the worker alive:

```ini
[program:lambda-queue]
command=php /path/to/lambda-cms/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
```

## Web Server

### Nginx

```nginx
server {
    listen 80;
    server_name myblog.com;
    root /path/to/lambda-cms/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Apache

Ensure `mod_rewrite` is enabled. The `.htaccess` file in `public/` handles routing automatically.

## File Permissions

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## Storage Link

Make uploaded media publicly accessible:

```bash
php artisan storage:link
```

## HTTPS

Lambda CMS sets secure cookies when `APP_ENV=production`. Ensure your server has a valid TLS certificate. With Let's Encrypt:

```bash
certbot --nginx -d myblog.com
```

## Keeping Up to Date

```bash
git pull origin master
composer install --no-dev --optimize-autoloader
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
