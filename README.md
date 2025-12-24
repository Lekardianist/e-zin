# E-zin Permission Management System
Sistem manajemen izin online berbasis web menggunakan PHP & MySQL dengan role-based access control.

# Deskripsi Proyek
E-zin adalah sistem manajemen izin online yang memungkinkan mahasiswa mengajukan permohonan izin secara digital kepada dosen dan administrasi. Sistem ini mendukung berbagai jenis izin dengan lampiran bukti dan workflow approval yang terstruktur.

# Tujuan Proyek
> Sistem manajemen izin online yang robust dan efektif

> Menyediakan kontrol penuh atas proses pengajuan dan persetujuan izin

> Antarmuka pengguna yang sederhana dan mudah digunakan

> Keamanan data dan akses berbasis role

# Fitur Sistem E-zin
<i> Untuk Mahasiswa:
> Registrasi dan profil mahasiswa

> Pengajuan permohonan izin (Sakit, Izin, Cuti, Lainnya)

> Upload bukti/lampiran (gambar, PDF, dokumen)

> Melihat status permohonan (Pending/Approved/Rejected)

> Melihat riwayat izin

> Edit/hapus permohonan yang masih pending

<i> Untuk Dosen:
> Dashboard dengan statistik mahasiswa

> Melihat daftar permohonan dari mahasiswa bimbingan

> Approve/reject permohonan izin

> Melihat detail lengkap permohonan dengan bukti

> Melihat daftar mahasiswa bimbingan

> Profil dan pengaturan dosen

<i> Untuk Admin/Staff:
> Manajemen semua user (mahasiswa, dosen, staff)

> Monitoring semua permohonan izin

> Approve/reject permohonan

> Generate laporan dan statistik

> Pengaturan sistem

# Keamanan
> Role-based Access Control: Akses berbeda untuk mahasiswa, dosen, dan admin
Password Hashing: Password dienkripsi dengan hash yang aman

> Input Sanitization: Filter input untuk mencegah SQL Injection dan XSS

> Session Management: Manajemen session yang aman

> File Upload Validation: Validasi tipe dan ukuran file upload

# Struktur Database
> Database terdiri dari beberapa tabel utama:

> users - Data pengguna (mahasiswa, dosen, admin)

> employees - Detail lengkap karyawan/mahasiswa

> permissions - Data permohonan izin

> ... dan tabel pendukung lainnya
