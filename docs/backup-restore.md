# Backup and Restore

Dokumen ini dipakai untuk memastikan data survey bisa dipulihkan saat server rusak, deploy gagal, atau database perlu dipindahkan.

## Scope

Backup minimal production wajib mencakup:

- Database aplikasi.
- Folder upload privat: `storage/app/private/documents` dan `storage/app/private/photos`.
- File `.env` production, disimpan aman di password manager atau secret manager, bukan di repository.

Command di dokumen ini fokus ke backup database. Upload privat tetap perlu disalin oleh backup server, rsync, snapshot volume, atau object storage backup.

## Konfigurasi

Tambahkan env berikut di production:

```env
BACKUP_DISK=local
BACKUP_DATABASE_PATH=backups/database
BACKUP_DATABASE_KEEP_LATEST=14
BACKUP_DATABASE_MAX_AGE_HOURS=26
MYSQLDUMP_BINARY=mysqldump
PG_DUMP_BINARY=pg_dump
```

`BACKUP_DISK=local` menyimpan backup ke:

```text
storage/app/private/backups/database
```

Pastikan folder `storage` tidak berada di public web root dan hanya bisa dibaca user server aplikasi.

## Backup Manual

Jalankan:

```bash
php artisan app:backup-database
```

Pilih connection tertentu jika perlu:

```bash
php artisan app:backup-database --connection=pgsql
php artisan app:backup-database --connection=mysql
```

Override jumlah backup yang disimpan:

```bash
php artisan app:backup-database --keep=30
```

Command ini mendukung:

- SQLite: copy file database.
- MySQL/MariaDB: `mysqldump`.
- PostgreSQL: `pg_dump`.

Untuk MySQL dan PostgreSQL, output dump di-stream langsung ke file agar tidak memenuhi memory PHP.

## Backup Terjadwal

Contoh cron sudah tersedia:

```text
deploy/cron/lansia-papua-database-backup.cron.example
```

Install di server:

```bash
sudo crontab -e
```

Isi:

```cron
30 1 * * * cd /var/www/lansia-papua && php artisan app:backup-database >> storage/logs/database-backup.log 2>&1
```

Jalankan di jam rendah traffic. Backup database sebaiknya selesai sebelum maintenance dashboard atau rebuild facts dijalankan.

## Restore Drill

Minimal sekali sebelum go-live, lakukan restore drill di environment staging atau lokal.

1. Ambil file backup terbaru dari `storage/app/private/backups/database`.
2. Buat database kosong baru.
3. Restore dump ke database kosong.
4. Arahkan `.env` staging/lokal ke database restore.
5. Jalankan:

```bash
php artisan migrate --force
php artisan dashboard:health --fail-on-warning
php artisan dashboard:benchmark --iterations=3
php artisan uploads:prune-orphans
```

6. Login sebagai administrator.
7. Buka dashboard, daftar lansia, detail survey, halaman verifikasi, dan export data.
8. Catat durasi restore dan ukuran backup.

## Restore PostgreSQL

Contoh restore:

```bash
createdb lansia_papua_restore
psql -d lansia_papua_restore -f storage/app/private/backups/database/database_pgsql_YYYYMMDD_HHMMSS.sql
```

## Restore MySQL/MariaDB

Contoh restore:

```bash
mysql -e "CREATE DATABASE lansia_papua_restore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql lansia_papua_restore < storage/app/private/backups/database/database_mysql_YYYYMMDD_HHMMSS.sql
```

## Restore SQLite

Contoh restore:

```bash
cp storage/app/private/backups/database/database_sqlite_YYYYMMDD_HHMMSS.sqlite database/database.sqlite
php artisan migrate --force
```

## Operational Rules

- Jangan simpan backup di repository.
- Jangan simpan backup hanya di server yang sama. Salin ke storage eksternal atau snapshot volume.
- Enkripsi backup jika dipindahkan ke luar server.
- Uji restore secara berkala, bukan hanya membuat file backup.
- Simpan minimal 14 backup harian, lalu tambah snapshot mingguan/bulanan jika sudah masuk production penuh.
