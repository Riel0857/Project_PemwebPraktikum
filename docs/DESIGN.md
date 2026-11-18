# DESIGN.md

Catatan arah desain PeduliKasih. Isinya dipakai untuk menilai setiap perubahan tampilan:
kalau sebuah keputusan tidak ada alasannya di sini, keputusan itu belum selesai.

## Design read

| Hal | Nilai | Alasan |
| --- | --- | --- |
| Produk | Situs donasi untuk masyarakat umum, banyak dibuka dari HP | Halaman harus cepat dibaca, bukan mengesankan |
| Energi | 2 dari 5 | Lembaga amal tidak berteriak; satu ajakan aksi per layar cukup |
| Ritme | 2 dari 5 | Komposisi bagian berganti (hero dua kolom, strip angka, grid kartu, daftar, form dua kolom), tapi tidak dramatis |
| Gerak | 1 dari 5 | Hanya transisi hover dan cincin fokus. Tidak ada animasi yang berjalan sendiri |

## Palet

| Token | Nilai | Dipakai untuk |
| --- | --- | --- |
| `--brand-700` | `#1e40af` | Footer, sidebar admin |
| `--brand` | `#1d4ed8` | Hero, tombol utama, angka penting |
| `--brand-500` | `#2563eb` | Hover, cincin fokus |
| `--brand-050` | `#eff6ff` | Latar ikon dan label |
| `--accent` | `#f59e0b` | Lihat aturan aksen di bawah |
| `--ink` / `--ink-soft` | `#1f2937` / `#374151` | Teks utama dan teks isi |
| `--muted` | `#5f6673` | Teks sekunder, minimal 4.5:1 di atas putih maupun `--bg` |
| `--bg` | `#f5f6f8` | Latar halaman |
| `--line` / `--line-strong` | `#e2e5ea` / `#cfd5dd` | Garis pemisah dan batas input |

Tidak ada gradient, glow, glassmorphism, atau orb latar di mana pun. Kalau suatu saat
butuh, alasan pemakaiannya harus ditulis dulu di sini.

### Aturan aksen

Amber hanya boleh muncul pada tiga hal:

1. Satu kata di judul hero (`dampak yang nyata.`).
2. Tombol aksi utama di hero.
3. Penanda status yang benar-benar ada di data: label "Pilihan", tahap donasi yang
   masih menunggu, pesan yang belum dibaca, dan jumlah pesan di sidebar.
4. Lambang merek (kotak kecil di kiri tulisan "PeduliKasih") di header, footer, dan
   sidebar. Satu bentuk, satu tempat per layar.

Amber tidak dipakai untuk garis dekoratif, ikon hiasan, atau latar bagian.

## Tipografi

- `Segoe UI, Tahoma, Arial, sans-serif`: font sistem. Tidak ada webfont, jadi tidak ada
  teks yang berkedip saat font selesai diunduh, dan halaman tetap ringan di HP.
- Huruf kapital hanya untuk label status dan kategori yang panjangnya satu sampai dua
  kata. Judul bagian, label form, dan label sidebar memakai huruf normal supaya enak
  dibaca.
- Monospace hanya untuk nilai yang dibaca karakter per karakter: kode donasi, nomor
  virtual account, dan kode referensi. Judul halaman tidak memakai monospace.

## Bentuk dan elevasi

- Radius: `10px` untuk kartu dan tombol, `8px` untuk panel kecil dan tautan navigasi,
  `6px` untuk input kecil. Bentuk pil (`999px`) hanya untuk chip filter, label status,
  dan bilah progres: bentuk pil berarti "ini label atau keadaan", bukan "ini tombol".
- Bayangan hanya untuk elemen yang memang bisa diklik dan perlu terangkat dari halaman
  (kartu program). Tabel, panel, dan bagian statis duduk rata dengan garis tipis.
- Cincin fokus: `2px` solid `--brand-500`, selalu terlihat. Fokus keyboard tidak boleh
  bergantung pada perubahan warna tipis.

## State

Setiap daftar dan formulir punya empat keadaan, dan tiap keadaan menyebut sebab serta
langkah berikutnya:

- Kosong: apa yang kosong dan tombol apa yang mengisinya.
- Memuat: tidak dipakai, karena halaman dirender server.
- Gagal: apa yang gagal dan apa yang bisa dicoba lagi.
- Berhasil: konfirmasi singkat lewat `flash()`.

## Kejujuran isi

- Semua angka di halaman publik diambil dari database, tidak ada angka contoh.
- Halaman pembayaran menyebut dirinya simulasi, dan tidak menampilkan hitungan waktu
  yang tidak benar-benar diberlakukan server.
- Kolom kosong di form tetap kosong atau memakai placeholder yang jelas maksudnya.
