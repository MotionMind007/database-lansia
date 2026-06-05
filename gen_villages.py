"""Generate SQL for all villages from the document data."""
import subprocess

# Get district id mapping
result = subprocess.run(
    ['docker', 'exec', 'lansia-papua-db', 'psql', '-U', 'lansia', '-d', 'lansia_papua', '-t', '-A', '-c',
     "SELECT id, code FROM regions WHERE type='district';"],
    capture_output=True, text=True
)

district_map = {}
for line in result.stdout.strip().split('\n'):
    if '|' in line:
        parts = line.split('|')
        district_map[parts[1]] = parts[0]

print(f"Found {len(district_map)} districts")

# All villages data from the xlsx document
# Format: code,name,district_code
villages = []

# Read from the comprehensive data embedded here
raw_data = """91.03.01.1001,Sentani Kota,91.03.01
91.03.01.1002,Dobonsolo,91.03.01
91.03.01.1003,Hinekombe,91.03.01
91.03.01.2004,Sereh,91.03.01
91.03.01.2005,Yobeh,91.03.01
91.03.01.2006,Ilfele,91.03.01
91.03.01.2011,Hobong,91.03.01
91.03.01.2017,Yahim,91.03.01
91.03.01.3007,Desa Adat Yoboi,91.03.01
91.03.01.3010,Desa Adat Heaiseai Yomo Heai,91.03.01
91.03.02.2001,Nolokla,91.03.02
91.03.02.2002,Puai,91.03.02
91.03.02.2004,Asei Besar,91.03.02
91.03.02.2006,Nendali,91.03.02
91.03.02.3003,Desa Adat Ayapo,91.03.02
91.03.02.3005,Desa Adat Kleuwblou,91.03.02
91.03.02.3007,Desa Adat Yokiwa,91.03.02
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
91.03.04.3006,Desa Adat Waibron Bano,91.03.04
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
91.03.07.1021,Tabri,91.03.07
91.03.07.2001,Gemebs,91.03.07
91.03.07.2002,Singgri,91.03.07
91.03.07.2003,Meyu,91.03.07
91.03.07.2004,Benyom,91.03.07
91.03.07.2005,Oyengsi,91.03.07
91.03.07.2006,Singgriway,91.03.07
91.03.07.2009,Imsar,91.03.07
91.03.07.2010,Kuipons,91.03.07
91.03.07.2014,Yenggu Baru,91.03.07
91.03.07.2015,Yenggu Lama,91.03.07
91.03.07.2017,Kuwase,91.03.07
91.03.07.2019,Pobaim,91.03.07
91.03.07.3018,Desa Adat Ketemung,91.03.07
91.03.08.2001,Nimbokrang,91.03.08
91.03.08.2002,Benyom Jaya I,91.03.08
91.03.08.2003,Benyom Jaya II,91.03.08
91.03.08.2004,Berab,91.03.08
91.03.08.2005,Hamonggrang,91.03.08
91.03.08.2006,Wahab,91.03.08
91.03.08.2007,Nombukrang Sari,91.03.08
91.03.08.2008,Rhepang Muaf,91.03.08
91.03.08.2009,Bunyom,91.03.08
91.03.09.2001,Beneik,91.03.09
91.03.09.2002,Ganusa,91.03.09
91.03.09.2003,Guryard,91.03.09
91.03.09.2004,Santosa,91.03.09
91.03.09.2005,Sawa Suma,91.03.09
91.03.09.2006,Nandaizi,91.03.09
91.03.10.2001,Demta,91.03.10
91.03.10.2002,Ambora,91.03.10
91.03.10.2003,Yougapsa,91.03.10
91.03.10.2004,Muris Kecil,91.03.10
91.03.10.2005,Yakore,91.03.10
91.03.10.2006,Kamdera,91.03.10
91.03.10.2007,Muaif,91.03.10
91.03.11.2001,Lapua,91.03.11
91.03.11.2002,Sebum,91.03.11
91.03.11.2003,Soskotek,91.03.11
91.03.11.2004,Yadauw,91.03.11
91.03.11.2006,Umbron,91.03.11
91.03.12.2001,Ebungfa,91.03.12
91.03.12.2002,Atabar,91.03.12
91.03.12.2004,Khameyoka,91.03.12
91.03.12.3003,Desa Adat Bobrongko,91.03.12
91.03.12.3005,Desa Adat Homfolo,91.03.12
91.03.13.2002,Doyo Lama,91.03.13
91.03.13.2003,Kwadeware,91.03.13
91.03.13.2004,Yakonde,91.03.13
91.03.13.2005,Sosiri,91.03.13
91.03.13.2006,Doyo Baru,91.03.13
91.03.13.3001,Desa Adat Dondai,91.03.13
91.03.13.3007,Desa Adat Bambar,91.03.13
91.03.14.2001,Sarmai Atas,91.03.14
91.03.14.2002,Sarmai Bawah,91.03.14
91.03.14.2003,Sanggai,91.03.14
91.03.14.2004,Yakasib,91.03.14
91.03.14.2005,Besum,91.03.14
91.03.14.2007,Imestum,91.03.14
91.03.14.2008,Karya Bumi,91.03.14
91.03.14.2009,Hanggaiy Hamong,91.03.14
91.03.14.2010,Sumbe,91.03.14
91.03.15.2001,Tabbeyan,91.03.15
91.03.15.2002,Kwarja,91.03.15
91.03.15.2003,Ongan Jaya,91.03.15
91.03.15.2004,Bumi Sahaja,91.03.15
91.03.15.2005,Nawa Mulya,91.03.15
91.03.15.2006,Nawa Mukti,91.03.15
91.03.15.2007,Taqwa Bangun,91.03.15
91.03.15.2008,Purnama Jati,91.03.15
91.03.15.3009,Desa Adat Bundru,91.03.15
91.03.16.2001,Hulu Atas,91.03.16
91.03.16.2002,Pagai,91.03.16
91.03.16.2003,Aurina,91.03.16
91.03.16.2004,Muara Nawa,91.03.16
91.03.16.2005,Kamikaru,91.03.16
91.03.16.2006,Naira,91.03.16
91.03.17.2001,Yongsu Sapari,91.03.17
91.03.17.2002,Yongsu Dosoyo,91.03.17
91.03.17.2003,Newa,91.03.17
91.03.17.3004,Desa Adat Nechive,91.03.17
91.03.18.2001,Omon,91.03.18
91.03.18.2003,Klaisu,91.03.18
91.03.18.2004,Bangai,91.03.18
91.03.18.3002,Desa Adat Iwon,91.03.18
91.03.19.2001,Maruwai,91.03.19
91.03.19.2002,Meukisi,91.03.19
91.03.19.2003,Endokisi,91.03.19
91.03.19.2005,Buseryo,91.03.19
91.03.19.2006,Senamay,91.03.19
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
91.71.05.2005,Waena Kampung,91.71.05
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

# Generate SQL
sql_lines = []
for line in raw_data.strip().split('\n'):
    parts = line.split(',', 2)
    if len(parts) != 3:
        continue
    code, name, dist_code = parts
    parent_id = district_map.get(dist_code)
    if not parent_id:
        continue
    esc_name = name.replace("'", "''")
    sql_lines.append(f"({parent_id}, '{esc_name}', 'village', '{code}', true, NOW(), NOW())")

# Write in batches of 50
with open('import_villages.sql', 'w', encoding='utf-8') as f:
    batch = []
    for i, val in enumerate(sql_lines):
        batch.append(val)
        if len(batch) == 50 or i == len(sql_lines) - 1:
            f.write("INSERT INTO regions (parent_id, name, type, code, is_active, created_at, updated_at) VALUES\n")
            f.write(",\n".join(batch))
            f.write("\nON CONFLICT (code) DO NOTHING;\n\n")
            batch = []

print(f"Generated SQL for {len(sql_lines)} villages")
