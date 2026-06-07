# Benchmark Results

Benchmark dijalankan di lokal SQLite pada mesin development. Angka production PostgreSQL/Redis bisa berbeda, tapi pola bottleneck tetap berguna untuk keputusan optimasi.

## 702 Survey Responses

```text
dashboard_fact_rows: 40,598
stats_seconds avg: 0.0020
analytics_seconds avg: 0.3992
health_seconds avg: 0.0323
```

## 3,702 Survey Responses

Sebelum index agregasi tambahan:

```text
dashboard_fact_rows: 213,884
analytics_seconds avg: 2.1195
full rebuild: 325.9 seconds
```

Setelah index agregasi tambahan:

```text
dashboard_fact_rows: 213,884
stats_seconds avg: 0.0041
analytics_seconds avg: 0.0957
health_seconds avg: 0.1411
```

Setelah full rebuild dibuat atomic transaction dan bulk insert adaptif:

```text
full rebuild: 128.77 seconds
stats_seconds avg: 0.0054
analytics_seconds avg: 0.1427
health_seconds avg: 0.2250
```

## 50,000 Survey Responses

Stress test lokal SQLite dilakukan dengan append `46,298` data dummy ke baseline `3,702` response.

```text
survey_responses: 50,000
dashboard_fact_rows: 2,886,302
dashboard_fact_responses: 50,000
cities: 9
districts: 105
villages: 999
sqlite_size_after_rebuild: ~2.51 GB
```

Waktu seed:

```text
append 46,298 demo responses: 179.2 seconds
```

Full rebuild dashboard facts:

```text
dashboard:rebuild-facts --chunk=500
result: completed, but exceeded 30 minute command timeout
estimated runtime: ~32 minutes on local SQLite
health after rebuild: OK, 50,000/50,000 responses covered
```

Cold/warm dashboard benchmark:

```text
first run, iterations=5
stats_seconds avg: 0.3917
analytics_seconds min/avg/max: 1.2500 / 47.2385 / 230.9103
health_seconds avg: 2.0067

warm run, iterations=3
stats_seconds avg: 0.0287
analytics_seconds min/avg/max: 1.2698 / 1.2980 / 1.3458
health_seconds avg: 1.9583
```

Export CSV simulation:

```text
rows: 50,000
seconds: 21.5358
csv_size: ~12.5 MB
```

Additional stress observation:

```text
direct uncached multi-filter benchmark against DashboardFactReader timed out after 10 minutes on SQLite
dashboard:health remained OK after timeout
```

## Kesimpulan Saat Ini

- Read dashboard facts sudah jauh lebih siap untuk volume besar setelah index agregasi.
- Full rebuild facts sudah lebih aman karena atomic transaction: jika gagal, perubahan bisa rollback.
- Full rebuild facts tetap tidak cocok dijalankan harian saat data membesar.
- Strategi production utama adalah incremental sync lewat queue `analytics`.
- Full rebuild sebaiknya manual/off-hours untuk migrasi data atau perubahan katalog pertanyaan.
- Pada 50k response, warm dashboard analytics masih layak untuk lokal SQLite (~1.3 detik), tetapi cold analytics dan uncached filter masih berat.
- Export 50k CSV masih bisa berjalan streaming, tetapi durasi ~21.5 detik perlu dianggap job background jika volume naik jauh atau user banyak.
- Production 150k+ sebaiknya memakai PostgreSQL + Redis, cache warm-up, dan menghindari full rebuild saat jam kerja.

## 50,000 Survey Responses on PostgreSQL + Redis

Stress test lokal berikut dijalankan memakai Docker Desktop:

```text
database: PostgreSQL 16 container
cache: Redis 7 container
queue: Redis 7 container
php redis client: predis
```

Dataset:

```text
survey_responses: 50,000
respondents: 50,000
survey_answers: 50,000
dashboard_fact_rows: 2,885,733
dashboard_fact_responses: 50,000
postgres_size_before_facts: 198 MB
postgres_size_after_facts: 1,518 MB
```

Seed:

```text
DEMO_SURVEY_COUNT=50000 DEMO_SURVEY_RESET=true
duration: 1,042.74 seconds (~17.4 minutes)
```

Full rebuild dashboard facts:

```text
dashboard:rebuild-facts --chunk=500
duration: 1,318.76 seconds (~22.0 minutes)
health after rebuild: OK, 50,000/50,000 responses covered
```

Cold/warm dashboard benchmark:

```text
first run, iterations=5
stats_seconds avg: 0.0905
analytics_seconds min/avg/max: 1.6663 / 14.9375 / 42.1184
health_seconds avg: 1.5789

warm run, iterations=3
stats_seconds avg: 0.0398
analytics_seconds min/avg/max: 1.7523 / 1.9248 / 2.0221
health_seconds avg: 1.4205
```

Export CSV simulation:

```text
rows: 50,000
seconds: 22.8722
csv_size: ~11.8 MB
```

PostgreSQL + Redis observations:

- Web runtime no longer competes with SQLite locks for cache/session/rate limiter.
- Warm dashboard analytics is stable around 2 seconds for 50k responses.
- Cold analytics is much better than SQLite but still has a heavy first run.
- Seeder is slower than SQLite because the demo seeder creates related rows one by one.
- Full rebuild facts is still too slow for business hours and should remain an off-hours/manual operation.
- Next optimization target is bulk seeding/fact rebuild strategy, not basic dashboard read path.
