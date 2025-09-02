
# Railway Deployment Guide (CodeIgniter + PHP-Apache)

## Prerequisites
- Railway account
- GitHub repo connected to Railway (or CLI deploys)

## What this repo includes
- Dockerfile: php:8.2-apache, mod_rewrite enabled, mysqli/pdo_mysql installed
- .htaccess: CodeIgniter rewrite rules
- `application/config/database.php`: reads env vars and `DATABASE_URL`

## Environment Variables (set in Railway)
- `PORT`: 80 (Railway sets this automatically; Apache listens on 80)
- `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME` (preferred), or
- `DATABASE_URL` (format: mysql://user:pass@host:port/dbname)
- Optional app envs your app needs

## Steps
1. Push the repo to GitHub.
2. On Railway: New Project → Deploy from GitHub → select this repo.
3. Add a MySQL database on Railway, then copy credentials.
4. In Project → Variables, add:
   - `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME` (or `DATABASE_URL`).
5. Deploy. Wait for build + healthcheck to pass.
6. Visit the generated domain.

## Notes
- If your app expects `public/` as docroot, Dockerfile auto-repoints Apache if folder exists.
- File uploads persist only while container lives; use Railway Volumes or external storage for permanence.
- For debugging, check Railway Logs → Deploy logs and Runtime logs.

## Migrations/seeders
Run your provided PHP scripts via Railway Shell or create one-off deploys as needed.
