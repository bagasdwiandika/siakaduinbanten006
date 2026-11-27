<?php
session_start();
include "config.php";

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];

function hitung($db, $tabel, $kondisi = "") {
    try {
        $sql = "SELECT COUNT(*) AS total FROM $tabel" . ($kondisi ? " WHERE $kondisi" : "");
        $q = mysqli_query($db, $sql);
        $r = $q ? mysqli_fetch_assoc($q) : null;
        return $r ? (int)$r['total'] : 0;
    } catch (Exception $e) {
        return 0;
    }
}

$total_mahasiswa  = hitung($db, 'mahasiswa');
$total_dosen      = hitung($db, 'dosen');
$total_matakuliah = hitung($db, 'matakuliah');

$mhs_belum_krs = 0;
try {
    $q_belum = mysqli_query($db, "SELECT COUNT(*) AS total FROM mahasiswa WHERE nim NOT IN (SELECT DISTINCT nim FROM krs WHERE nim IS NOT NULL)");
    $r_belum = $q_belum ? mysqli_fetch_assoc($q_belum) : null;
    $mhs_belum_krs = $r_belum ? (int)$r_belum['total'] : 0;
} catch (Exception $e) {
    $mhs_belum_krs = 0;
}

date_default_timezone_set('Asia/Jakarta');
$tahun_ini = (int)date('Y');
$bulan_ini = (int)date('n');
$ganjil    = ($bulan_ini >= 8);
$tahun_akademik = $ganjil ? $tahun_ini . '/' . ($tahun_ini + 1) : ($tahun_ini - 1) . '/' . $tahun_ini;
$periode   = $ganjil ? 'Ganjil' : 'Genap';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - SIAKAD</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="sidebar">
    <div class="logo">
        <img src="logo.png" alt="Logo">
        Sistem Informasi Akademik
    </div>

    <h4>MENU UTAMA</h4>
    <a href="dashboard_admin.php" class="active">🏠 Dashboard</a>

    <h4>MASTER DATA</h4>
    <a href="data_mahasiswa.php">👨‍🎓 Data Mahasiswa</a>
    <a href="data_dosen.php">👨‍🏫 Data Dosen</a>
    <a href="data_matakuliah.php">📚 Data Mata Kuliah</a>

    <a href="logout.php" class="logout">🚪 Logout</a>
</div>

<div class="main">
    <div class="navbar">
        <h2>Dashboard Administrator</h2>
        <div class="profile">
            <div class="profile-avatar"><?= strtoupper(substr($username, 0, 1)); ?></div>
            <div class="profile-info">
                <strong><?= htmlspecialchars($username); ?></strong>
                <small>Administrator</small>
            </div>
        </div>
    </div>

    <div class="welcome">
        <h2>Selamat Datang, <?= htmlspecialchars($username); ?>!</h2>
        <p>Panel Kontrol Administrator Sistem Informasi Akademik.</p>
    </div>

    <div class="cards">
        <div class="card">
            <div class="icon">👨‍🎓</div>
            <h4>Total Mahasiswa</h4>
            <h2><?= $total_mahasiswa; ?></h2>
        </div>

        <div class="card">
            <div class="icon">👨‍🏫</div>
            <h4>Total Dosen</h4>
            <h2><?= $total_dosen; ?></h2>
        </div>

        <div class="card">
            <div class="icon">📚</div>
            <h4>Mata Kuliah</h4>
            <h2><?= $total_matakuliah; ?></h2>
        </div>

        <div class="card">
            <div class="icon">⚠️</div>
            <h4>Mahasiswa Belum Isi KRS</h4>
            <h2><?= $mhs_belum_krs; ?></h2>
        </div>
    </div>

    <div class="content">
        <div class="box">
            <h3>Informasi Sistem</h3>
            <p><strong>Admin Aktif:</strong> <?= htmlspecialchars($username); ?></p><br>
            <p><strong>Periode Aktif:</strong> Semester <?= $periode; ?></p><br>
            <p><strong>Tahun Akademik:</strong> <?= $tahun_akademik; ?></p><br>
            <p><strong>Tanggal Server:</strong> <?= date('d/m/Y'); ?></p>
        </div>

        <div class="box">
            <h3>Menu Cepat Pengelolaan</h3>
            <div class="menu-grid">
                <a href="data_mahasiswa.php" class="menu-item"><div>👨‍🎓</div>Data Mahasiswa</a>
                <a href="data_dosen.php" class="menu-item"><div>👨‍🏫</div>Data Dosen</a>
                <a href="data_matakuliah.php" class="menu-item"><div>📚</div>Mata Kuliah</a>
            </div>
        </div>
    </div>

    <p class="nilai-footer">Sistem Informasi Akademik - Panel Admin</p>
</div>

</body>
</html>