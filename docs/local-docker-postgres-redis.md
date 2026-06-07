# Local Docker PostgreSQL and Redis

Panduan ini untuk menjalankan database lokal yang lebih mendekati production memakai Docker Desktop.

## Start Services

```bash
docker compose -f docker-compose.local.yml up -d
docker compose -f docker-compose.local.yml ps
```

Service yang dibuat:

- PostgreSQL 16: `127.0.0.1:5432`
- Redis 7: `127.0.0.1:6379`

## Local Env

Contoh env tersedia di:

```text
deploy/env/local.docker-postgres-redis.env.example
```

Nilai utama:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=lansia_papua
DB_USERNAME=lansia
DB_PASSWORD=lansia_secret

SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis
QUEUE_AFTER_COMMIT=true

REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

`REDIS_CLIENT=predis` dipakai agar Redis bisa berjalan lokal tanpa mengaktifkan ekstensi PHP `redis`.

## Migrate and Seed

```bash
php artisan config:clear
php artisan migrate:fresh --seed
```

Set password seed lokal di `.env` sebelum seed jika ingin login dengan password yang diketahui:

```env
LANSIA_ADMIN_PASSWORD=password-admin
LANSIA_SURVEYOR_PASSWORD=password-surveyor
LANSIA_VERIFIKATOR_PASSWORD=password-verifikator
```

## Health Check

```bash
php artisan app:production-status
php artisan dashboard:health
```

Hasil yang diharapkan:

```text
Queue connection: redis
Cache store: redis
Dashboard status: OK
```

## Seed Demo Data

Untuk data uji ringan:

```bash
DEMO_SURVEY_COUNT=5000 DEMO_SURVEY_RESET=true php artisan db:seed --class=DemoSurveyResponseSeeder
php artisan dashboard:rebuild-facts --chunk=500
php artisan dashboard:benchmark --iterations=3
```

Di PowerShell:

```powershell
$env:DEMO_SURVEY_COUNT='5000'
$env:DEMO_SURVEY_RESET='true'
php artisan db:seed --class=DemoSurveyResponseSeeder
Remove-Item Env:\DEMO_SURVEY_COUNT
Remove-Item Env:\DEMO_SURVEY_RESET
php artisan dashboard:rebuild-facts --chunk=500
php artisan dashboard:benchmark --iterations=3
```

## Stop Services

```bash
docker compose -f docker-compose.local.yml down
```

Hapus data volume lokal jika ingin benar-benar reset:

```bash
docker compose -f docker-compose.local.yml down -v
```

## SQLite Fallback

Sebelum switch ke PostgreSQL, simpan `.env` lama:

```bash
cp .env .env.sqlite-before-postgres
```

Untuk balik ke SQLite, restore nilai `DB_CONNECTION=sqlite`, `SESSION_DRIVER=file`, `CACHE_STORE=file`, dan `QUEUE_CONNECTION=database`, lalu:

```bash
php artisan config:clear
```
