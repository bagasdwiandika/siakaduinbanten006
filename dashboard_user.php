<?php
session_start();
include "config.php";

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];

if ($role != 'user') {
    header("Location: dashboard_admin.php");
    exit;
}

function hitung($db, $tabel) {
    $q = mysqli_query($db, "SELECT COUNT(*) AS total FROM $tabel");
    $r = $q ? mysqli_fetch_assoc($q) : null;
    return $r ? (int)$r['total'] : 0;
}

$total_matakuliah = hitung($db, 'matakuliah');
$total_krs        = hitung($db, 'krs');

date_default_timezone_set('Asia/Jakarta');
$nama_hari = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis',
              5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
$hari_ini = $nama_hari[(int)date('N')];

$kuliah_hari_ini = 0;
try {
    $stmt = mysqli_prepare($db,
        "SELECT COUNT(*) FROM krs
         JOIN jadwal ON krs.id_jadwal = jadwal.id_jadwal
         WHERE krs.nim = ? AND jadwal.hari = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ss", $username, $hari_ini);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $kuliah_hari_ini);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
    }
} catch (Exception $e) {
    $kuliah_hari_ini = 0;
}

$nama = $username;
try {
    $stmt = mysqli_prepare($db, "SELECT nama FROM mahasiswa WHERE nim = ? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $nama_db);
        if (mysqli_stmt_fetch($stmt) && $nama_db !== null && $nama_db !== '') {
            $nama = $nama_db;
        }
        mysqli_stmt_close($stmt);
    }
} catch (Exception $e) {
}

$program_studi = '-';

try {
    $stmt = mysqli_prepare($db, "SELECT program_studi FROM mahasiswa WHERE nim = ? LIMIT 1");

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $program_studi_db);

        if (mysqli_stmt_fetch($stmt) && $program_studi_db !== null && $program_studi_db !== '') {
            $program_studi = $program_studi_db;
        }

        mysqli_stmt_close($stmt);
    }
} catch (Exception $e) {

}

$jadwal_akademik = [
    ['Pengisian KRS',        '2026-08-10', '2026-08-21'],
    ['Perkuliahan Semester', '2026-08-31', '2026-12-04'],
    ['UTS',                  '2026-10-19', '2026-10-23'],
    ['UAS',                  '2026-12-07', '2026-12-18'],
];

function tgl_indo($tanggal) {
    $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
              'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $t = strtotime($tanggal);
    return date('j', $t) . ' ' . $bulan[(int)date('n', $t)] . ' ' . date('Y', $t);
}

$tahun_ini = (int)date('Y');
$bulan_ini = (int)date('n');
$ganjil    = ($bulan_ini >= 8);

if (strlen($username) >= 2 && ctype_digit(substr($username, 0, 2))) {
    $angkatan = 2000 + (int)substr($username, 0, 2);
    $semester = max(1, ($tahun_ini - $angkatan) * 2 + ($ganjil ? 1 : 0));
} else {
    $angkatan = '-';
    $semester = 1;
}

$tahun_akademik = $ganjil
    ? $tahun_ini . '/' . ($tahun_ini + 1)
    : ($tahun_ini - 1) . '/' . $tahun_ini;
$periode = $ganjil ? 'Ganjil' : 'Genap';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard SIAKAD</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="sidebar">

    <div class="logo"> <img src="logo.png">
        Sistem Informasi Akademik
    </div>

    <h4>MENU UTAMA</h4>
    <a href="dashboard_user.php" class="active">🏠 Dashboard</a>

    <?php if ($role == 'admin'): ?>

        <h4>ADMINISTRASI</h4>
        <a href="data-mahasiswa.php">👨‍🎓 Data Mahasiswa</a>
        <a href="data-matakuliah.php">📚 Data Mata Kuliah</a>
        <a href="data-user.php">👥 Data Pengguna</a>

    <?php else: ?>

        <h4>AKADEMIK</h4>
        <a href="krs_user.php">📝 Pengisian KRS</a>
        <a href="jadwal.php">📅 Jadwal Kuliah</a>
        <a href="nilai.php">📊 Nilai Akademik</a>

    <?php endif; ?>

    <a href="logout.php" class="logout">🚪 Logout</a>

</div>

<div class="main">

    <div class="navbar">

        <h2>Dashboard</h2>

        <div class="profile">

    <div class="profile-avatar">
        <?= strtoupper(substr($username, 0, 1)); ?>
    </div>

    <div class="profile-info">
        <strong><?= htmlspecialchars($username); ?></strong>
        <small><?= htmlspecialchars(ucfirst($role)); ?></small>
    </div>

</div>

    </div>

    <div class="welcome">

        <h2>
            Selamat Datang, <?= htmlspecialchars($nama); ?>!
        </h2>

        <p>Selamat datang di Sistem Informasi Akademik.</p>

        <p>
            Kelola aktivitas akademik dengan mudah melalui dashboard ini.
        </p>

    </div>

    <div class="cards">

        <div class="card">
            <div class="icon">📅</div>
            <h4>Kuliah Hari Ini (<?= $hari_ini; ?>)</h4>
            <h2><?= $kuliah_hari_ini; ?></h2>
        </div>

        <div class="card">
            <div class="icon">📚</div>
            <h4>Mata Kuliah</h4>
            <h2><?= $total_matakuliah; ?></h2>
        </div>

        <div class="card">
            <div class="icon">📝</div>
            <h4>KRS</h4>
            <h2><?= $total_krs; ?></h2>
        </div>

        <div class="card">
            <div class="icon">📊</div>
            <h4>Semester</h4>
            <h2><?= $semester; ?></h2>
        </div>

    </div>

    <div class="content">

        <div class="box">

            <h3>Informasi Akademik</h3>

            <table>

                <tr>
                    <th>Informasi</th>
                    <th>Tanggal</th>
                </tr>

                <?php foreach ($jadwal_akademik as $j): ?>
                <tr>
                    <td><?= htmlspecialchars($j[0]); ?></td>
                    <td>
                        <span class="status">
                            <?= tgl_indo($j[1]); ?> - <?= tgl_indo($j[2]); ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>

            </table>

        </div>

        <div class="box">

            <h3>Informasi Pengguna</h3>

            <p>
                <strong>Nama:</strong>
                <?= htmlspecialchars($nama); ?>
            </p>

            <br>

            <p>
                <strong>Username:</strong>
                <?= htmlspecialchars($username); ?>
            </p>

            <br>

            <p>
                <strong>Program Studi:</strong>
                <?= htmlspecialchars($program_studi); ?>
            </p>

            <br>

            <p>
                <strong>Angkatan:</strong>
                <?= htmlspecialchars($angkatan); ?>
            </p>

            <br>

            <p>
                <strong>Semester:</strong>
                <?= $semester; ?> (<?= $periode; ?>)
            </p>

            <br>

            <p>
                <strong>Tahun Akademik:</strong>
                <?= $tahun_akademik; ?>
            </p>

            <br>

            <p>
                <strong>Status:</strong> Aktif
            </p>

        </div>

    </div>

    <h3 style="margin-top:25px;">Menu Cepat</h3>

    <div class="menu-grid">

        <?php if ($role == 'admin'): ?>

            <a href="data-mahasiswa.php" class="menu-item">
                <div>👨‍🎓</div>
                Data Mahasiswa
            </a>

            <a href="data-matakuliah.php" class="menu-item">
                <div>📚</div>
                Data Mata Kuliah
            </a>

            <a href="data-user.php" class="menu-item">
                <div>👥</div>
                Data Pengguna
            </a>

        <?php else: ?>

            <a href="krs_user.php" class="menu-item">
                <div>📝</div>
                Pengisian KRS
            </a>

            <a href="jadwal.php" class="menu-item">
                <div>📅</div>
                Jadwal Kuliah
            </a>

            <a href="nilai.php" class="menu-item">
                <div>📊</div>
                Lihat Nilai
            </a>

        <?php endif; ?>

    </div>

        <p class="nilai-footer">
        Sistem Informasi Akademik
    </p>

</div>

</body>
</html>
