# Plugin Kantong Buku v1.0.1

Dikemas dari fitur Pocket Book SLiMS yang diberikan pengguna menjadi plugin mandiri.

Perubahan utama:
- tidak perlu mengganti `admin/modules/bibliography/submenu.php`;
- tidak perlu mengganti `print_settings.php`;
- tidak perlu mengubah `printed_settings.inc.php`;
- tidak perlu import SQL manual;
- gambar `kantong.png` dibundel di dalam plugin;
- pengaturan disimpan sendiri pada tabel `setting` dengan nama `kantong_buku_plugin_settings`;
- route admin plugin tetap mempertahankan parameter `id` dan `mod`;
- kompatibel dengan PHP 8.1.

Fitur:
- pencarian nomor inventaris, nomor panggil, dan judul;
- paging 8 data per halaman;
- pilih beberapa eksemplar dan masukkan ke antrian;
- maksimum cetak dapat diatur;
- cetak kantong buku menggunakan desain `kantong.png`;
- pengaturan nama perpustakaan, institusi, jumlah kantong per baris, dan batas antrian.

Instalasi:
1. Ekstrak folder `kantong_buku` ke folder `plugins/`.
2. Aktifkan melalui System > Plugins.
3. Buka menu Bibliography > Kantong Buku.

