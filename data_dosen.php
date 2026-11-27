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
    $nip     = trim($_POST['nip']);
    $nama    = trim($_POST['nama']);
    $jabatan = trim($_POST['jabatan']);

    try {
        $cek = mysqli_prepare($db, "SELECT nip FROM dosen WHERE nip = ?");
        mysqli_stmt_bind_param($cek, "s", $nip);
        mysqli_stmt_execute($cek);
        mysqli_stmt_store_result($cek);

        if (mysqli_stmt_num_rows($cek) > 0) {
            $pesan = "<div class='alert-error'>❌ NIP <strong>" . htmlspecialchars($nip) . "</strong> sudah terdaftar!</div>";
        } else {
            $stmt = mysqli_prepare($db, "INSERT INTO dosen (nip, nama, jabatan) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sss", $nip, $nama, $jabatan);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $pesan = "<div class='alert-success'>✅ Data dosen berhasil ditambahkan!</div>";
        }
        mysqli_stmt_close($cek);
    } catch (Throwable $e) {
        $pesan = "<div class='alert-error'>❌ Gagal menambah data: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

if (isset($_POST['update'])) {
    $nip_lama = trim($_POST['nip_lama']);
    $nip      = trim($_POST['nip']);
    $nama     = trim($_POST['nama']);
    $jabatan  = trim($_POST['jabatan']);

    try {
        $stmt = mysqli_prepare($db, "UPDATE dosen SET nip = ?, nama = ?, jabatan = ? WHERE nip = ?");
        mysqli_stmt_bind_param($stmt, "ssss", $nip, $nama, $jabatan, $nip_lama);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header("Location: data_dosen.php?status=updated");
        exit;
    } catch (Throwable $e) {
        $pesan = "<div class='alert-error'>❌ Gagal memperbarui data: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

if (isset($_GET['hapus'])) {
    try {
        $nip_hapus = $_GET['hapus'];
        $stmt = mysqli_prepare($db, "DELETE FROM dosen WHERE nip = ?");
        mysqli_stmt_bind_param($stmt, "s", $nip_hapus);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header("Location: data_dosen.php?status=deleted");
        exit;
    } catch (Throwable $e) {
        $pesan = "<div class='alert-error'>❌ Gagal menghapus data: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

if (isset($_GET['status'])) {
    if ($_GET['status'] == 'updated') $pesan = "<div class='alert-success'>✅ Data dosen berhasil diperbarui!</div>";
    if ($_GET['status'] == 'deleted') $pesan = "<div class='alert-success'>✅ Data dosen berhasil dihapus!</div>";
}

$edit_data = null;
if (isset($_GET['edit'])) {
    try {
        $nip_edit = $_GET['edit'];
        $stmt = mysqli_prepare($db, "SELECT * FROM dosen WHERE nip = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $nip_edit);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $edit_data = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);
    } catch (Throwable $e) {
    }
}

$cari = trim($_GET['cari'] ?? '');
$daftar_dosen = [];
try {
    if ($cari !== '') {
        $like = "%" . $cari . "%";
        $stmt = mysqli_prepare($db, "SELECT * FROM dosen WHERE nip LIKE ? OR nama LIKE ? OR jabatan LIKE ? ORDER BY nama ASC");
        mysqli_stmt_bind_param($stmt, "sss", $like, $like, $like);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
    } else {
        $res = mysqli_query($db, "SELECT * FROM dosen ORDER BY nama ASC");
    }
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $daftar_dosen[] = $row;
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
    <title>Kelola Data Dosen - SIAKAD</title>
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
    <a href="data_mahasiswa.php">👨‍🎓 Data Mahasiswa</a>
    <a href="data_dosen.php" class="active">👨‍🏫 Data Dosen</a>
    <a href="data_matakuliah.php">📚 Data Mata Kuliah</a>

    <a href="logout.php" class="logout">🚪 Logout</a>
</div>

<div class="main">
    <div class="navbar">
        <h2>Kelola Data Dosen</h2>
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
            <h3>Daftar Dosen (Total: <?= count($daftar_dosen); ?>)</h3>

            <form method="GET" action="data_dosen.php" class="search-bar">
                <input type="text" name="cari" class="search-input" placeholder="Cari NIP, Nama, atau Jabatan..." value="<?= htmlspecialchars($cari); ?>">
                <button type="submit" class="btn btn-primary">Cari</button>
                <?php if ($cari !== ''): ?>
                    <a href="data_dosen.php" class="btn btn-secondary">Reset</a>
                <?php endif; ?>
            </form>

            <div class="table-scroll">
                <table>
                    <tr>
                        <th>NIP</th>
                        <th>Nama</th>
                        <th>Jabatan</th>
                        <th>Aksi</th>
                    </tr>
                    <?php if (count($daftar_dosen) > 0): ?>
                        <?php foreach ($daftar_dosen as $row): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row['nip']); ?></strong></td>
                            <td><?= htmlspecialchars($row['nama']); ?></td>
                            <td><?= htmlspecialchars($row['jabatan']); ?></td>
                            <td class="td-nowrap">
                                <a href="data_dosen.php?edit=<?= urlencode($row['nip']); ?>" class="btn btn-warning">Edit</a>
                                <a href="data_dosen.php?hapus=<?= urlencode($row['nip']); ?>" class="btn btn-danger" onclick="return confirm('Yakin ingin menghapus dosen <?= htmlspecialchars($row['nama']); ?>?')">Hapus</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center">Data dosen tidak ditemukan.</td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <div class="box box-form">
            <h3><?= $edit_data ? 'Edit Dosen' : 'Tambah Dosen'; ?></h3>
            <form method="POST" action="data_dosen.php">
                <?php if ($edit_data): ?>
                    <input type="hidden" name="nip_lama" value="<?= htmlspecialchars($edit_data['nip']); ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>NIP</label>
                    <input type="text" name="nip" placeholder="Contoh: 198507142020121003" required value="<?= htmlspecialchars($edit_data['nip'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Nama Lengkap & Gelar</label>
                    <input type="text" name="nama" placeholder="Contoh: Dr. Isak Iskandar, S.Pd. M.M" required value="<?= htmlspecialchars($edit_data['nama'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Jabatan</label>
                    <input type="text" name="jabatan" placeholder="Contoh: Dosen / Dekan / Kepala Lab" required value="<?= htmlspecialchars($edit_data['jabatan'] ?? ''); ?>">
                </div>

                <?php if ($edit_data): ?>
                    <button type="submit" name="update" class="btn btn-warning">Simpan</button>
                    <a href="data_dosen.php" class="btn btn-secondary">Batal</a>
                <?php else: ?>
                    <button type="submit" name="tambah" class="btn btn-primary btn-full">+ Tambah Dosen</button>
                <?php endif; ?>
            </form>
        </div>

    </div>

    <p class="nilai-footer">Sistem Informasi Akademik - Panel Admin</p>
</div>

</body>
</html>