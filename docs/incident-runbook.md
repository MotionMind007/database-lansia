# Incident Runbook

Panduan ini dipakai saat aplikasi production bermasalah. Tujuannya bukan mencari penyebab sempurna di menit pertama, tetapi menjaga data aman, layanan tetap terbaca, dan investigasi punya urutan.

## Pemeriksaan Awal

Jalankan:

```bash
php artisan app:production-status
php artisan dashboard:health --fail-on-warning
php artisan queue:failed
tail -n 100 storage/logs/laravel.log
```

Jika command dipakai dari cron, log status ada di:

```text
storage/logs/production-status.log
```

## Dashboard Lambat

Gejala:

- Halaman dashboard butuh waktu lama.
- Filter dashboard lambat.
- CPU database naik saat banyak user membaca dashboard.

Langkah:

1. Jalankan `php artisan dashboard:health --fail-on-warning`.
2. Cek apakah fakta dashboard stale atau queue analytics menumpuk.
3. Cek worker analytics:

```bash
sudo supervisorctl status
```

4. Jika worker mati, restart:

```bash
sudo supervisorctl restart lansia-papua-analytics-worker:*
sudo supervisorctl restart lansia-papua-default-worker:*
```

5. Jika fakta dashboard perlu dihitung ulang, jalankan saat traffic rendah:

```bash
php artisan dashboard:rebuild-facts --chunk=500
```

## Queue Menumpuk

Gejala:

- Data baru tidak langsung masuk analytics.
- Verifikasi terasa berhasil tetapi dashboard belum berubah.
- `jobs` table atau Redis queue terus bertambah.

Langkah:

1. Cek status:

```bash
php artisan app:production-status
php artisan queue:failed
```

2. Restart worker lewat Supervisor.
3. Jika failed job terkait bug aplikasi, jangan retry massal sebelum patch bug.
4. Setelah bug aman, retry:

```bash
php artisan queue:retry all
```

5. Jika failed job sudah tidak relevan:

```bash
php artisan queue:flush
```

## Backup Gagal atau Terlambat

Gejala:

- `app:production-status` memberi warning backup.
- `storage/logs/database-backup.log` berisi error.
- File backup terakhir lebih tua dari `BACKUP_DATABASE_MAX_AGE_HOURS`.

Langkah:

1. Jalankan manual:

```bash
php artisan app:backup-database
```

2. Pastikan binary dump tersedia:

```bash
which pg_dump
which mysqldump
```

3. Cek permission folder:

```bash
ls -lah storage/app/private/backups/database
```

4. Jika disk penuh, pindahkan backup lama ke storage eksternal dan sisakan ruang.
5. Setelah backup manual berhasil, cek lagi:

```bash
php artisan app:production-status --fail-on-warning
```

## Upload atau Dokumen Tidak Bisa Dibuka

Gejala:

- Foto profil hilang.
- Dokumen survey tidak bisa diunduh.
- User mendapat 404/403 saat membuka dokumen.

Langkah:

1. Cek path di database tidak berbahaya atau hilang:

```bash
php artisan uploads:prune-orphans
```

2. Pastikan disk private benar:

```env
PRIVATE_UPLOAD_DISK=local
```

3. Pastikan file ada di `storage/app/private/documents` atau `storage/app/private/photos`.
4. Jangan memindahkan dokumen sensitif ke folder public.

## Error 500

Gejala:

- Browser menampilkan Internal Server Error.
- User gagal submit survey, verifikasi, atau export.

Langkah:

1. Cek log terbaru:

```bash
tail -n 150 storage/logs/laravel.log
```

2. Pastikan config/cache tidak stale setelah deploy:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

3. Jalankan smoke check:

```bash
php artisan app:production-status
php artisan test --filter=CoreWorkflowTest
```

4. Jika error terjadi setelah deploy, rollback kode lebih dulu lalu investigasi dump/log.

## Go-Live Monitoring Checklist

- `php artisan app:production-status --fail-on-warning` lolos.
- `php artisan dashboard:benchmark --iterations=3` sudah dicatat.
- Backup manual berhasil dan restore drill pernah dicoba.
- Supervisor worker `analytics` dan `default` aktif.
- Cron scheduler dan cron backup sudah aktif.
- `APP_DEBUG=false`.
- `FORCE_HTTPS=true`.
- File upload baru masuk disk private, bukan public.
