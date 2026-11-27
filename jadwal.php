<?php
session_start();
include 'config.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];

$jam_kuliah = [
    1  => '07:30 – 08:20',
    2  => '08:20 – 09:10',
    3  => '09:10 – 10:00',
    4  => '10:00 – 10:50',
    5  => '10:50 – 11:40',
    6  => '11:40 – 12:30',
    7  => '13:00 – 13:50',
    8  => '13:50 – 14:40',
    9  => '14:40 – 15:30',
    10 => '15:30 – 16:20',
    11 => '16:20 – 17:10',
    12 => '17:10 – 18:00',
];

$hari_list = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

$jadwal_data = [];
$total_matkul = 0;

$db = isset($config) ? $config : $db;

$stmt = $db->prepare("
    SELECT j.hari, j.jam_mulai, j.ruang, m.nama_matakuliah, m.dosen, m.sks
    FROM krs k
    JOIN jadwal j ON k.id_jadwal = j.id_jadwal
    JOIN matakuliah m ON j.kode_matakuliah = m.kode_matakuliah
    WHERE k.nim = ?
");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $hari = $row['hari'];
    $jam  = (int)$row['jam_mulai'];

    $jadwal_data[$hari][$jam] = [
        'matkul' => $row['nama_matakuliah'],
        'dosen'  => $row['dosen'],
        'ruang'  => $row['ruang'],
        'sks'    => (int)$row['sks']
    ];
    $total_matkul++;
}

$skip_slot = [];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Kuliah - SIAKAD</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="sidebar">

    <div class="logo">
        <img src="logo.png" alt="Logo">
        <span>Sistem Informasi Akademik</span>
    </div>

    <h4>MENU UTAMA</h4>
    <a href="dashboard_user.php">🏠 Dashboard</a>

    <?php if ($role == 'admin'): ?>
        <h4>ADMINISTRASI</h4>
        <a href="data-mahasiswa.php">👨‍🎓 Data Mahasiswa</a>
        <a href="data-matakuliah.php">📚 Data Mata Kuliah</a>
        <a href="data-user.php">👥 Data Pengguna</a>
    <?php else: ?>
        <h4>AKADEMIK</h4>
        <a href="krs_user.php">📝 Pengisian KRS</a>
        <a href="jadwal.php" class="active">📅 Jadwal Kuliah</a>
        <a href="nilai.php">📊 Nilai Akademik</a>
    <?php endif; ?>

    <a href="logout.php" class="logout">🚪 Logout</a>

</div>

<div class="main">

    <div class="navbar">
        <h2>Jadwal Kuliah</h2>

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

    <div class="welcome" style="padding: 25px 30px;">
        <h2 style="font-size: 21px; margin-bottom: 6px;">📅 Jadwal Perkuliahan Semester Ini</h2>
        <p>Tahun Akademik 2026/2027 | Pastikan hadir tepat waktu sesuai ruang yang ditentukan.</p>
    </div>

    <div class="box schedule-box">
        <div class="schedule-header">
            <h3>Matriks Jadwal Mingguan</h3>
            <span class="status">Total: <?= $total_matkul; ?> Mata Kuliah</span>
        </div>

        <div class="table-responsive">
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th style="width: 60px; text-align: center;">Jam Ke</th>
                        <th style="width: 130px; text-align: center;">Waktu</th>
                        <?php foreach ($hari_list as $hari): ?>
                            <th><?= $hari; ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jam_kuliah as $no => $waktu): ?>
                        <tr>
                            <td class="jam-no"><?= $no; ?></td>
                            <td class="jam-waktu"><?= $waktu; ?></td>

                            <?php foreach ($hari_list as $hari): ?>
                                <?php
                                if (isset($skip_slot[$hari][$no])) {
                                    continue;
                                }

                                if (isset($jadwal_data[$hari][$no])):
                                    $mk = $jadwal_data[$hari][$no];
                                    $rowspan = $mk['sks'];

                                    for ($i = 1; $i < $rowspan; $i++) {
                                        $skip_slot[$hari][$no + $i] = true;
                                    }
                                ?>
                                    <td rowspan="<?= $rowspan; ?>" class="mk-cell">
                                        <div class="mk-card">
                                            <div class="mk-title"><?= htmlspecialchars($mk['matkul']); ?></div>
                                            <div class="mk-dosen">👨‍🏫 <?= htmlspecialchars($mk['dosen']); ?></div>
                                            <div class="mk-ruang">📍 <?= htmlspecialchars($mk['ruang']); ?></div>
                                        </div>
                                    </td>
                                <?php else: ?>
                                    <td class="empty-slot">-</td>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
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