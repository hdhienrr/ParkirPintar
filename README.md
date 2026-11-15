# ParkirPintar

Sistem Manajemen Parkir Gedung Perkantoran dengan Karcis Digital

ParkirPintar merupakan aplikasi manajemen parkir yang dirancang untuk membantu pengelolaan area parkir gedung perkantoran. Sistem ini menyediakan pengelolaan data parkir, transaksi kendaraan masuk dan keluar, pembayaran tarif parkir, serta pencatatan transaksi melalui karcis atau struk digital.


## Anggota:
1. Dwiky Rahman Aji Putra (102062400146) - Tech Lead / Backend
2. Muhammad Hadhie Nurrahman (102062400076) - DevOps Engineer
3. Muhammad Ihsan Ramadhan (1204220101) - Container Engineer
4. Hartita Wirastri (102062400085) - QA Engineer & Tech Writer


## Tech Stack
- Backend: Laravel 13, PHP 8.3
- Database: MySQL
- Container: Docker, Docker Compose
- CI/CD: Jenkins, GitHub Webhook, Ngrok, Docker Hub
- Testing: PHPUnit (laporan JUnit XML)
- AI: ChatGPT, Claude


## Fitur
- [ ] CRUD area parkir dan kapasitas per jenis kendaraan
- [ ] Check-in kendaraan (plat nomor, jenis)
- [ ] Check-out dengan perhitungan tarif
- [ ] Laporan okupansi dan pendapatan


## Aturan Bisnis
- Tarif motor/mobil, pembulatan jam ke atas, tarif maksimal harian
- 15 menit pertama gratis
- Check-in ditolak jika area penuh atau plat masih di dalam
