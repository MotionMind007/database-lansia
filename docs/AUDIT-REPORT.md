# Laporan Audit Project Lansia Papua

Tanggal audit: 11 Juni 2026  
Auditor: Codex sebagai senior developer / security reviewer  
Scope: repository `lansia-papua` pada commit `1a50875` plus perubahan lokal yang sedang ada  
Stack: Laravel 13.8, Filament 5.6, PHP 8.4, PostgreSQL/SQLite, Redis/database queue, Vite/Tailwind

## Ringkasan Eksekutif

Project ini punya pondasi aplikasi yang cukup baik: route sudah dipisah, Form Request dipakai untuk survey, RBAC memakai Spatie Permission, file responden disimpan di private disk, export CSV sudah menetralkan formula injection, dan test suite saat ini lulus.

Namun statusnya belum siap production. Ada beberapa blocker yang perlu dibereskan sebelum go-live, terutama di jalur Docker/deployment, ownership download export, reproducible seeding, dan integritas data dashboard.

Hasil verifikasi:

| Pemeriksaan | Hasil |
|---|---|
| `php artisan test` | Lulus, 47 test / 168 assertion |
| `composer audit` | Tidak ada advisory |
| `npm audit --omit=dev` | Tidak ada vulnerability |
| Security scan worklist | 195 file/source-like rows ditutup di scan bundle |
| Critical finding | 0 |
| High / P1 finding | 4 security/production blockers |
| Medium / P2 finding | 10 database, ops, code quality findings |
| Low / P3 finding | beberapa hardening dan maintainability items |

Verdict: lanjutkan development, tetapi jangan launch production Docker path sebelum P0/P1 selesai.

## Prioritas Wajib Sebelum Production

| Prioritas | Area | Temuan | Action minimum |
|---|---|---|---|
| P1 | Security | Export download IDOR: `file=exports/...` tidak dicek kepemilikannya | Bind file export ke user atau database export record; gunakan signed URL expiring |
| P1 | Docker | `COPY . .` tanpa `.dockerignore` dapat memasukkan `.env` dan `.env.backup` ke image | Tambah `.dockerignore`; jangan build dari working tree kotor |
| P1 | Docker | `php artisan config:cache` dijalankan saat build, sebelum runtime env masuk | Pindah `config:cache` ke entrypoint/runtime container |
| P1 | Docker | Image production tidak build Vite assets, sementara `public/build` ignored | Tambah Node build stage dan copy `public/build` |
| P1 | Docker/TLS | Compose publish 443, nginx hanya listen 80 | Tambah TLS server block atau dokumentasikan external TLS dan hapus mapping 443 |
| P1 | Dependency | Seeder Excel memakai `PhpOffice\PhpSpreadsheet` tapi package tidak ada di Composer | Require `phpoffice/phpspreadsheet` atau keluarkan seeder dari default `DatabaseSeeder` |
| P1 | Database | `survey_answers` bulk JSON bisa duplicate karena `question_id` nullable tanpa unique guard | Tambah partial unique index `survey_response_id where question_id is null` |
| P1 | Dashboard | Fact reader percaya tabel facts jika ada isi sedikit saja | Tambah coverage/freshness check atau fallback bila facts belum lengkap |
| P1 | Infra | DB/Redis expose host ports; Redis tidak pakai password di compose | Jangan publish port production, atau bind private/localhost dan aktifkan auth/firewall |
| P1 | Backup | Docker path belum punya backup service yang jelas dan `pg_dump` belum eksplisit | Tambah backup job/service, off-host copy, restore drill, alert stale backup |

## Temuan Security

### S1. Export download IDOR pada queued CSV

Severity: High  
File: `routes/web.php:89`, `app/Http/Controllers/App/ExportController.php:83-94`, `app/Jobs/ExportCsvJob.php:59-107`

Route `app.export.download` boleh diakses role `administrator,surveyor`. Controller membaca query `file`, hanya memastikan path diawali `exports/`, tidak mengandung `..`, dan file ada. Setelah itu file langsung di-download dari private disk. Job export membuat nama file `exports/data_lansia_{userId}_{timestamp}.csv`, lalu mengirim path ini ke notifikasi user.

Masalahnya, download endpoint tidak memeriksa apakah path itu milik user yang sedang login. Surveyor yang mengetahui atau menebak path export user lain bisa mencoba download CSV privat tersebut.

Rekomendasi:

- Simpan export sebagai record DB: `id`, `user_id`, `path`, `expires_at`, `downloaded_at`.
- Download berdasarkan id/signed token, bukan raw path dari query string.
- Validasi `export.user_id === auth()->id()` kecuali admin policy memang mengizinkan akses lintas user.
- Tambah feature test: surveyor A tidak bisa download export surveyor B.

### S2. Docker image dapat membawa secret lokal dan cached config yang salah

Severity: High / launch blocker  
File: `deploy/docker/Dockerfile:42`, `deploy/docker/Dockerfile:52-54`, `deploy/docker/docker-compose.production.yml:16-17`

Dockerfile memakai `COPY . .` dan repo belum punya `.dockerignore`. Working tree saat audit mengandung `.env` dan `.env.backup`. File ini di-ignore Git, tetapi tidak otomatis di-ignore Docker. Setelah copy, Dockerfile menjalankan `php artisan config:cache` saat build image. Runtime env baru masuk lewat `env_file` di compose.

Risiko:

- Secret lokal bisa ikut masuk image.
- Config cache bisa membekukan nilai build-time, bukan env production.
- Image yang dipush atau dibagikan dapat membawa credential.

Rekomendasi:

- Tambah `.dockerignore` untuk `.env*`, `.git`, `node_modules`, `vendor`, storage logs/cache, `.phpunit.result.cache`, test artifacts, dan import lokal.
- Jangan jalankan `config:cache` saat build. Jalankan di entrypoint setelah env runtime tersedia.
- Tambah smoke test container: cek `APP_ENV`, `DB_HOST`, `CACHE_STORE`, `QUEUE_CONNECTION` dari dalam container.

### S3. `/health` public mengekspos status dependency

Severity: Low/Medium  
File: `routes/web.php:17`, `app/Http/Controllers/HealthController.php:21-60`

Endpoint `/health` public mengecek database, cache, dan queue, lalu mengembalikan status dependency. Ini tidak membocorkan secret, tetapi memberi sinyal recon kepada pihak luar dan dapat dipakai untuk probing health backend.

Rekomendasi:

- Pisahkan public liveness sederhana: hanya `{ "status": "ok" }`.
- Jadikan readiness detail hanya internal: IP allowlist load balancer, token, atau route internal.
- Rate-limit ringan jika endpoint terbuka ke internet.

### S4. Inactive account enumeration dan throttle gap

Severity: Low  
File: `app/Http/Controllers/Auth/LoginController.php:40`, `app/Http/Controllers/Auth/LoginController.php:58-68`

Login membedakan user tidak ditemukan / password salah dari user inactive. Cabang inactive juga tidak memanggil `LoginThrottle::recordFailedAttempt()`. Dampaknya rendah, tetapi attacker bisa menguji status akun.

Rekomendasi:

- Tampilkan pesan generik yang sama untuk inactive, unknown user, dan wrong password.
- Tetap log reason server-side.
- Tetap hitung failed attempt pada inactive login.

### Hardening Security Lain

| Area | Evidence | Catatan |
|---|---|---|
| CSP | `app/Http/Middleware/SecurityHeaders.php:39-40` | Masih mengizinkan `unsafe-inline`. Ini bukan XSS sendiri, tapi melemahkan containment. Migrasi ke nonce/hash jika UI siap. |
| RBAC super admin | `routes/web.php:66`, `routes/web.php:86`, `SurveyResponseAccess.php:13` | Helper menganggap `super admin` setara admin, beberapa route hanya izinkan `administrator`. Ini lebih ke access denial/confusion, bukan privilege escalation. |
| File upload responden | `SurveyRequest.php`, `SecureUploadStorage.php` | Kontrol cukup baik: MIME/mimetype/max size, UUID filename, private disk, path prefix check. Tambahkan test malicious file/oversize. |
| SQL injection | `SearchHelper.php`, Eloquent queries | Tidak ditemukan SQLi reportable; LIKE wildcard sudah di-escape dan query parameterized. |
| CSV injection | `ExportController::safeCsvValue()` | Sudah menetralkan prefix `=`, `+`, `-`, `@`. |

## Audit Database dan Data Integrity

### D1. Bulk survey answer bisa duplicate

Severity: High data integrity  
File: `database/migrations/2026_06_06_012329_make_question_id_nullable_in_survey_answers.php:13`, `app/Http/Controllers/App/SurveyController.php:124`, `app/Http/Controllers/App/SurveyController.php:288`

`survey_answers.question_id` dibuat nullable untuk menyimpan satu payload JSON per survey response. Insert store memakai `question_id => null`, update hanya mengambil `$response->answers->first()`. Tidak ada unique guard yang mencegah lebih dari satu row `question_id = null` untuk response yang sama.

Dampak:

- Data jawaban bisa duplicate diam-diam.
- Dashboard builder menggabungkan payload dengan `array_replace_recursive`, hasil bisa tidak deterministik.

Rekomendasi:

- PostgreSQL: tambah partial unique index `unique (survey_response_id) where question_id is null`.
- Alternatif lebih bersih: buat tabel one-to-one `survey_response_payloads`.
- Tambah test duplicate payload ditolak.

### D2. Dashboard facts bisa stale tetapi tetap dipakai

Severity: High production correctness  
File: `app/Support/DashboardFactReader.php:14`, `app/Http/Controllers/App/DashboardController.php:94-115`

Dashboard memakai `DashboardFactReader` bila fact table punya isi. Tidak ada validasi coverage bahwa semua response visible sudah punya facts. Karena facts dibangun async setelah create/update/verify, queue lag dapat membuat statistik utama dan analytics detail berbeda.

Rekomendasi:

- Track watermark atau compare `distinct survey_response_id` dengan response visible.
- Jika coverage kurang, fallback ke raw mode atau tampilkan warning internal.
- Tambah regression test: facts hanya ada untuk 1 dari 2 response, dashboard tidak boleh menampilkan analytics stale tanpa sinyal.

### D3. `region_id` menerima level wilayah apa pun

Severity: Medium  
File: `app/Http/Requests/App/SurveyRequest.php:17`, `app/Support/DashboardFactBuilder.php:189`

Validation hanya `exists:regions,id`, sedangkan code mengasumsikan `region_id` adalah village/kampung agar bisa naik ke district dan city. Crafted request bisa menyimpan province/city/district sebagai region response dan merusak denormalisasi dashboard.

Rekomendasi:

- Gunakan `Rule::exists('regions', 'id')->where('type', 'village')->where('is_active', true)`.
- Tambah test reject province/city/district.

### D4. Audit history bisa hilang saat user dihapus

Severity: Medium  
File: `database/migrations/2026_06_05_030004_create_survey_responses_table.php:63`, `database/migrations/2026_06_05_030005_create_documents_and_widgets_table.php:22`

`verification_logs.verified_by` dan `respondent_documents.uploaded_by` memakai `cascadeOnDelete`. Menghapus user dapat menghapus log verifikasi atau metadata dokumen, padahal audit trail production seharusnya bertahan.

Rekomendasi:

- Ubah ke nullable `nullOnDelete()` atau `restrictOnDelete()`.
- Tambah test bahwa delete user tidak menghapus verification log dan respondent document.

### D5. Soft delete konflik dengan unique key global

Severity: Medium  
File: `database/migrations/2026_06_09_110000_add_soft_deletes_to_critical_tables.php:11-20`, `database/migrations/2026_06_05_030004_create_survey_responses_table.php:15`, `database/migrations/2026_06_09_140000_add_nik_to_respondents_table.php:12`

`survey_responses`, `respondents`, dan `respondent_documents` sudah soft delete, tetapi `questionnaire_number` dan `nik` tetap globally unique. Soft-deleted bad record tetap menghalangi re-entry.

Rekomendasi:

- Tentukan policy. Jika reuse setelah soft delete boleh, gunakan partial unique index `where deleted_at is null` dan Laravel `withoutTrashed()`.
- Jika reuse tidak boleh, dokumentasikan restore workflow dan hindari seeder reset dengan soft delete biasa.

### D6. Index belum soft-delete-aware

Severity: Medium performance  
File: `database/migrations/2026_06_06_150000_add_production_performance_indexes.php:18-31`

Query list/verification otomatis mendapat scope `deleted_at is null`, tetapi index production belum partial by `deleted_at`.

Rekomendasi PostgreSQL:

- `(created_at desc) where deleted_at is null`
- `(status, submitted_at) where deleted_at is null`
- `(surveyor_id, created_at desc) where deleted_at is null`
- `(region_id, created_at desc) where deleted_at is null`

### D7. Query bulanan tidak sargable

Severity: Low performance  
File: `app/Http/Controllers/App/DashboardController.php:86`, `app/Support/DashboardBenchmark.php:72`

`whereMonth()` dan `whereYear()` membuat DB sulit memakai index `created_at` secara optimal.

Rekomendasi:

- Ganti dengan range half-open: `created_at >= startOfMonth` dan `< startOfNextMonth`.

## Audit Codebase, Struktur, dan Dead Code

### Yang sudah baik

- Struktur Laravel cukup bersih: `Controllers`, `Requests`, `Models`, `Support`, `Jobs`, `Filament`, `deploy`, `docs`, dan `tests` jelas.
- Security controls ditempatkan cukup rapi: `SecurityHeaders`, `LoginThrottle`, `CheckRole`, `SurveyResponseAccess`, `SecureUploadStorage`.
- Form Request dipakai untuk survey create/update.
- Banyak proses berat sudah diarahkan ke queue: export besar dan dashboard facts.
- PHPUnit punya coverage workflow inti dan regression security.

### C1. Missing dependency untuk Excel seeder

Severity: High bootstrap  
File: `database/seeders/ImportPapuaRegionExcelSeeder.php:8`, `database/seeders/DatabaseSeeder.php:20`, `composer.json:8-16`

Seeder import wilayah memakai `PhpOffice\PhpSpreadsheet\IOFactory`, tetapi `phpoffice/phpspreadsheet` tidak ada di `composer.json` maupun `composer.lock`. `composer show phpoffice/phpspreadsheet` juga tidak menemukan package. Fresh seed akan gagal.

Rekomendasi:

- Tambahkan dependency eksplisit `phpoffice/phpspreadsheet`, atau
- Keluarkan Excel seeder dari default seeding dan jadikan command/import ops terpisah.

### C2. Dead/unused Filament Region classes

Severity: Medium maintainability  
File: `app/Filament/Resources/Regions/Schemas/RegionForm.php:10`, `app/Filament/Resources/Regions/Tables/RegionsTable.php:12`, `app/Filament/Resources/Regions/RegionResource.php:56`, `app/Filament/Resources/Regions/RegionResource.php:95`

`RegionForm` dan `RegionsTable` ada, tetapi `RegionResource` mendefinisikan form/table inline. Resource lain memakai pola `Schema`/`Table` class terpisah.

Rekomendasi:

- Wire `RegionResource` ke `RegionForm::configure()` dan `RegionsTable::configure()`, atau hapus class unused.

### C3. Role policy tersebar dan tidak konsisten

Severity: Medium  
File: `SurveyResponseAccess.php:13`, `User.php:35`, `routes/web.php:66`, `routes/web.php:86`, `SurveyController.php:170`, `SurveyController.php:208`

Ada beberapa variasi role: `administrator`, `super admin`, `super_admin`, `surveyor`, `verifikator`. Sebagian helper menganggap super admin setara admin, tapi route survey/export tidak.

Rekomendasi:

- Buat konstanta/policy role terpusat, misalnya `App\Support\Roles`.
- Pakai policy/gate untuk workflow survey/export/verification.
- Tambah test super admin pada survey dan export.

### C4. `SurveyController` terlalu gemuk

Severity: Medium maintainability  
File: `app/Http/Controllers/App/SurveyController.php:40`, `app/Http/Controllers/App/SurveyController.php:199`, `app/Http/Controllers/App/SurveyController.php:389-482`

Controller mengurus orchestration, family members, respondent, files, answers mapping, status, activity log, dan dashboard job. Mapping jawaban hardcoded besar.

Rekomendasi:

- Extract `SurveySubmissionService`.
- Extract `SurveyAnswerMapper`.
- Extract `FamilyMemberSyncer`.
- Tambah unit test untuk mapper dan update/revision behavior.

### C5. Export CSV duplicate logic

Severity: Medium maintainability  
File: `app/Http/Controllers/App/ExportController.php:99-165`, `app/Jobs/ExportCsvJob.php:68-98`

Sync export dan async export punya header/row generation duplicate. Job bahkan import controller hanya untuk `safeCsvValue()`.

Rekomendasi:

- Buat service `SurveyCsvWriter` dan `CsvValueSanitizer`.
- Pakai di controller dan job.
- Test writer langsung untuk CSV formula injection.

### C6. Test suite punya beberapa source-string assertions

Severity: Medium test quality  
File: `tests/Feature/SecurityRegressionTest.php:177-261`, `tests/Unit/ExampleTest.php:7-14`

Beberapa test membaca source file dan assert string. Ini menangkap perubahan teks, bukan behavior. Placeholder `ExampleTest` masih ada.

Rekomendasi:

- Ganti dengan behavior test route/controller/form.
- Hapus placeholder tests.
- Tambah coverage untuk upload malicious file, export ownership, super admin role, stale dashboard facts, duplicate survey_answers.

### C7. Route console closures terlalu besar

Severity: Medium maintainability  
File: `routes/console.php:116`, `routes/console.php:265`, `routes/console.php:342`, `routes/console.php:437`

Command backup, production status, upload prune, dan warm cache berada sebagai closure besar di `routes/console.php`.

Rekomendasi:

- Pindah ke `app/Console/Commands` atau service class.
- Test success/failure path dengan fake disk/process boundary.

### C8. Artefak import lama dan README boilerplate

Severity: Low cleanup  
File: `README.md`, `gen_villages.py`, `import_wilayah.py`, `import_villages.sql`, `import_districts.sql`

README masih memuat boilerplate Laravel. Beberapa script/SQL import lama tidak direferensikan oleh workflow saat ini.

Rekomendasi:

- Update README menjadi app-specific: setup, env, seed, test, deploy, ops docs.
- Pindahkan import lama ke `docs/archive` atau hapus bila tidak didukung.

## Audit Deployment dan Production Readiness

### P1. Docker belum production-safe

Blocker:

- Tidak ada `.dockerignore`.
- `COPY . .` berisiko memasukkan secret/local artifacts.
- `config:cache` dijalankan saat build.
- Tidak ada Node/Vite build stage.
- Nginx volume mount `../../public` dari host, bukan artifact image, sehingga Docker image tidak immutable.
- 443 dipublish tetapi nginx hanya listen 80.

Rekomendasi high-level:

- Multi-stage build: Composer deps, Node build, final PHP-FPM image.
- Copy hanya file yang diperlukan.
- Build assets di image.
- Runtime entrypoint: migrate ops-controlled, config cache setelah env, permission check.
- Nginx harus membaca `public` dari image/shared artifact yang konsisten.

### P2. Env Docker tidak cocok dengan service networking

File: `deploy/env/production.redis.env.example:17`, `deploy/env/production.redis.env.example:33`, `deploy/docker/docker-compose.production.yml:94`, `deploy/docker/docker-compose.production.yml:114`

Env production contoh memakai `DB_HOST=127.0.0.1` dan `REDIS_HOST=127.0.0.1`, sementara compose service bernama `postgres` dan `redis`.

Rekomendasi:

- Buat `production.docker.env.example` dengan `DB_HOST=postgres`, `REDIS_HOST=redis`.
- Bedakan env bare-metal vs Docker.

### P3. Supervisor/Cron path drift dengan symlink deploy

File: `deploy/scripts/deploy.sh:15`, `deploy/cron/lansia-papua-scheduler.cron.example:1`, `deploy/supervisor/lansia-papua-default-worker.conf.example:3-4`

Deploy script memakai layout `/var/www/lansia-papua/current`, tetapi cron/supervisor contoh menjalankan dari `/var/www/lansia-papua`.

Rekomendasi:

- Ubah semua contoh ke `/var/www/lansia-papua/current`.
- Pastikan log/storage masuk ke shared symlink.

### P4. Queue monitoring belum mencakup Redis dan exports

File: `app/Support/DashboardHealthCheck.php:38-40`, `app/Support/DashboardHealthCheck.php:76-86`, `app/Jobs/ExportCsvJob.php:31`

Health check queue menghitung table `jobs` dan hanya label `analytics/default`. Export job memakai queue `exports`, dan pada Redis queue count tidak terbaca dari table DB.

Rekomendasi:

- Tambah queue `exports` ke health summary.
- Jika `QUEUE_CONNECTION=redis`, hitung Redis queue length.
- Alert terpisah untuk export stuck/failed.

### P5. CI belum memvalidasi production infrastructure

File: `.github/workflows/ci.yml:18-20`, `.github/workflows/ci.yml:98-104`

CI menjalankan lint/test/build asset di SQLite, tetapi belum build Docker image, belum smoke test Postgres/Redis, dan belum menjalankan dependency audit sebagai gate.

Rekomendasi:

- Tambah `composer audit` dan `npm audit --omit=dev`.
- Tambah Docker image build.
- Tambah compose smoke test: `/health`, `php artisan migrate --pretend` atau migration test DB, queue worker boot.

### P6. k6 scripts belum aman untuk CI/production test

File: `tests/k6/vps-authenticated-test.js:8-10`, `tests/k6/vps-authenticated-test.js:40-68`

Script k6 hardcode URL dan credential demo. Authenticated setup mengembalikan `{ loggedIn: true }`, tetapi session cookie setup tidak otomatis dipakai semua VU request seperti yang dikomentari.

Rekomendasi:

- Ambil `BASE_URL`, `LOGIN_EMAIL`, `LOGIN_PASSWORD` dari env.
- Fail fast bila credential tidak diset.
- Login per VU atau propagate cookie jar dengan benar.

### P7. Operational docs drift dari code

File: `docs/production-operations.md:215-220`, `docs/production-operations.md:343-346`, `bootstrap/app.php:22-25`, `app/Http/Controllers/App/ExportController.php:20-23`

Docs menyebut export async threshold 5.000, code default 2.000. Docs schedule production belum mencantumkan `dashboard:warm-cache` tiap 15 menit.

Rekomendasi:

- Update docs sebelum handover ops.
- Jadikan docs bagian CI lint ringan jika memungkinkan.

## Kekuatan Project yang Perlu Dipertahankan

| Area | Catatan |
|---|---|
| Auth/session | Session regenerate setelah login, logout invalidates session, CSRF web stack aktif |
| Rate limiting | Login route throttle dan custom lockout ada |
| RBAC | Role middleware plus `SurveyResponseAccess` sudah jadi pondasi bagus |
| File security | Respondent docs/photos private disk, path prefix validation, MIME/mimetype/max size |
| CSV security | Formula injection sudah ditangani |
| Audit logging | Login, export, verification, survey activity dilog |
| Database | FK hardening untuk survey response, indexes, dashboard facts, soft delete |
| Tests | Core workflow dan security regression sudah ada, test suite lulus |
| Ops docs | Backup, incident runbook, production ops, Docker/Supervisor/Cron sudah mulai lengkap |

## Rencana Perbaikan 10 Hari

### Hari 1-2: Security dan Docker blocker

1. Fix export ownership/signed download.
2. Tambah `.dockerignore`.
3. Refactor Dockerfile: remove build-time `config:cache`, tambah Node build stage.
4. Benahi TLS mapping atau dokumentasi external TLS.
5. Stop expose Postgres/Redis host port production.

### Hari 3-4: Bootstrap dan database integrity

1. Fix `phpoffice/phpspreadsheet` dependency atau ubah seeder default.
2. Tambah unique guard bulk `survey_answers`.
3. Validasi `region_id` harus village aktif.
4. Ubah cascade audit FK ke null/restrict.
5. Tambah soft-delete-aware indexes.

### Hari 5-6: Monitoring, backup, queue

1. Tambah Docker backup service/job dan restore drill.
2. Tambah queue health untuk Redis dan exports.
3. Update env examples: Docker vs bare-metal.
4. Update cron/supervisor path ke `current`.

### Hari 7-8: Test hardening

1. Test export ownership.
2. Test upload malicious/oversized/wrong MIME.
3. Test duplicate survey answer ditolak.
4. Test stale dashboard facts fallback/warning.
5. Test super admin route access.

### Hari 9-10: Refactor dan docs

1. Extract survey submission service/mapper.
2. Extract CSV writer.
3. Wire/delete unused Region Filament classes.
4. Move console closures ke command/service.
5. Update README dan production docs.

## Final Verdict

Project ini layak dilanjutkan sebagai fondasi production, tetapi belum boleh dianggap production-ready. Risiko terbesar saat ini bukan framework atau arsitektur utama, melainkan detail launch: Docker build path, ownership export, bootstrap dependency, database integrity, dan monitoring/backup.

Setelah P1 selesai dan regression test ditambah, pondasinya akan jauh lebih sehat untuk pilot production.

## Artefak Audit

Security scan bundle:

```text
C:/tmp/codex-security-scans/lansia-papua/1a50875_20260611162152
```

Report final security markdown/html berada di bundle tersebut. Report operasional utama berada di file ini.
