# Requirements

Before installing Lambda CMS, make sure your environment meets the following requirements.

## Server requirements

| Requirement | Minimum version |
|---|---|
| PHP | 8.2 |
| Composer | 2.x |
| Node.js | 18.x |
| npm | 9.x |

### Required PHP extensions

- `pdo` and `pdo_sqlite` (or `pdo_mysql` for MySQL)
- `mbstring`
- `openssl`
- `tokenizer`
- `xml`
- `ctype`
- `json`
- `bcmath`
- `fileinfo`
- `gd` or `imagick` (for image processing via Intervention Image)

## Database

Lambda CMS supports two database drivers:

- **SQLite** — the default. No setup required; a `database/database.sqlite` file is created automatically during installation.
- **MySQL 8+** — choose this during the installation wizard if you need a dedicated database server.

## Browser requirements (admin panel)

Lambda CMS's admin UI targets modern evergreen browsers:

- Chrome / Edge 90+
- Firefox 88+
- Safari 14+

## Optional

- **Queue worker** — recommended for webhook dispatching and email notifications in production. Lambda CMS works without a queue (using the `sync` driver) but a real queue (e.g. `database` or `redis`) improves response times.
- **Mail server** — required for comment notification emails, user invitation emails, and the test-send feature. Configure via the Settings panel or `.env`.
