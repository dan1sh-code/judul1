<?php
/**
 * Praktikum 4: Implementasi Aplikasi Web Dinamis Menggunakan PHP
 * File: proses_tambah.php
 * Skrip pemroses pengiriman formulir tambah mahasiswa via POST.
 */

require_once __DIR__ . '/includes/functions.php';

// Pastikan request menggunakan metode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? null)) {
        set_flash_message('danger', 'Permintaan tidak valid. Silakan muat ulang halaman.');
    } else {
        $npm = $_POST['npm'] ?? '';
        $nama = $_POST['nama'] ?? '';
        $program_studi = $_POST['program_studi'] ?? '';
        $angkatan = $_POST['angkatan'] ?? '';
        $email = $_POST['email'] ?? '';

        $hasil = tambah_mahasiswa($npm, $nama, $program_studi, $angkatan, $email);

        if ($hasil['status']) {
            set_flash_message('success', $hasil['message']);
        } else {
            set_flash_message('danger', $hasil['message']);
        }
    }
} else {
    set_flash_message('danger', 'Akses tidak valid! Formulir harus dikirim melalui metode POST.');
}

// Redirect kembali ke halaman utama
// Percobaan 4-3 Langkah 6
header('Location: index.php#daftar-mahasiswa');
exit;
