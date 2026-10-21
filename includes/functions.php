<?php
/**
 * Praktikum 4: Implementasi Aplikasi Web Dinamis Menggunakan PHP
 * File: includes/functions.php
 * Kumpulan fungsi modular untuk pengelolaan data mahasiswa di server.
 */

// Memulai session jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    $is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $is_https,
        'samesite' => 'Lax'
    ]);

    session_start();
}

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

function csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function validate_csrf_token($token) {
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Inisialisasi data mahasiswa pada session jika belum ada
 */
function init_mahasiswa_session() {
    if (!isset($_SESSION['daftar_mahasiswa'])) {
        $_SESSION['daftar_mahasiswa'] = [
          [ 'npm' => '230101001',
            'nama' => 'ahmad fauzi',
            'program_studi' => 'teknik_informatika',
            'angkatan' => 2023,
            'email' => 'ahmad.fauzi@example.com'
        ],
        [
            'npm' => '230101002',
            'nama' => 'sisti nurhaliza',
            'program_studi' => 'teknik informatika',
            'angkatan' => 2023,
            'email' => 'siti.nurhalizw@example.com'
        ],
        [
            'npm' => '240101015',
            'nama' => 'budi santoso',
            'program_studi' => 'teknik informasi',
            'angkatan' => 2024,
            'email' => 'budi.santosow@example.com'
        ]
        ];

    }

    foreach ($_SESSION['daftar_mahasiswa'] as $index => $mahasiswa) {
        if (!is_array($mahasiswa)) {
            continue;
        }

        if (!array_key_exists('angkatan', $mahasiswa) && array_key_exists('angkatan ', $mahasiswa)) {
            $mahasiswa['angkatan'] = $mahasiswa['angkatan '];
        }

        unset($mahasiswa['angkatan ']);
        $mahasiswa['angkatan'] ??= '';
        $_SESSION['daftar_mahasiswa'][$index] = $mahasiswa;
    }
}

/**
 * Normalisasi input teks; output harus tetap di-escape saat dirender.
 */
function sanitize($data) {
    if (!is_string($data) && !is_numeric($data)) {
        return '';
    }

    return trim((string) $data);
}

/**
 * Mengambil semua data mahasiswa dengan opsi filter pencarian
 */
function get_all_mahasiswa($keyword = '') {
    init_mahasiswa_session();
    $data = ($_SESSION['daftar_mahasiswa']);

    if (!is_string($keyword)) {
        $keyword = '';
    }

    if (empty($keyword)) {
        return $data;
    }

    $keyword = strtolower(trim($keyword));
    $filtered = [];

    foreach ($data as $mhs) {
        if (
            str_contains(strtolower($mhs['npm']), $keyword) || 
            str_contains(strtolower($mhs['nama']), $keyword) ||
            str_contains(strtolower($mhs['program_studi']), $keyword)
        ) {
            $filtered[] = $mhs;
        }
    }

    return $filtered;
}

/**
 * Menghitung total data mahasiswa yang tersimpan
 */
function count_total_mahasiswa() {
    init_mahasiswa_session();
    return count($_SESSION['daftar_mahasiswa']);
}

/**
 * Menambahkan data mahasiswa baru ke dalam session dengan validasi server-side
 */
function tambah_mahasiswa($npm, $nama, $program_studi, $angkatan, $email) {
    init_mahasiswa_session();

    // Sanitasi data
    // Percobaan 4-3 Langkah 3
    $npm = sanitize($npm);
    $nama = sanitize($nama);
    $program_studi = sanitize($program_studi);
    $angkatan = sanitize($angkatan);
    $email = sanitize($email);


    // Validasi Server-Side
   
    if (empty($npm) || empty($nama) || empty($program_studi) || empty($angkatan) || empty($email)) {
        return [
            'status' => false,
            'message' => 'Semua kolom formulir wajib diisi!'
        ];
    }

    if (!preg_match('/^[0-9]{8,12}$/', $npm)) {
        return [
            'status' => false,
            'message' => 'npm harus berupa angka 8 hingga 12 digit!'
        ];
    }

    if (!preg_match('/^[0-9]{4}$/', $angkatan)) {
        return [
            'status' => false,
            'message' => 'Tahun angkatan harus berupa angka 4 digit!'
        ];
    }

    $angkatan = (int) $angkatan;

    // Cek duplikasi npm
    foreach ($_SESSION['daftar_mahasiswa'] as $mhs) {
        if ($mhs['npm'] === $npm) {
            return [
                'status' => false,
                'message' => 'npm sudah terdaftar! Harap gunakan npm lain.'
            ];
        }
    }

    if (strlen($nama) < 3) {
        return [
            'status' => false,
            'message' => 'Nama lengkap minimal 3 karakter!'
        ];
    }

    if ($angkatan < 2018 || $angkatan > 2026) {
        return [
            'status' => false,
            'message' => 'Tahun angkatan harus berada antara 2018 hingga 2026!'
        ];
    }

    $program_studi_valid = [
        'Teknik Informatika',
        'Sistem Informasi',
        'Teknologi Informasi',
        'Rekayasa Perangkat Lunak'
    ];

    if (!in_array($program_studi, $program_studi_valid, true)) {
        return [
            'status' => false,
            'message' => 'Program studi yang dipilih tidak valid!'
        ];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [
            'status' => false,
            'message' => 'Format alamat email tidak valid!'
        ];
    }

    // Tambahkan data baru ke array session
    $_SESSION['daftar_mahasiswa'][] = [
        'npm' => $npm,
        'nama' => $nama,
        'program_studi' => $program_studi,
        'angkatan' => $angkatan,
        'email' => $email
    ];

    return [
        'status' => true,
        'message' => "Data mahasiswa {$nama} (npm: {$npm}) berhasil ditambahkan!"
    ];
}

/**
 * Menghapus data mahasiswa dari session berdasarkan npm
 */
function hapus_mahasiswa($npm) {
    init_mahasiswa_session();
    $npm = sanitize($npm);
    $found = false;
    $namaDihapus = '';

    foreach ($_SESSION['daftar_mahasiswa'] as $index => $mhs) {
        if ($mhs['npm'] === $npm) {
            $namaDihapus = $mhs['nama'];
            unset($_SESSION['daftar_mahasiswa'][$index]);
            // Re-index array
            $_SESSION['daftar_mahasiswa'] = array_values($_SESSION['daftar_mahasiswa']);
            $found = true;
            break;
        }
    }

    if ($found) {
        return [
            'status' => true,
            'message' => "Data mahasiswa {$namaDihapus} (npm: {$npm}) berhasil dihapus."
        ];
    }

    return [
        'status' => false,
        'message' => "Data mahasiswa dengan npm {$npm} tidak ditemukan."
    ];
}

/**
 * Set flash message untuk feedback notifikasi pengguna
 */
 function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
 }

 function get_flash_message () {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION ['flash_message']);
        return $flash;
    }
    return null;
 }
