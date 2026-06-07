# Production Operations

Catatan ini fokus ke bagian yang perlu hidup terus di server: cache dashboard, queue analytics, dan rebuild fakta dashboard.

## Local Run

Jalankan app lokal di port 8080:

```bash
php artisan serve --host=127.0.0.1 --port=8080
```

Jalankan worker queue untuk proses analitik:

```bash
php artisan queue:work --queue=analytics,default --tries=3 --timeout=120 --sleep=2
```

Kalau data lama perlu dihitung ulang ke tabel fakta dashboard:

```bash
php artisan dashboard:rebuild-facts --chunk=250
```

Benchmark dashboard lokal:

```bash
php artisan dashboard:benchmark --iterations=5
php artisan dashboard:benchmark --iterations=3 --rebuild --chunk=500
```

## Production Env

Minimal production masih bisa memakai database queue:

```env
APP_ENV=production
APP_DEBUG=false
FORCE_HTTPS=true
QUEUE_CONNECTION=database
QUEUE_AFTER_COMMIT=true
CACHE_STORE=database
SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
DB_QUEUE_RETRY_AFTER=180
DASHBOARD_CACHE_TTL=900
```

Template lengkapnya ada di:

```text
deploy/env/production.database-queue.env.example
```

Untuk traffic lebih besar, pakai Redis:

```env
QUEUE_CONNECTION=redis
QUEUE_AFTER_COMMIT=true
CACHE_STORE=redis
SESSION_DRIVER=redis
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
REDIS_QUEUE_RETRY_AFTER=180
DASHBOARD_CACHE_TTL=900
```

Template lengkapnya ada di:

```text
deploy/env/production.redis.env.example
```

Variabel dashboard yang penting:

```env
DASHBOARD_CACHE_TTL=900
DASHBOARD_RAW_FALLBACK_LIMIT=5000
DASHBOARD_HEALTH_MAX_PENDING_JOBS=1000
DASHBOARD_HEALTH_MAX_FAILED_JOBS=0
DASHBOARD_HEALTH_FACT_STALE_MINUTES=1440
DASHBOARD_SCHEDULED_REBUILD_ENABLED=false
DASHBOARD_SCHEDULED_REBUILD_TIME=02:00
DASHBOARD_SCHEDULED_REBUILD_CHUNK=500
```

Variabel upload/storage:

```env
PRIVATE_UPLOAD_DISK=local
LEGACY_PUBLIC_UPLOAD_DISK=public
DOCUMENT_UPLOAD_MAX_KB=5120
PHOTO_UPLOAD_MAX_KB=2048
```

Variabel backup database:

```env
BACKUP_DISK=local
BACKUP_DATABASE_PATH=backups/database
BACKUP_DATABASE_KEEP_LATEST=14
BACKUP_DATABASE_MAX_AGE_HOURS=26
MYSQLDUMP_BINARY=mysqldump
PG_DUMP_BINARY=pg_dump
```

Dokumen pendukung dan foto profil baru dilayani lewat route authenticated. Disk `local` mengarah ke `storage/app/private`, sehingga file sensitif tidak perlu berada di public web root.

Audit storage upload secara berkala:

```bash
php artisan uploads:prune-orphans
```

Command ini default-nya dry-run. Ia melaporkan file orphan di folder `documents/` dan `photos/`, serta referensi database yang filenya hilang atau path-nya tidak aman. Setelah daftar sudah dicek, hapus orphan dengan:

```bash
php artisan uploads:prune-orphans --delete
```

Kalau masih ada file lama dari disk public legacy, ikutkan scan legacy:

```bash
php artisan uploads:prune-orphans --legacy
php artisan uploads:prune-orphans --legacy --delete
```

Saat foto profil diganti, file foto lama akan dihapus setelah transaksi database sukses. Dokumen pendukung lama tidak langsung dihapus; sistem menandainya bukan `is_latest` agar riwayat upload tetap ada.

## Worker

Worker harus selalu hidup. Di Linux server, jalankan lewat Supervisor atau service manager lain.

File contoh Supervisor sudah disiapkan:

```text
deploy/supervisor/lansia-papua-analytics-worker.conf.example
deploy/supervisor/lansia-papua-default-worker.conf.example
```

Contoh worker gabungan kalau server masih kecil:

```ini
[program:lansia-papua-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/lansia-papua/artisan queue:work --queue=analytics,default --tries=3 --timeout=120 --sleep=2
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/lansia-papua/storage/logs/worker.log
stopwaitsecs=180
```

Kalau volume data sudah besar, pisahkan worker analytics:

```ini
command=php /var/www/lansia-papua/artisan queue:work --queue=analytics --tries=3 --timeout=180 --sleep=2
numprocs=2
```

dan worker default:

```ini
command=php /var/www/lansia-papua/artisan queue:work --queue=default --tries=3 --timeout=120 --sleep=2
numprocs=2
```

Install contoh config ke Supervisor:

```bash
sudo cp deploy/supervisor/lansia-papua-analytics-worker.conf.example /etc/supervisor/conf.d/lansia-papua-analytics-worker.conf
sudo cp deploy/supervisor/lansia-papua-default-worker.conf.example /etc/supervisor/conf.d/lansia-papua-default-worker.conf
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
```

## Scheduler

Laravel scheduler perlu dipanggil setiap menit oleh cron. File contoh:

```text
deploy/cron/lansia-papua-scheduler.cron.example
```

Install cron:

```bash
sudo crontab -e
```

Isi:

```cron
* * * * * cd /var/www/lansia-papua && php artisan schedule:run >> /dev/null 2>&1
```

Schedule yang aktif:

```bash
php artisan schedule:list
```

Saat ini schedule production:

```text
dashboard:health --fail-on-warning     setiap jam
dashboard:rebuild-facts --chunk=500    opsional, aktif jika DASHBOARD_SCHEDULED_REBUILD_ENABLED=true
```

Untuk data besar, biarkan full rebuild nonaktif secara default. Fakta dashboard sudah dijaga incremental lewat queue saat survey dibuat, direvisi, atau diverifikasi. Jalankan full rebuild hanya saat migrasi data, perubahan katalog pertanyaan, atau maintenance off-hours.

Full rebuild facts berjalan atomic dalam satu transaksi database. Ini membuat proses lebih aman: jika rebuild gagal, database bisa rollback dan tidak meninggalkan facts setengah jadi. Tetap jalankan saat traffic rendah karena proses ini berat.

Log scheduler dashboard:

```text
storage/logs/dashboard-health.log
storage/logs/dashboard-rebuild.log
```

## Backup and Restore

Backup manual database:

```bash
php artisan app:backup-database
```

Contoh cron backup harian:

```text
deploy/cron/lansia-papua-database-backup.cron.example
```

Panduan lengkap backup, restore, dan restore drill:

```text
docs/backup-restore.md
```

## Monitoring and Incident Readiness

Status production gabungan:

```bash
php artisan app:production-status
php artisan app:production-status --fail-on-warning
```

Command ini mengecek health dashboard, queue analytics, failed jobs, dan usia backup database terakhir.

Contoh cron monitoring hourly:

```text
deploy/cron/lansia-papua-production-status.cron.example
```

Runbook saat incident:

```text
docs/incident-runbook.md
```

## Deploy Checklist

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan dashboard:rebuild-facts --chunk=500
php artisan queue:restart
```

Migration `harden_survey_response_foreign_keys` mengubah foreign key survey utama menjadi `restrictOnDelete` untuk database production non-SQLite. Ini mencegah data survey ikut hilang saat user surveyor, respondent, survey, atau region terhapus. Di SQLite lokal migration ini no-op karena SQLite tidak aman untuk alter constraint langsung tanpa rebuild tabel; instalasi SQLite baru tetap mendapat constraint yang sudah diperbaiki dari migration dasar.

Setelah deploy, pastikan:

```bash
php artisan dashboard:health
php artisan dashboard:benchmark --iterations=3
php artisan schedule:list
php artisan queue:failed
php artisan tinker --execute="echo DB::table('dashboard_answer_facts')->distinct()->count('survey_response_id');"
```

Jumlah distinct `survey_response_id` di `dashboard_answer_facts` idealnya sama dengan jumlah data survey yang sudah masuk.
