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

## Kesimpulan Saat Ini

- Read dashboard facts sudah jauh lebih siap untuk volume besar setelah index agregasi.
- Full rebuild facts sudah lebih aman karena atomic transaction: jika gagal, perubahan bisa rollback.
- Full rebuild facts tetap tidak cocok dijalankan harian saat data membesar.
- Strategi production utama adalah incremental sync lewat queue `analytics`.
- Full rebuild sebaiknya manual/off-hours untuk migrasi data atau perubahan katalog pertanyaan.
