<?php
session_start();
include "config.php";

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];
$pesan    = "";

if (isset($_POST['tambah'])) {
    $nim           = trim($_POST['nim']);
    $nama          = trim($_POST['nama']);
    $jenis_kelamin = trim($_POST['jenis_kelamin']);
    $kode_prodi    = trim($_POST['kode_prodi']);
    $program_studi = trim($_POST['program_studi']);
    $no_hp         = trim($_POST['no_hp']);
    $alamat        = trim($_POST['alamat']);
    $status        = trim($_POST['status']);

    try {
        $cek = mysqli_prepare($db, "SELECT nim FROM mahasiswa WHERE nim = ?");
        mysqli_stmt_bind_param($cek, "s", $nim);
        mysqli_stmt_execute($cek);
        mysqli_stmt_store_result($cek);

        if (mysqli_stmt_num_rows($cek) > 0) {
            $pesan = "<div class='alert-error'>❌ NIM <strong>" . htmlspecialchars($nim) . "</strong> sudah terdaftar!</div>";
        } else {
            $sql = "INSERT INTO mahasiswa (nim, nama, jenis_kelamin, kode_prodi, program_studi, no_hp, alamat, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($db, $sql);
            mysqli_stmt_bind_param($stmt, "ssssssss", $nim, $nama, $jenis_kelamin, $kode_prodi, $program_studi, $no_hp, $alamat, $status);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $pesan = "<div class='alert-success'>✅ Data mahasiswa berhasil ditambahkan!</div>";
        }
        mysqli_stmt_close($cek);
    } catch (Throwable $e) {
        $pesan = "<div class='alert-error'>❌ Gagal menambah data: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

if (isset($_POST['update'])) {
    $nim_lama      = trim($_POST['nim_lama']);
    $nim           = trim($_POST['nim']);
    $nama          = trim($_POST['nama']);
    $jenis_kelamin = trim($_POST['jenis_kelamin']);
    $kode_prodi    = trim($_POST['kode_prodi']);
    $program_studi = trim($_POST['program_studi']);
    $no_hp         = trim($_POST['no_hp']);
    $alamat        = trim($_POST['alamat']);
    $status        = trim($_POST['status']);

    try {
        $sql = "UPDATE mahasiswa SET 
                nim = ?, nama = ?, jenis_kelamin = ?, kode_prodi = ?, 
                program_studi = ?, no_hp = ?, alamat = ?, status = ? 
                WHERE nim = ?";
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, "sssssssss", $nim, $nama, $jenis_kelamin, $kode_prodi, $program_studi, $no_hp, $alamat, $status, $nim_lama);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header("Location: data_mahasiswa.php?status=updated");
        exit;
    } catch (Throwable $e) {
        $pesan = "<div class='alert-error'>❌ Gagal memperbarui data: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

if (isset($_GET['hapus'])) {
    try {
        $nim_hapus = $_GET['hapus'];
        $stmt = mysqli_prepare($db, "DELETE FROM mahasiswa WHERE nim = ?");
        mysqli_stmt_bind_param($stmt, "s", $nim_hapus);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header("Location: data_mahasiswa.php?status=deleted");
        exit;
    } catch (Throwable $e) {
        $pesan = "<div class='alert-error'>❌ Gagal menghapus data: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

if (isset($_GET['status'])) {
    if ($_GET['status'] == 'updated') $pesan = "<div class='alert-success'>✅ Data mahasiswa berhasil diperbarui!</div>";
    if ($_GET['status'] == 'deleted') $pesan = "<div class='alert-success'>✅ Data mahasiswa berhasil dihapus!</div>";
}

$edit_data = null;
if (isset($_GET['edit'])) {
    try {
        $nim_edit = $_GET['edit'];
        $stmt = mysqli_prepare($db, "SELECT * FROM mahasiswa WHERE nim = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $nim_edit);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $edit_data = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);
    } catch (Throwable $e) {
    }
}

$cari = trim($_GET['cari'] ?? '');
$daftar_mhs = [];
try {
    if ($cari !== '') {
        $like = "%" . $cari . "%";
        $stmt = mysqli_prepare($db, "SELECT * FROM mahasiswa WHERE nim LIKE ? OR nama LIKE ? OR program_studi LIKE ? OR alamat LIKE ? ORDER BY nim ASC");
        mysqli_stmt_bind_param($stmt, "ssss", $like, $like, $like, $like);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
    } else {
        $res = mysqli_query($db, "SELECT * FROM mahasiswa ORDER BY nim ASC");
    }
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $daftar_mhs[] = $row;
        }
    }
} catch (Throwable $e) {
    $pesan = "<div class='alert-error'>❌ Error membaca data: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Data Mahasiswa - SIAKAD</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="sidebar">
    <div class="logo">
        <img src="logo.png" alt="Logo">
        Sistem Informasi Akademik
    </div>

    <h4>MENU UTAMA</h4>
    <a href="dashboard_admin.php">🏠 Dashboard</a>

    <h4>MASTER DATA</h4>
    <a href="data_mahasiswa.php" class="active">👨‍🎓 Data Mahasiswa</a>
    <a href="data_dosen.php">👨‍🏫 Data Dosen</a>
    <a href="data_matakuliah.php">📚 Data Mata Kuliah</a>

    <a href="logout.php" class="logout">🚪 Logout</a>
</div>

<div class="main">
    <div class="navbar">
        <h2>Kelola Data Mahasiswa</h2>
        <div class="profile">
            <div class="profile-avatar"><?= strtoupper(substr($username, 0, 1)); ?></div>
            <div class="profile-info">
                <strong><?= htmlspecialchars($username); ?></strong>
                <small>Administrator</small>
            </div>
        </div>
    </div>

    <?= $pesan; ?>

    <div class="content-layout">

        <div class="box box-table">
            <h3>Daftar Mahasiswa (Total: <?= count($daftar_mhs); ?>)</h3>

            <form method="GET" action="data_mahasiswa.php" class="search-bar">
                <input type="text" name="cari" class="search-input" placeholder="Cari NIM, Nama, Prodi, atau Alamat..." value="<?= htmlspecialchars($cari); ?>">
                <button type="submit" class="btn btn-primary">Cari</button>
                <?php if ($cari !== ''): ?>
                    <a href="data_mahasiswa.php" class="btn btn-secondary">Reset</a>
                <?php endif; ?>
            </form>

            <div class="table-scroll">
                <table>
                    <tr>
                        <th>NIM</th>
                        <th>Nama</th>
                        <th>L/P</th>
                        <th>Prodi</th>
                        <th>No. HP</th>
                        <th>Alamat</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                    <?php if (count($daftar_mhs) > 0): ?>
                        <?php foreach ($daftar_mhs as $row): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row['nim']); ?></strong></td>
                            <td><?= htmlspecialchars($row['nama']); ?></td>
                            <td><?= htmlspecialchars($row['jenis_kelamin']); ?></td>
                            <td><?= htmlspecialchars($row['program_studi']); ?> (<?= htmlspecialchars($row['kode_prodi']); ?>)</td>
                            <td><?= htmlspecialchars($row['no_hp']); ?></td>
                            <td><?= htmlspecialchars($row['alamat']); ?></td>
                            <td><span class="status"><?= htmlspecialchars(ucfirst($row['status'])); ?></span></td>
                            <td class="td-nowrap">
                                <a href="data_mahasiswa.php?edit=<?= urlencode($row['nim']); ?>" class="btn btn-warning">Edit</a>
                                <a href="data_mahasiswa.php?hapus=<?= urlencode($row['nim']); ?>" class="btn btn-danger" onclick="return confirm('Yakin ingin menghapus mahasiswa <?= htmlspecialchars($row['nama']); ?>?')">Hapus</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center">Data mahasiswa tidak ditemukan.</td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <div class="box box-form">
            <h3><?= $edit_data ? 'Edit Mahasiswa' : 'Tambah Mahasiswa'; ?></h3>
            <form method="POST" action="data_mahasiswa.php">
                <?php if ($edit_data): ?>
                    <input type="hidden" name="nim_lama" value="<?= htmlspecialchars($edit_data['nim']); ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>NIM</label>
                    <input type="text" name="nim" placeholder="Contoh: 251603011" required value="<?= htmlspecialchars($edit_data['nim'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama" placeholder="Nama lengkap" required value="<?= htmlspecialchars($edit_data['nama'] ?? ''); ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Jenis Kelamin</label>
                        <select name="jenis_kelamin" required>
                            <option value="Laki-laki" <?= (isset($edit_data['jenis_kelamin']) && $edit_data['jenis_kelamin'] == 'Laki-laki') ? 'selected' : ''; ?>>Laki-laki</option>
                            <option value="Perempuan" <?= (isset($edit_data['jenis_kelamin']) && $edit_data['jenis_kelamin'] == 'Perempuan') ? 'selected' : ''; ?>>Perempuan</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" required>
                            <option value="aktif" <?= (isset($edit_data['status']) && strtolower($edit_data['status']) == 'aktif') ? 'selected' : ''; ?>>Aktif</option>
                            <option value="cuti" <?= (isset($edit_data['status']) && strtolower($edit_data['status']) == 'cuti') ? 'selected' : ''; ?>>Cuti</option>
                            <option value="nonaktif" <?= (isset($edit_data['status']) && strtolower($edit_data['status']) == 'nonaktif') ? 'selected' : ''; ?>>Nonaktif</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Kode Prodi</label>
                        <input type="text" name="kode_prodi" placeholder="INF" required value="<?= htmlspecialchars($edit_data['kode_prodi'] ?? 'INF'); ?>">
                    </div>

                    <div class="form-group">
                        <label>Program Studi</label>
                        <input type="text" name="program_studi" placeholder="Informatika" required value="<?= htmlspecialchars($edit_data['program_studi'] ?? 'Informatika'); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>No. HP</label>
                    <input type="text" name="no_hp" placeholder="081251603011" required value="<?= htmlspecialchars($edit_data['no_hp'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Alamat</label>
                    <input type="text" name="alamat" placeholder="Contoh: Serang" required value="<?= htmlspecialchars($edit_data['alamat'] ?? ''); ?>">
                </div>

                <?php if ($edit_data): ?>
                    <button type="submit" name="update" class="btn btn-warning">Simpan</button>
                    <a href="data_mahasiswa.php" class="btn btn-secondary">Batal</a>
                <?php else: ?>
                    <button type="submit" name="tambah" class="btn btn-primary btn-full">+ Tambah Mahasiswa</button>
                <?php endif; ?>
            </form>
        </div>

    </div>

    <p class="nilai-footer">Sistem Informasi Akademik - Panel Admin</p>
</div>

</body>
</html>