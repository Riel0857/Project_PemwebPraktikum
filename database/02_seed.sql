BEGIN;

INSERT INTO users (name, email, password_hash, role) VALUES
('Admin PeduliKasih', 'admin@pedulikasih.test',
 '$2y$10$xk7jZLFe0M8ML/8KXGBeru9.bz/wzmGpNHyv.p9sbP4.76psYwBiS', 'admin');

INSERT INTO categories (name, slug, description) VALUES
('Pendidikan',  'pendidikan',  'Beasiswa, sarana sekolah, dan literasi anak.'),
('Kesehatan',   'kesehatan',   'Layanan klinik gratis, gizi, dan obat-obatan.'),
('Kemanusiaan', 'kemanusiaan', 'Tanggap bencana dan bantuan darurat.'),
('Lingkungan',  'lingkungan',  'Air bersih, sanitasi, dan penghijauan.');

INSERT INTO programs
(category_id, title, slug, summary, description, target_amount, location, deadline, status, is_featured, created_at)
VALUES
(1, 'Beasiswa Anak Pelosok', 'beasiswa-anak-pelosok',
 'Biaya sekolah satu tahun untuk 40 anak dari keluarga prasejahtera.',
 'Banyak anak di daerah terpencil terpaksa berhenti sekolah karena biaya. Program ini menanggung uang pangkal, seragam, buku, dan biaya transportasi selama satu tahun ajaran untuk 40 anak terpilih hasil verifikasi relawan lapangan. Setiap penerima beasiswa didampingi mentor agar tetap bertahan di sekolah.',
 50000000, 'Bandung Barat', CURRENT_DATE + 45, 'aktif', TRUE, now() - INTERVAL '40 days'),

(1, 'Renovasi Sekolah Dasar Cibeureum', 'renovasi-sd-cibeureum',
 'Perbaikan 3 ruang kelas yang atapnya bocor dan lantainya retak.',
 'SD Cibeureum memiliki tiga ruang kelas dengan atap bocor dan lantai retak sehingga kegiatan belajar terganggu saat musim hujan. Dana yang terkumpul akan dipakai untuk mengganti atap, mengecor lantai, memperbaiki jendela, dan menyediakan 60 set meja-kursi baru.',
 75000000, 'Cianjur', CURRENT_DATE + 60, 'aktif', TRUE, now() - INTERVAL '30 days'),

(2, 'Klinik Gratis Desa Sukamaju', 'klinik-gratis-desa-sukamaju',
 'Layanan pemeriksaan dan obat gratis setiap pekan untuk 300 warga.',
 'Desa Sukamaju berjarak 27 km dari puskesmas terdekat. Program ini mendirikan klinik komunitas dengan tenaga medis sukarela, menyediakan pemeriksaan rutin, obat dasar, serta penyuluhan kesehatan ibu dan anak setiap akhir pekan.',
 40000000, 'Sukamaju, Bogor', CURRENT_DATE + 30, 'aktif', TRUE, now() - INTERVAL '25 days'),

(2, 'Bantuan Gizi Balita', 'bantuan-gizi-balita',
 'Paket makanan bergizi 90 hari untuk 50 balita berisiko stunting.',
 'Hasil posyandu menunjukkan 50 balita masuk kategori risiko stunting. Program ini memberikan paket protein hewani, sayur, dan susu pertumbuhan selama 90 hari, disertai pemantauan berat badan bulanan oleh kader posyandu.',
 25000000, 'Garut', CURRENT_DATE + 20, 'aktif', FALSE, now() - INTERVAL '18 days'),

(3, 'Tanggap Bencana Banjir Bandang', 'tanggap-bencana-banjir-bandang',
 'Logistik darurat, air bersih, dan tempat pengungsian layak.',
 'Banjir bandang melanda tiga kecamatan dan membuat 700 keluarga mengungsi. Bantuan difokuskan pada kebutuhan 14 hari pertama: makanan siap saji, air minum, selimut, obat-obatan, serta penerangan untuk posko pengungsian.',
 100000000, 'Sukabumi', CURRENT_DATE + 14, 'aktif', TRUE, now() - INTERVAL '10 days'),

(4, 'Air Bersih untuk Dusun Kering', 'air-bersih-dusun-kering',
 'Sumur bor dan instalasi air untuk 120 kepala keluarga.',
 'Dusun Kering hanya memiliki satu sumber mata air yang mengering di musim kemarau. Program ini membangun sumur bor sedalam 60 meter, menara penampung, dan jaringan pipa ke 120 rumah, lalu menyerahkan pengelolaannya kepada koperasi desa.',
 60000000, 'Kupang, NTT', CURRENT_DATE + 90, 'aktif', FALSE, now() - INTERVAL '8 days');

INSERT INTO donors (name, email, phone) VALUES
('Rina Kartika',      'rina.kartika@example.com',  '081200000101'),
('Budi Santoso',      'budi.santoso@example.com',  '081200000102'),
('Siti Aminah',       'siti.aminah@example.com',   '081200000103'),
('Andi Pratama',      'andi.pratama@example.com',  '081200000104'),
('Komunitas Senja',   'senja@example.com',         '081200000105'),
('Dewi Lestari',      'dewi.lestari@example.com',  '081200000106'),
('Hendra Wijaya',     'hendra.wijaya@example.com', '081200000107'),
('Maya Putri',        'maya.putri@example.com',    '081200000108'),
('Yayasan Cahaya',    'cahaya@example.com',        '081200000109'),
('Fajar Nugroho',     'fajar.nugroho@example.com', '081200000110');

INSERT INTO donations
(code, program_id, donor_id, amount, method, message, is_anonymous, status, verified_at, verified_by, created_at)
VALUES
('DON-20251001-A1B2', 1, 1,  2500000, 'transfer_bank', 'Semoga anak-anak tetap semangat sekolah.', FALSE, 'verified', now() - INTERVAL '39 days', 1, now() - INTERVAL '40 days'),
('DON-20251002-C3D4', 1, 2,  1000000, 'qris',          'Sedikit rezeki untuk pendidikan.',          FALSE, 'verified', now() - INTERVAL '37 days', 1, now() - INTERVAL '38 days'),
('DON-20251005-E5F6', 1, 5, 15000000, 'transfer_bank', 'Dari komunitas senja untuk beasiswa.',      FALSE, 'verified', now() - INTERVAL '33 days', 1, now() - INTERVAL '34 days'),
('DON-20251008-G7H8', 2, 3,  5000000, 'transfer_bank', 'Semoga kelasnya segera diperbaiki.',        TRUE,  'verified', now() - INTERVAL '28 days', 1, now() - INTERVAL '29 days'),
('DON-20251011-I9J0', 2, 7, 12500000, 'ewallet',       'Titip untuk meja-kursi baru.',              FALSE, 'verified', now() - INTERVAL '24 days', 1, now() - INTERVAL '25 days'),
('DON-20251015-K1L2', 2, 9, 20000000, 'transfer_bank', 'Yayasan Cahaya ikut mendukung renovasi.',   FALSE, 'verified', now() - INTERVAL '20 days', 1, now() - INTERVAL '21 days'),
('DON-20251018-M3N4', 3, 4,  3000000, 'qris',          'Untuk klinik di desa kami.',                FALSE, 'verified', now() - INTERVAL '17 days', 1, now() - INTERVAL '18 days'),
('DON-20251021-O5P6', 3, 6,  7500000, 'transfer_bank', 'Semoga bermanfaat untuk warga.',            FALSE, 'verified', now() - INTERVAL '14 days', 1, now() - INTERVAL '15 days'),
('DON-20251024-Q7R8', 4, 8,  2000000, 'ewallet',       'Untuk balita-balita hebat.',                TRUE,  'verified', now() - INTERVAL '11 days', 1, now() - INTERVAL '12 days'),
('DON-20251027-S9T0', 5, 9, 25000000, 'transfer_bank', 'Bantuan awal untuk pengungsi.',             FALSE, 'verified', now() - INTERVAL '8 days',  1, now() - INTERVAL '9 days'),
('DON-20251029-U1V2', 5, 10, 5000000, 'qris',          'Semoga lekas pulih.',                       FALSE, 'verified', now() - INTERVAL '6 days',  1, now() - INTERVAL '7 days'),
('DON-20251101-W3X4', 6, 2,  4000000, 'transfer_bank', 'Air bersih untuk semua.',                   FALSE, 'verified', now() - INTERVAL '4 days',  1, now() - INTERVAL '5 days'),
('DON-20251103-Y5Z6', 6, 5,  6000000, 'transfer_bank', 'Dari komunitas senja.',                     FALSE, 'verified', now() - INTERVAL '2 days',  1, now() - INTERVAL '3 days'),
('DON-20251105-A7B8', 3, 1,  1500000, 'qris',          'Mohon dikonfirmasi ya.',                    FALSE, 'pending',  NULL, NULL, now() - INTERVAL '2 days'),
('DON-20251106-C9D0', 4, 3,  1000000, 'ewallet',       'Transfer dari rekening istri saya.',        FALSE, 'pending',  NULL, NULL, now() - INTERVAL '1 day'),
('DON-20251107-E1F2', 5, 7,  3500000, 'transfer_bank', 'Bantuan susulan untuk posko.',              TRUE,  'pending',  NULL, NULL, now() - INTERVAL '6 hours'),
('DON-20251104-G3H4', 1, 4,  500000,  'qris',          'Test transfer.',                            FALSE, 'rejected', NULL, NULL, now() - INTERVAL '3 days');

UPDATE donations
SET status      = 'paid',
    paid_at     = created_at + INTERVAL '12 minutes',
    payment_ref = '8808' || LPAD(id::text, 8, '0')
WHERE code IN ('DON-20251106-C9D0', 'DON-20251107-E1F2');

INSERT INTO contact_messages (name, email, subject, message, is_read, created_at) VALUES
('Rahmat Hidayat', 'rahmat@example.com', 'Cara menjadi relawan',
 'Selamat pagi, saya ingin mendaftar sebagai relawan lapangan di wilayah Bandung. Apakah ada jadwal briefing?', FALSE, now() - INTERVAL '2 days'),
('Lina Marlina', 'lina@example.com', 'Konfirmasi donasi',
 'Saya sudah transfer Rp1.500.000 via QRIS kemarin, namun status donasi masih menunggu. Mohon dibantu cek.', TRUE, now() - INTERVAL '5 days');

COMMIT;
