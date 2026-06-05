-- Insert all 105 districts
-- Kota Jayapura (id=28)
INSERT INTO regions (parent_id, name, type, code, is_active, created_at, updated_at) VALUES
(28, 'Jayapura Utara', 'district', '91.71.01', true, NOW(), NOW()),
(28, 'Jayapura Selatan', 'district', '91.71.02', true, NOW(), NOW()),
(28, 'Abepura', 'district', '91.71.03', true, NOW(), NOW()),
(28, 'Muara Tami', 'district', '91.71.04', true, NOW(), NOW()),
(28, 'Heram', 'district', '91.71.05', true, NOW(), NOW())
ON CONFLICT (code) DO NOTHING;

-- Kabupaten Jayapura (id=29)
INSERT INTO regions (parent_id, name, type, code, is_active, created_at, updated_at) VALUES
(29, 'Sentani', 'district', '91.03.01', true, NOW(), NOW()),
(29, 'Sentani Timur', 'district', '91.03.02', true, NOW(), NOW()),
(29, 'Depapre', 'district', '91.03.03', true, NOW(), NOW()),
(29, 'Sentani Barat', 'district', '91.03.04', true, NOW(), NOW()),
(29, 'Kemtuk', 'district', '91.03.05', true, NOW(), NOW()),
(29, 'Kemtuk Gresi', 'district', '91.03.06', true, NOW(), NOW()),
(29, 'Nimboran', 'district', '91.03.07', true, NOW(), NOW()),
(29, 'Nimbokrang', 'district', '91.03.08', true, NOW(), NOW()),
(29, 'Unurum Guay', 'district', '91.03.09', true, NOW(), NOW()),
(29, 'Demta', 'district', '91.03.10', true, NOW(), NOW()),
(29, 'Kaureh', 'district', '91.03.11', true, NOW(), NOW()),
(29, 'Ebungfao', 'district', '91.03.12', true, NOW(), NOW()),
(29, 'Waibu', 'district', '91.03.13', true, NOW(), NOW()),
(29, 'Nambluong', 'district', '91.03.14', true, NOW(), NOW()),
(29, 'Yapsi', 'district', '91.03.15', true, NOW(), NOW()),
(29, 'Airu', 'district', '91.03.16', true, NOW(), NOW()),
(29, 'Raveni Rara', 'district', '91.03.17', true, NOW(), NOW()),
(29, 'Gresi Selatan', 'district', '91.03.18', true, NOW(), NOW()),
(29, 'Yokari', 'district', '91.03.19', true, NOW(), NOW())
ON CONFLICT (code) DO NOTHING;

-- Kabupaten Kepulauan Yapen (id=30)
INSERT INTO regions (parent_id, name, type, code, is_active, created_at, updated_at) VALUES
(30, 'Yapen Selatan', 'district', '91.05.01', true, NOW(), NOW()),
(30, 'Yapen Barat', 'district', '91.05.02', true, NOW(), NOW()),
(30, 'Yapen Timur', 'district', '91.05.03', true, NOW(), NOW()),
(30, 'Angkaisera', 'district', '91.05.04', true, NOW(), NOW()),
(30, 'Poom', 'district', '91.05.05', true, NOW(), NOW()),
(30, 'Kosiwo', 'district', '91.05.06', true, NOW(), NOW()),
(30, 'Yapen Utara', 'district', '91.05.07', true, NOW(), NOW()),
(30, 'Raimbawi', 'district', '91.05.08', true, NOW(), NOW()),
(30, 'Teluk Ampimoi', 'district', '91.05.09', true, NOW(), NOW()),
(30, 'Kepulauan Ambai', 'district', '91.05.10', true, NOW(), NOW()),
(30, 'Wonawa', 'district', '91.05.11', true, NOW(), NOW()),
(30, 'Windesi', 'district', '91.05.12', true, NOW(), NOW()),
(30, 'Pulau Kurudu', 'district', '91.05.13', true, NOW(), NOW()),
(30, 'Pulau Yerui', 'district', '91.05.14', true, NOW(), NOW()),
(30, 'Anotaurei', 'district', '91.05.15', true, NOW(), NOW()),
(30, 'Yawakukat', 'district', '91.05.16', true, NOW(), NOW()),
(30, 'Nusawani', 'district', '91.05.17', true, NOW(), NOW())
ON CONFLICT (code) DO NOTHING;

-- Kabupaten Biak Numfor (id=31)
INSERT INTO regions (parent_id, name, type, code, is_active, created_at, updated_at) VALUES
(31, 'Biak Kota', 'district', '91.06.01', true, NOW(), NOW()),
(31, 'Biak Utara', 'district', '91.06.02', true, NOW(), NOW()),
(31, 'Biak Timur', 'district', '91.06.03', true, NOW(), NOW()),
(31, 'Numfor Barat', 'district', '91.06.04', true, NOW(), NOW()),
(31, 'Numfor Timur', 'district', '91.06.05', true, NOW(), NOW()),
(31, 'Biak Barat', 'district', '91.06.08', true, NOW(), NOW()),
(31, 'Warsa', 'district', '91.06.09', true, NOW(), NOW()),
(31, 'Padaido', 'district', '91.06.10', true, NOW(), NOW()),
(31, 'Yendidori', 'district', '91.06.11', true, NOW(), NOW()),
(31, 'Samofa', 'district', '91.06.12', true, NOW(), NOW()),
(31, 'Yawosi', 'district', '91.06.13', true, NOW(), NOW()),
(31, 'Andey', 'district', '91.06.14', true, NOW(), NOW()),
(31, 'Swandiwe', 'district', '91.06.15', true, NOW(), NOW()),
(31, 'Bruyadori', 'district', '91.06.16', true, NOW(), NOW()),
(31, 'Orkeri', 'district', '91.06.17', true, NOW(), NOW()),
(31, 'Poiru', 'district', '91.06.18', true, NOW(), NOW()),
(31, 'Aimando Padaido', 'district', '91.06.19', true, NOW(), NOW()),
(31, 'Oridek', 'district', '91.06.20', true, NOW(), NOW()),
(31, 'Bondifuar', 'district', '91.06.21', true, NOW(), NOW())
ON CONFLICT (code) DO NOTHING;

-- Kabupaten Sarmi (id=32)
INSERT INTO regions (parent_id, name, type, code, is_active, created_at, updated_at) VALUES
(32, 'Sarmi', 'district', '91.10.01', true, NOW(), NOW()),
(32, 'Tor Atas', 'district', '91.10.02', true, NOW(), NOW()),
(32, 'Pantai Barat', 'district', '91.10.03', true, NOW(), NOW()),
(32, 'Pantai Timur', 'district', '91.10.04', true, NOW(), NOW()),
(32, 'Bonggo', 'district', '91.10.05', true, NOW(), NOW()),
(32, 'Apawer Hulu', 'district', '91.10.09', true, NOW(), NOW()),
(32, 'Sarmi Selatan', 'district', '91.10.12', true, NOW(), NOW()),
(32, 'Sarmi Timur', 'district', '91.10.13', true, NOW(), NOW()),
(32, 'Pantai Timur Bagian Barat', 'district', '91.10.14', true, NOW(), NOW()),
(32, 'Bonggo Timur', 'district', '91.10.15', true, NOW(), NOW())
ON CONFLICT (code) DO NOTHING;

-- Kabupaten Keerom (id=33)
INSERT INTO regions (parent_id, name, type, code, is_active, created_at, updated_at) VALUES
(33, 'Waris', 'district', '91.11.01', true, NOW(), NOW()),
(33, 'Arso', 'district', '91.11.02', true, NOW(), NOW()),
(33, 'Senggi', 'district', '91.11.03', true, NOW(), NOW()),
(33, 'Web', 'district', '91.11.04', true, NOW(), NOW()),
(33, 'Skanto', 'district', '91.11.05', true, NOW(), NOW()),
(33, 'Arso Timur', 'district', '91.11.06', true, NOW(), NOW()),
(33, 'Towe', 'district', '91.11.07', true, NOW(), NOW()),
(33, 'Arso Barat', 'district', '91.11.08', true, NOW(), NOW()),
(33, 'Mannem', 'district', '91.11.09', true, NOW(), NOW()),
(33, 'Yaffi', 'district', '91.11.10', true, NOW(), NOW()),
(33, 'Kaisenar', 'district', '91.11.11', true, NOW(), NOW())
ON CONFLICT (code) DO NOTHING;

-- Kabupaten Waropen (id=34)
INSERT INTO regions (parent_id, name, type, code, is_active, created_at, updated_at) VALUES
(34, 'Waropen Bawah', 'district', '91.15.01', true, NOW(), NOW()),
(34, 'Masirei', 'district', '91.15.03', true, NOW(), NOW()),
(34, 'Risei Sayati', 'district', '91.15.07', true, NOW(), NOW()),
(34, 'Urei Faisei', 'district', '91.15.08', true, NOW(), NOW()),
(34, 'Inggerus', 'district', '91.15.09', true, NOW(), NOW()),
(34, 'Kirihi', 'district', '91.15.10', true, NOW(), NOW()),
(34, 'Oudate', 'district', '91.15.11', true, NOW(), NOW()),
(34, 'Wapoga', 'district', '91.15.12', true, NOW(), NOW()),
(34, 'Demba', 'district', '91.15.13', true, NOW(), NOW()),
(34, 'Wonti', 'district', '91.15.14', true, NOW(), NOW()),
(34, 'Soyoi Mambai', 'district', '91.15.15', true, NOW(), NOW())
ON CONFLICT (code) DO NOTHING;

-- Kabupaten Supiori (id=35)
INSERT INTO regions (parent_id, name, type, code, is_active, created_at, updated_at) VALUES
(35, 'Supiori Selatan', 'district', '91.19.01', true, NOW(), NOW()),
(35, 'Supiori Utara', 'district', '91.19.02', true, NOW(), NOW()),
(35, 'Supiori Timur', 'district', '91.19.03', true, NOW(), NOW()),
(35, 'Kepulauan Aruri', 'district', '91.19.04', true, NOW(), NOW()),
(35, 'Supiori Barat', 'district', '91.19.05', true, NOW(), NOW())
ON CONFLICT (code) DO NOTHING;

-- Kabupaten Mamberamo Raya (id=36)
INSERT INTO regions (parent_id, name, type, code, is_active, created_at, updated_at) VALUES
(36, 'Mamberamo Tengah', 'district', '91.20.01', true, NOW(), NOW()),
(36, 'Mamberamo Hulu', 'district', '91.20.02', true, NOW(), NOW()),
(36, 'Rufaer', 'district', '91.20.03', true, NOW(), NOW()),
(36, 'Mamberamo Tengah Timur', 'district', '91.20.04', true, NOW(), NOW()),
(36, 'Mamberamo Hilir', 'district', '91.20.05', true, NOW(), NOW()),
(36, 'Waropen Atas', 'district', '91.20.06', true, NOW(), NOW()),
(36, 'Benuki', 'district', '91.20.07', true, NOW(), NOW()),
(36, 'Sawai', 'district', '91.20.08', true, NOW(), NOW())
ON CONFLICT (code) DO NOTHING;
