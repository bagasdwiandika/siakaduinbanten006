<?php
session_start();
include "config.php";

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$username =$_SESSION['username'];
$role =$_SESSION['role'];

if ($role != 'user') {
    header("Location: dashboard_admin.php");
    exit;
}

$nim =$username;

if (isset($_POST['ambil_krs'])) {
    $id_jadwal = (int)$_POST['id_jadwal'];

    $cek = mysqli_prepare($db, "SELECT id_krs FROM krs WHERE nim = ? AND id_jadwal = ?");
    mysqli_stmt_bind_param($cek, "si", $nim,$id_jadwal);
    mysqli_stmt_execute($cek);
    mysqli_stmt_store_result($cek);

    if (mysqli_stmt_num_rows($cek) > 0) {
        echo "<script>
            alert('Jadwal ini sudah ada di KRS kamu!');
            window.location='krs_user.php';
        </script>";
        exit;
    }

    $insert = mysqli_prepare($db, "INSERT INTO krs (nim, id_jadwal) VALUES (?, ?)");
    mysqli_stmt_bind_param($insert, "si", $nim,$id_jadwal);

    if (mysqli_stmt_execute($insert)) {
        echo "<script>
            alert('Mata kuliah berhasil ditambahkan!');
            window.location='krs_user.php';
        </script>";
        exit;
    } else {
        echo "<script>
            alert('Gagal menambahkan KRS!');
            window.location='krs_user.php';
        </script>";
        exit;
    }
}

if (isset($_POST['hapus_krs'])) {
    $id_krs = (int)$_POST['id_krs'];

    $hapus = mysqli_prepare($db, "DELETE FROM krs WHERE id_krs = ? AND nim = ?");
    mysqli_stmt_bind_param($hapus, "is", $id_krs,$nim);
    mysqli_stmt_execute($hapus);

    header("Location: krs_user.php");
    exit;
}

$query_mahasiswa = mysqli_prepare($db, "SELECT nama, nim FROM mahasiswa WHERE nim = ?");
mysqli_stmt_bind_param($query_mahasiswa, "s", $nim);
mysqli_stmt_execute($query_mahasiswa);
$result_mahasiswa = mysqli_stmt_get_result($query_mahasiswa);
$mahasiswa = mysqli_fetch_assoc($result_mahasiswa);

$query_jadwal = mysqli_query($db,
    "SELECT
        jadwal.id_jadwal,
        matakuliah.kode_matakuliah,
        matakuliah.nama_matakuliah,
        matakuliah.sks,
        matakuliah.dosen,
        jadwal.hari,
        jadwal.jam_mulai,
        jadwal.ruang
    FROM jadwal
    JOIN matakuliah ON jadwal.kode_matakuliah = matakuliah.kode_matakuliah
    ORDER BY jadwal.hari, jadwal.jam_mulai"
);

$query_krs = mysqli_prepare($db,
    "SELECT
        krs.id_krs,
        matakuliah.kode_matakuliah,
        matakuliah.nama_matakuliah,
        matakuliah.sks,
        matakuliah.dosen,
        jadwal.hari,
        jadwal.jam_mulai,
        jadwal.ruang
    FROM krs
    JOIN jadwal ON krs.id_jadwal = jadwal.id_jadwal
    JOIN matakuliah ON jadwal.kode_matakuliah = matakuliah.kode_matakuliah
    WHERE krs.nim = ?
    ORDER BY jadwal.hari, jadwal.jam_mulai"
);

mysqli_stmt_bind_param($query_krs, "s", $nim);
mysqli_stmt_execute($query_krs);
$result_krs = mysqli_stmt_get_result($query_krs);

$total_sks = 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengisian KRS - SIAKAD</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="sidebar">

    <div class="logo"> <img src="logo.png">
        Sistem Informasi Akademik
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
        <a href="krs_user.php" class="active">📝 Pengisian KRS</a>
        <a href="jadwal.php">📅 Jadwal Kuliah</a>
        <a href="nilai.php">📊 Nilai Akademik</a>

    <?php endif; ?>

    <a href="logout.php" class="logout">🚪 Logout</a>

</div>

<div class="main">

    <div class="navbar">
        <h2>Pengisian KRS</h2>

        <div class="profile">
            <div class="profile-avatar">
                <?= substr(htmlspecialchars($nim), 0, 1) ?>
            </div>
            <div class="profile-info">
                <strong><?= htmlspecialchars($nim) ?></strong>
                <small><?= ucfirst(htmlspecialchars($role)) ?></small>
            </div>
        </div>
    </div>

    <div class="welcome">
        <h2>Kartu Rencana Studi (KRS)</h2>
        <p><strong>Nama:</strong> <?= htmlspecialchars($mahasiswa['nama'] ?? 'Nama tidak ditemukan') ?>  •  <strong>NIM:</strong> <?= htmlspecialchars($nim) ?></p>
    </div>

    <div class="box krs-section">
        <h3>Daftar Mata Kuliah Tersedia</h3>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Mata Kuliah</th>
                        <th>SKS</th>
                        <th>Dosen</th>
                        <th>Hari</th>
                        <th>Jam</th>
                        <th>Ruang</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($j = mysqli_fetch_assoc($query_jadwal)) : ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($j['kode_matakuliah']) ?></strong></td>
                        <td><?= htmlspecialchars($j['nama_matakuliah']) ?></td>
                        <td><?= htmlspecialchars($j['sks']) ?></td>
                        <td><?= htmlspecialchars($j['dosen']) ?></td>
                        <td><?= htmlspecialchars($j['hari']) ?></td>
                        <td><?= htmlspecialchars($j['jam_mulai']) ?></td>
                        <td><span class="mk-ruang"><?= htmlspecialchars($j['ruang']) ?></span></td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="id_jadwal" value="<?= $j['id_jadwal'] ?>">
                                <button type="submit" name="ambil_krs" class="btn-tambah">+ Ambil</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="box">
        <h3>KRS Saya</h3>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode</th>
                        <th>Mata Kuliah</th>
                        <th>SKS</th>
                        <th>Dosen</th>
                        <th>Hari</th>
                        <th>Jam</th>
                        <th>Ruang</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $no = 1;
                while ($k = mysqli_fetch_assoc($result_krs)) :
                    $total_sks += (int)$k['sks'];
                ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><strong><?= htmlspecialchars($k['kode_matakuliah']) ?></strong></td>
                        <td><?= htmlspecialchars($k['nama_matakuliah']) ?></td>
                        <td><?= htmlspecialchars($k['sks']) ?></td>
                        <td><?= htmlspecialchars($k['dosen']) ?></td>
                        <td><?= htmlspecialchars($k['hari']) ?></td>
                        <td><?= htmlspecialchars($k['jam_mulai']) ?></td>
                        <td><span class="mk-ruang"><?= htmlspecialchars($k['ruang']) ?></span></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Yakin ingin menghapus mata kuliah ini dari KRS?')">
                                <input type="hidden" name="id_krs" value="<?= $k['id_krs'] ?>">
                                <button type="submit" name="hapus_krs" class="btn-hapus">Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="total-sks">
            Total SKS yang diambil: <strong><?= $total_sks ?> SKS</strong>
        </div>
    </div>

        <p class="nilai-footer">
        Sistem Informasi Akademik
    </p>

</div>

</body>
</html>