<?php
session_start();
include "config.php";

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];
$pesan    = "";

// 1. TAMBAH MATA KULIAH
if (isset($_POST['tambah'])) {
    $kode_matakuliah = trim($_POST['kode_matakuliah']);
    $nama_matakuliah = trim($_POST['nama_matakuliah']);
    $sks             = (int)$_POST['sks'];
    $dosen           = trim($_POST['dosen']);

    try {
        $cek = mysqli_prepare($db, "SELECT kode_matakuliah FROM matakuliah WHERE kode_matakuliah = ?");
        mysqli_stmt_bind_param($cek, "s", $kode_matakuliah);
        mysqli_stmt_execute($cek);
        mysqli_stmt_store_result($cek);

        if (mysqli_stmt_num_rows($cek) > 0) {
            $pesan = "<div class='alert-error'>❌ Kode Mata Kuliah <strong>" . htmlspecialchars($kode_matakuliah) . "</strong> sudah terdaftar!</div>";
        } else {
            $stmt = mysqli_prepare($db, "INSERT INTO matakuliah (kode_matakuliah, nama_matakuliah, sks, dosen) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssis", $kode_matakuliah, $nama_matakuliah, $sks, $dosen);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $pesan = "<div class='alert-success'>✅ Mata kuliah berhasil ditambahkan!</div>";
        }
        mysqli_stmt_close($cek);
    } catch (Throwable $e) {
        $pesan = "<div class='alert-error'>❌ Gagal menambah data: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

if (isset($_POST['update'])) {
    $kode_lama       = trim($_POST['kode_lama']);
    $kode_matakuliah = trim($_POST['kode_matakuliah']);
    $nama_matakuliah = trim($_POST['nama_matakuliah']);
    $sks             = (int)$_POST['sks'];
    $dosen           = trim($_POST['dosen']);

    try {
        $stmt = mysqli_prepare($db, "UPDATE matakuliah SET kode_matakuliah = ?, nama_matakuliah = ?, sks = ?, dosen = ? WHERE kode_matakuliah = ?");
        mysqli_stmt_bind_param($stmt, "ssiss", $kode_matakuliah, $nama_matakuliah, $sks, $dosen, $kode_lama);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header("Location: data_matakuliah.php?status=updated");
        exit;
    } catch (Throwable $e) {
        $pesan = "<div class='alert-error'>❌ Gagal memperbarui data: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

if (isset($_GET['hapus'])) {
    try {
        $kode_hapus = $_GET['hapus'];
        $stmt = mysqli_prepare($db, "DELETE FROM matakuliah WHERE kode_matakuliah = ?");
        mysqli_stmt_bind_param($stmt, "s", $kode_hapus);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header("Location: data_matakuliah.php?status=deleted");
        exit;
    } catch (Throwable $e) {
        $pesan = "<div class='alert-error'>❌ Gagal menghapus data: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

if (isset($_GET['status'])) {
    if ($_GET['status'] == 'updated') $pesan = "<div class='alert-success'>✅ Data mata kuliah berhasil diperbarui!</div>";
    if ($_GET['status'] == 'deleted') $pesan = "<div class='alert-success'>✅ Data mata kuliah berhasil dihapus!</div>";
}

$edit_data = null;
if (isset($_GET['edit'])) {
    try {
        $kode_edit = $_GET['edit'];
        $stmt = mysqli_prepare($db, "SELECT * FROM matakuliah WHERE kode_matakuliah = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $kode_edit);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $edit_data = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);
    } catch (Throwable $e) {
    }
}

$daftar_dosen = [];
try {
    $q_dosen = mysqli_query($db, "SELECT nama FROM dosen ORDER BY nama ASC");
    if ($q_dosen) {
        while ($d = mysqli_fetch_assoc($q_dosen)) {
            $daftar_dosen[] = $d['nama'];
        }
    }
} catch (Throwable $e) {
}

$cari = trim($_GET['cari'] ?? '');
$daftar_mk = [];
try {
    if ($cari !== '') {
        $like = "%" . $cari . "%";
        $stmt = mysqli_prepare($db, "SELECT * FROM matakuliah WHERE kode_matakuliah LIKE ? OR nama_matakuliah LIKE ? OR dosen LIKE ? ORDER BY kode_matakuliah ASC");
        mysqli_stmt_bind_param($stmt, "sss", $like, $like, $like);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
    } else {
        $res = mysqli_query($db, "SELECT * FROM matakuliah ORDER BY kode_matakuliah ASC");
    }
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $daftar_mk[] = $row;
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
    <title>Kelola Mata Kuliah - SIAKAD</title>
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
    <a href="data_dosen.php">👨‍🏫 Data Dosen</a>
    <a href="data_matakuliah.php" class="active">📚 Data Mata Kuliah</a>

    <a href="logout.php" class="logout">🚪 Logout</a>
</div>

<div class="main">
    <div class="navbar">
        <h2>Kelola Data Mata Kuliah</h2>
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
            <h3>Daftar Mata Kuliah (Total: <?= count($daftar_mk); ?>)</h3>

            <form method="GET" action="data_matakuliah.php" class="search-bar">
                <input type="text" name="cari" class="search-input" placeholder="Cari Kode, Mata Kuliah, atau Dosen..." value="<?= htmlspecialchars($cari); ?>">
                <button type="submit" class="btn btn-primary">Cari</button>
                <?php if ($cari !== ''): ?>
                    <a href="data_matakuliah.php" class="btn btn-secondary">Reset</a>
                <?php endif; ?>
            </form>

            <div class="table-scroll">
                <table>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Mata Kuliah</th>
                        <th>SKS</th>
                        <th>Dosen Pengampu</th>
                        <th>Aksi</th>
                    </tr>
                    <?php if (count($daftar_mk) > 0): ?>
                        <?php foreach ($daftar_mk as $row): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row['kode_matakuliah']); ?></strong></td>
                            <td><?= htmlspecialchars($row['nama_matakuliah']); ?></td>
                            <td><?= (int)$row['sks']; ?></td>
                            <td><?= htmlspecialchars($row['dosen'] ?? '-'); ?></td>
                            <td class="td-nowrap">
                                <a href="data_matakuliah.php?edit=<?= urlencode($row['kode_matakuliah']); ?>" class="btn btn-warning">Edit</a>
                                <a href="data_matakuliah.php?hapus=<?= urlencode($row['kode_matakuliah']); ?>" class="btn btn-danger" onclick="return confirm('Yakin ingin menghapus mata kuliah <?= htmlspecialchars($row['nama_matakuliah']); ?>?')">Hapus</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center">Data mata kuliah tidak ditemukan.</td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <div class="box box-form">
            <h3><?= $edit_data ? 'Edit Mata Kuliah' : 'Tambah Mata Kuliah'; ?></h3>
            <form method="POST" action="data_matakuliah.php">
                <?php if ($edit_data): ?>
                    <input type="hidden" name="kode_lama" value="<?= htmlspecialchars($edit_data['kode_matakuliah']); ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Kode Mata Kuliah</label>
                    <input type="text" name="kode_matakuliah" placeholder="Contoh: MK11" required value="<?= htmlspecialchars($edit_data['kode_matakuliah'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Nama Mata Kuliah</label>
                    <input type="text" name="nama_matakuliah" placeholder="Contoh: Pemrograman Web" required value="<?= htmlspecialchars($edit_data['nama_matakuliah'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>SKS</label>
                    <input type="number" name="sks" min="1" max="6" required value="<?= htmlspecialchars($edit_data['sks'] ?? '3'); ?>">
                </div>

                <div class="form-group">
                    <label>Dosen Pengampu</label>
                    <input type="text" name="dosen" list="list_dosen" placeholder="Ketik atau pilih nama dosen" required value="<?= htmlspecialchars($edit_data['dosen'] ?? ''); ?>">
                    <datalist id="list_dosen">
                        <?php foreach ($daftar_dosen as $nama_dsn): ?>
                            <option value="<?= htmlspecialchars($nama_dsn); ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>

                <?php if ($edit_data): ?>
                    <button type="submit" name="update" class="btn btn-warning">Simpan</button>
                    <a href="data_matakuliah.php" class="btn btn-secondary">Batal</a>
                <?php else: ?>
                    <button type="submit" name="tambah" class="btn btn-primary btn-full">+ Tambah Mata Kuliah</button>
                <?php endif; ?>
            </form>
        </div>

    </div>

    <p class="nilai-footer">Sistem Informasi Akademik - Panel Admin</p>
</div>

</body>
</html>