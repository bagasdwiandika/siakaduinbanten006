<?php
session_start();
include "config.php";

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];
$role = $_SESSION['role'] ?? '';

if ($role !== 'user') {
    header("Location: dashboard_admin.php");
    exit;
}

$nim = $_SESSION['nim'] ?? $username;

$stmt_mahasiswa = mysqli_prepare(
    $db,
    "SELECT nim, nama, kode_prodi
     FROM mahasiswa
     WHERE nim = ?"
);

mysqli_stmt_bind_param($stmt_mahasiswa, "s", $nim);
mysqli_stmt_execute($stmt_mahasiswa);

$result_mahasiswa = mysqli_stmt_get_result($stmt_mahasiswa);
$mahasiswa = mysqli_fetch_assoc($result_mahasiswa);

$stmt_nilai = mysqli_prepare(
    $db,
    "SELECT
        nilai.id_nilai,
        nilai.nim,
        nilai.id_jadwal,
        nilai.nilai_angka,
        nilai.nilai_huruf,
        matakuliah.kode_matakuliah,
        matakuliah.nama_matakuliah,
        matakuliah.sks,
        matakuliah.dosen,
        jadwal.hari,
        jadwal.jam_mulai,
        jadwal.ruang
    FROM nilai
    JOIN jadwal
        ON nilai.id_jadwal = jadwal.id_jadwal
    JOIN matakuliah
        ON jadwal.kode_matakuliah = matakuliah.kode_matakuliah
    WHERE nilai.nim = ?
    ORDER BY matakuliah.nama_matakuliah ASC"
);

mysqli_stmt_bind_param($stmt_nilai, "s", $nim);
mysqli_stmt_execute($stmt_nilai);

$result_nilai = mysqli_stmt_get_result($stmt_nilai);

function konversiNilai($angka)
{
    if ($angka >= 85) {
        return "A";
    } elseif ($angka >= 80) {
        return "A-";
    } elseif ($angka >= 75) {
        return "B+";
    } elseif ($angka >= 70) {
        return "B";
    } elseif ($angka >= 65) {
        return "B-";
    } elseif ($angka >= 60) {
        return "C+";
    } elseif ($angka >= 55) {
        return "C";
    } elseif ($angka >= 50) {
        return "D";
    } else {
        return "E";
    }
}

function getBobotNilai($huruf)
{
    $bobot = [
        'A'  => 4.00,
        'A-' => 3.75,
        'B+' => 3.50,
        'B'  => 3.00,
        'B-' => 2.75,
        'C+' => 2.50,
        'C'  => 2.00,
        'D'  => 1.00,
        'E'  => 0.00
    ];

    return $bobot[strtoupper(trim($huruf))] ?? 0.00;
}

$total_nilai = 0;
$total_sks = 0;
$total_mutu = 0;
$jumlah_mk = 0;

$data_nilai = [];

while ($row = mysqli_fetch_assoc($result_nilai)) {
    $angka = (float) $row['nilai_angka'];
    $sks   = (int) $row['sks'];

    $huruf = !empty($row['nilai_huruf'])
        ? $row['nilai_huruf']
        : konversiNilai($angka);

    $bobot = getBobotNilai($huruf);
    $mutu  = $bobot * $sks;

    $row['nilai_huruf_final'] = $huruf;
    $row['bobot'] = $bobot;
    $row['mutu']  = $mutu;

    $data_nilai[] = $row;

    $total_nilai += $angka;
    $total_sks   += $sks;
    $total_mutu  += $mutu;
    $jumlah_mk++;
}

$rata_rata = $jumlah_mk > 0 ? ($total_nilai / $jumlah_mk) : 0;
$ip_semester = $total_sks > 0 ? ($total_mutu / $total_sks) : 0;

$nama_user = $mahasiswa['nama'] ?? $username;
$inisial   = strtoupper(substr($nama_user, 0, 1));
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nilai Akademik - SIAKAD</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="sidebar">

    <div class="logo">
        <img src="logo.png" alt="Logo SIAKAD">
        Sistem Informasi Akademik
    </div>

    <h4>MENU UTAMA</h4>
    <a href="dashboard_user.php">🏠 Dashboard</a>

    <h4>AKADEMIK</h4>
    <a href="krs_user.php">📝 Pengisian KRS</a>
    <a href="jadwal.php">📅 Jadwal Kuliah</a>
    <a href="nilai.php" class="active">📊 Nilai Akademik</a>

    <a href="logout.php" class="logout">🚪 Logout</a>

</div>

<div class="main">

    <div class="navbar">

        <h2>Nilai Akademik</h2>

        <div class="profile">
            <div class="profile-avatar">
                <?= htmlspecialchars($inisial); ?>
            </div>

            <div class="profile-info">
                <strong><?= htmlspecialchars($nama_user); ?></strong>
                <small><?= htmlspecialchars(ucfirst($role)); ?></small>
            </div>
        </div>

    </div>

    <div class="welcome">
        <h2>Nilai Akademik 📊</h2>
        <p>
            Berikut adalah hasil nilai mata kuliah yang telah kamu peroleh.
        </p>
    </div>

    <div class="nilai-card">

        <h3>Informasi Mahasiswa</h3>

        <div class="nilai-profil">

            <div class="nilai-profil-item">
                <span>Nama Mahasiswa</span>
                <strong>
                    <?= htmlspecialchars($mahasiswa['nama'] ?? 'Nama tidak ditemukan') ?>
                </strong>
            </div>

            <div class="nilai-profil-item">
                <span>NIM</span>
                <strong>
                    <?= htmlspecialchars($nim) ?>
                </strong>
            </div>

            <div class="nilai-profil-item">
                <span>Kode Program Studi</span>
                <strong>
                    <?= htmlspecialchars($mahasiswa['kode_prodi'] ?? '-') ?>
                </strong>
            </div>

            <div class="nilai-profil-item">
                <span>Total Mata Kuliah</span>
                <strong>
                    <?= $jumlah_mk ?> Mata Kuliah
                </strong>
            </div>

        </div>

    </div>

    <div class="nilai-summary">

        <div class="nilai-summary-card">
            <span>Indeks Prestasi (IP)</span>
            <strong>
                <?= number_format($ip_semester, 2) ?>
            </strong>
        </div>

        <div class="nilai-summary-card">
            <span>Rata-rata Nilai</span>
            <strong>
                <?= number_format($rata_rata, 2) ?>
            </strong>
        </div>

        <div class="nilai-summary-card">
            <span>Total SKS</span>
            <strong>
                <?= $total_sks ?> SKS
            </strong>
        </div>

    </div>

    <div class="nilai-card nilai-section">

        <h3>Daftar Nilai Mata Kuliah</h3>

        <div class="nilai-table-container">

            <table class="nilai-table">

                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode MK</th>
                        <th>Mata Kuliah</th>
                        <th>SKS</th>
                        <th>Dosen</th>
                        <th>Nilai Angka</th>
                        <th>Nilai Huruf</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (count($data_nilai) > 0): ?>

                        <?php foreach ($data_nilai as $index => $data): ?>

                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($data['kode_matakuliah']) ?></td>
                                <td><?= htmlspecialchars($data['nama_matakuliah']) ?></td>
                                <td><?= htmlspecialchars($data['sks']) ?></td>
                                <td><?= htmlspecialchars($data['dosen']) ?></td>
                                <td><?= number_format((float) $data['nilai_angka'], 2) ?></td>
                                <td>
                                    <span class="badge-nilai">
                                        <?= htmlspecialchars($data['nilai_huruf_final']) ?>
                                    </span>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="7">
                                <div class="nilai-empty">
                                    <div class="icon">📚</div>
                                    <strong>Nilai Belum Tersedia</strong>
                                    <p>
                                        Belum ada nilai mata kuliah yang dimasukkan oleh admin.
                                    </p>
                                </div>
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

    <p class="nilai-footer">
        Sistem Informasi Akademik
    </p>

</div>

</body>

</html>