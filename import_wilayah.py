"""
Import data wilayah Provinsi Papua ke PostgreSQL.
Data source: Kepmendagri 2025 via cahyadsn/wilayah
"""
import subprocess
import json

PROVINCE_ID = 1  # Provinsi Papua (already exists)

# Data structure: code -> (name, type, parent_code)
# Kabupaten/Kota
cities = {
    "91.71": "Kota Jayapura",
    "91.03": "Kabupaten Jayapura",
    "91.05": "Kabupaten Kepulauan Yapen",
    "91.06": "Kabupaten Biak Numfor",
    "91.10": "Kabupaten Sarmi",
    "91.11": "Kabupaten Keerom",
    "91.15": "Kabupaten Waropen",
    "91.19": "Kabupaten Supiori",
    "91.20": "Kabupaten Mamberamo Raya",
}

# Distrik data from document
districts_raw = """91.03.01,Sentani,91.03
91.03.02,Sentani Timur,91.03
91.03.03,Depapre,91.03
91.03.04,Sentani Barat,91.03
91.03.05,Kemtuk,91.03
91.03.06,Kemtuk Gresi,91.03
91.03.07,Nimboran,91.03
91.03.08,Nimbokrang,91.03
91.03.09,Unurum Guay,91.03
91.03.10,Demta,91.03
91.03.11,Kaureh,91.03
91.03.12,Ebungfao,91.03
91.03.13,Waibu,91.03
91.03.14,Nambluong,91.03
91.03.15,Yapsi,91.03
91.03.16,Airu,91.03
91.03.17,Raveni Rara,91.03
91.03.18,Gresi Selatan,91.03
91.03.19,Yokari,91.03
91.05.01,Yapen Selatan,91.05
91.05.02,Yapen Barat,91.05
91.05.03,Yapen Timur,91.05
91.05.04,Angkaisera,91.05
91.05.05,Poom,91.05
91.05.06,Kosiwo,91.05
91.05.07,Yapen Utara,91.05
91.05.08,Raimbawi,91.05
91.05.09,Teluk Ampimoi,91.05
91.05.10,Kepulauan Ambai,91.05
91.05.11,Wonawa,91.05
91.05.12,Windesi,91.05
91.05.13,Pulau Kurudu,91.05
91.05.14,Pulau Yerui,91.05
91.05.15,Anotaurei,91.05
91.05.16,Yawakukat,91.05
91.05.17,Nusawani,91.05
91.06.01,Biak Kota,91.06
91.06.02,Biak Utara,91.06
91.06.03,Biak Timur,91.06
91.06.04,Numfor Barat,91.06
91.06.05,Numfor Timur,91.06
91.06.08,Biak Barat,91.06
91.06.09,Warsa,91.06
91.06.10,Padaido,91.06
91.06.11,Yendidori,91.06
91.06.12,Samofa,91.06
91.06.13,Yawosi,91.06
91.06.14,Andey,91.06
91.06.15,Swandiwe,91.06
91.06.16,Bruyadori,91.06
91.06.17,Orkeri,91.06
91.06.18,Poiru,91.06
91.06.19,Aimando Padaido,91.06
91.06.20,Oridek,91.06
91.06.21,Bondifuar,91.06
91.10.01,Sarmi,91.10
91.10.02,Tor Atas,91.10
91.10.03,Pantai Barat,91.10
91.10.04,Pantai Timur,91.10
91.10.05,Bonggo,91.10
91.10.09,Apawer Hulu,91.10
91.10.12,Sarmi Selatan,91.10
91.10.13,Sarmi Timur,91.10
91.10.14,Pantai Timur Bagian Barat,91.10
91.10.15,Bonggo Timur,91.10
91.11.01,Waris,91.11
91.11.02,Arso,91.11
91.11.03,Senggi,91.11
91.11.04,Web,91.11
91.11.05,Skanto,91.11
91.11.06,Arso Timur,91.11
91.11.07,Towe,91.11
91.11.08,Arso Barat,91.11
91.11.09,Mannem,91.11
91.11.10,Yaffi,91.11
91.11.11,Kaisenar,91.11
91.15.01,Waropen Bawah,91.15
91.15.03,Masirei,91.15
91.15.07,Risei Sayati,91.15
91.15.08,Urei Faisei,91.15
91.15.09,Inggerus,91.15
91.15.10,Kirihi,91.15
91.15.11,Oudate,91.15
91.15.12,Wapoga,91.15
91.15.13,Demba,91.15
91.15.14,Wonti,91.15
91.15.15,Soyoi Mambai,91.15
91.19.01,Supiori Selatan,91.19
91.19.02,Supiori Utara,91.19
91.19.03,Supiori Timur,91.19
91.19.04,Kepulauan Aruri,91.19
91.19.05,Supiori Barat,91.19
91.20.01,Mamberamo Tengah,91.20
91.20.02,Mamberamo Hulu,91.20
91.20.03,Rufaer,91.20
91.20.04,Mamberamo Tengah Timur,91.20
91.20.05,Mamberamo Hilir,91.20
91.20.06,Waropen Atas,91.20
91.20.07,Benuki,91.20
91.20.08,Sawai,91.20
91.71.01,Jayapura Utara,91.71
91.71.02,Jayapura Selatan,91.71
91.71.03,Abepura,91.71
91.71.04,Muara Tami,91.71
91.71.05,Heram,91.71"""

# Parse villages from the document data
villages_raw = """91.03.01.1001,Sentani Kota,91.03.01
91.03.01.1002,Dobonsolo,91.03.01
91.03.01.1003,Hinekombe,91.03.01
91.03.01.2004,Sereh,91.03.01
91.03.01.2005,Yobeh,91.03.01
91.03.01.2006,Ilfele,91.03.01
91.03.01.2011,Hobong,91.03.01
91.03.01.2017,Yahim,91.03.01
91.03.02.2001,Nolokla,91.03.02
91.03.02.2002,Puai,91.03.02
91.03.02.2004,Asei Besar,91.03.02
91.03.02.2006,Nendali,91.03.02
91.03.03.2001,Waiya,91.03.03
91.03.03.2002,Entiyebo,91.03.03
91.03.03.2003,Kendate,91.03.03
91.03.03.2004,Tablasupa,91.03.03
91.03.03.2005,Yepase,91.03.03
91.03.03.2006,Wambena,91.03.03
91.03.03.2007,Yewena,91.03.03
91.03.03.2012,Doromena,91.03.03
91.03.04.2001,Dosay,91.03.04
91.03.04.2005,Maribu,91.03.04
91.03.04.2008,Sabron Sari,91.03.04
91.03.04.2011,Sabro Yaru,91.03.04
91.03.05.2001,Sama,91.03.05
91.03.05.2002,Manda Yawan,91.03.05
91.03.05.2003,Mamda,91.03.05
91.03.05.2004,Mamei,91.03.05
91.03.05.2005,Nambom,91.03.05
91.03.05.2006,Kwansu,91.03.05
91.03.05.2007,Soaib,91.03.05
91.03.05.2008,Sabeab Kecil,91.03.05
91.03.05.2009,Sekori,91.03.05
91.03.05.2010,Skoaim,91.03.05
91.03.05.2011,Benggwin Progo,91.03.05
91.03.05.2012,Aib,91.03.05
91.03.06.1015,Hatib,91.03.06
91.03.06.2001,Domoikati,91.03.06
91.03.06.2002,Dementin,91.03.06
91.03.06.2003,Yanbra,91.03.06
91.03.06.2004,Braso,91.03.06
91.03.06.2005,Pupehabu,91.03.06
91.03.06.2006,Bring,91.03.06
91.03.06.2007,Nembu Gresi,91.03.06
91.03.06.2009,Ibub,91.03.06
91.03.06.2013,Swentab,91.03.06
91.03.06.2014,Jagrang,91.03.06
91.03.06.2016,Hyansip,91.03.06
91.71.01.1001,Gurabesi,91.71.01
91.71.01.1002,Bayangkara,91.71.01
91.71.01.1003,Trikora,91.71.01
91.71.01.1004,Imbi,91.71.01
91.71.01.1005,Tanjung Ria,91.71.01
91.71.01.1006,Mandala,91.71.01
91.71.01.1007,Angkasapura,91.71.01
91.71.01.2008,Kayo Batu,91.71.01
91.71.02.1001,Argapura,91.71.02
91.71.02.1002,Ardipura,91.71.02
91.71.02.1003,Numbay,91.71.02
91.71.02.1005,Entrop,91.71.02
91.71.02.1006,Hamadi,91.71.02
91.71.02.2007,Tahima Sorama,91.71.02
91.71.02.2008,Tobati,91.71.02
91.71.03.1002,Asano,91.71.03
91.71.03.1008,Awiyo,91.71.03
91.71.03.1010,Yobe,91.71.03
91.71.03.1011,Abepantai,91.71.03
91.71.03.1012,Kota Baru,91.71.03
91.71.03.1014,Vim,91.71.03
91.71.03.1015,Wahno,91.71.03
91.71.03.1016,Way Mhorock,91.71.03
91.71.03.2001,Nafri,91.71.03
91.71.03.2004,Enggros,91.71.03
91.71.03.2007,Koya Koso,91.71.03
91.71.04.1004,Koya Barat,91.71.04
91.71.04.1005,Koya Timur,91.71.04
91.71.04.2001,Skouw Mabo,91.71.04
91.71.04.2002,Skouw Yambe,91.71.04
91.71.04.2003,Skouw Sae,91.71.04
91.71.04.2006,Holtekamp,91.71.04
91.71.04.2007,Koya Tengah,91.71.04
91.71.04.2008,Mosso,91.71.04
91.71.05.1001,Hedam,91.71.05
91.71.05.1002,Waena,91.71.05
91.71.05.1004,Yabansai,91.71.05
91.71.05.2003,Yoka,91.71.05
91.71.05.2005,Waena,91.71.05
91.19.01.2003,Fanindi,91.19.01
91.19.01.2004,Odori,91.19.01
91.19.01.2010,Biniki,91.19.01
91.19.01.2011,Didiabolo,91.19.01
91.19.01.2015,Warbefondi,91.19.01
91.19.01.2016,Awaki,91.19.01
91.19.01.2019,Maryaidori,91.19.01
91.19.02.2003,Warsa,91.19.02
91.19.02.2008,Warbor,91.19.02
91.19.02.2009,Kobari Jaya,91.19.02
91.19.02.2010,Fanjur,91.19.02
91.19.02.2015,Puweri,91.19.02
91.19.03.2001,Yawerma,91.19.03
91.19.03.2002,Wombonda,91.19.03
91.19.03.2003,Marsram,91.19.03
91.19.03.2004,Duber,91.19.03
91.19.03.2005,Sauyas,91.19.03
91.19.03.2006,Wafor,91.19.03
91.19.03.2007,Sorendidori,91.19.03
91.19.03.2008,Waryesi,91.19.03
91.19.03.2009,Syurdori,91.19.03
91.19.03.2010,Douwbo,91.19.03
91.19.04.2001,Rayori,91.19.04
91.19.04.2002,Mbrurwandi,91.19.04
91.19.04.2003,Manggonswan,91.19.04
91.19.04.2004,Wongkeina,91.19.04
91.19.04.2005,Yamnaisu,91.19.04
91.19.04.2006,Aruri,91.19.04
91.19.04.2007,Imbirsbari,91.19.04
91.19.04.2008,Ineki,91.19.04
91.19.04.2009,Insumbrei,91.19.04
91.19.05.2001,Waryei,91.19.05
91.19.05.2002,Koiryakam,91.19.05
91.19.05.2003,Wayori,91.19.05
91.19.05.2004,Amyas,91.19.05
91.19.05.2005,Napisndi,91.19.05
91.19.05.2006,Masyai,91.19.05
91.19.05.2007,Mapia,91.19.05"""

def escape(s):
    return s.replace("'", "''")

def run_sql(sql):
    result = subprocess.run(
        ['docker', 'exec', 'lansia-papua-db', 'psql', '-U', 'lansia', '-d', 'lansia_papua', '-c', sql],
        capture_output=True, text=True
    )
    if result.returncode != 0:
        print(f"ERROR: {result.stderr[:200]}")
    return result.returncode == 0

# 1. Insert cities
print("Inserting Kabupaten/Kota...")
city_ids = {}
for code, name in cities.items():
    sql = f"INSERT INTO regions (parent_id, name, type, code, is_active, created_at, updated_at) VALUES ({PROVINCE_ID}, '{escape(name)}', 'city', '{code}', true, NOW(), NOW()) ON CONFLICT (code) DO UPDATE SET name = EXCLUDED.name RETURNING id;"
    result = subprocess.run(
        ['docker', 'exec', 'lansia-papua-db', 'psql', '-U', 'lansia', '-d', 'lansia_papua', '-t', '-A', '-c', sql],
        capture_output=True, text=True
    )
    city_id = result.stdout.strip()
    city_ids[code] = city_id
    print(f"  {name} -> id={city_id}")

# 2. Insert districts
print("\nInserting Distrik/Kecamatan...")
district_ids = {}
for line in districts_raw.strip().split('\n'):
    code, name, parent_code = line.split(',')
    parent_id = city_ids.get(parent_code, 'NULL')
    sql = f"INSERT INTO regions (parent_id, name, type, code, is_active, created_at, updated_at) VALUES ({parent_id}, '{escape(name)}', 'district', '{code}', true, NOW(), NOW()) ON CONFLICT (code) DO UPDATE SET name = EXCLUDED.name, parent_id = EXCLUDED.parent_id RETURNING id;"
    result = subprocess.run(
        ['docker', 'exec', 'lansia-papua-db', 'psql', '-U', 'lansia', '-d', 'lansia_papua', '-t', '-A', '-c', sql],
        capture_output=True, text=True
    )
    district_id = result.stdout.strip()
    district_ids[code] = district_id

print(f"  Total: {len(district_ids)} distrik")

# 3. Insert villages (sample from document - Kota Jayapura + Supiori area)
print("\nInserting Desa/Kelurahan...")
village_count = 0
for line in villages_raw.strip().split('\n'):
    parts = line.split(',')
    if len(parts) != 3:
        continue
    code, name, parent_code = parts
    parent_id = district_ids.get(parent_code, 'NULL')
    if parent_id == 'NULL' or parent_id == '':
        continue
    sql = f"INSERT INTO regions (parent_id, name, type, code, is_active, created_at, updated_at) VALUES ({parent_id}, '{escape(name)}', 'village', '{code}', true, NOW(), NOW()) ON CONFLICT (code) DO UPDATE SET name = EXCLUDED.name, parent_id = EXCLUDED.parent_id;"
    run_sql(sql)
    village_count += 1

print(f"  Total: {village_count} desa/kelurahan")
print("\nDone!")
