<?php
session_start();
// Yönerge Madde 9: Aktif oturum kontrolü
// Eğer giriş yapılmamışsa veya rol yetkili değilse login.php'ye fırlat
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['Super Admin', 'Editor'])) {
    header("Location: login.php");
    exit();
}

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 1);
include 'baglan.php';

// --- YENİ: EKSİK SÜTUNLARI OTOMATİK EKLE (HATA ÖNLEYİCİ) ---
$yeni_sutunlar = [
    'biyografi' => 'TEXT', 'resim' => 'VARCHAR(255)', 
    'dogum_tarihi' => 'VARCHAR(100)', 'dogum_yeri' => 'VARCHAR(150)', 
    'aktif_yillar' => 'VARCHAR(50)', 'oduller' => 'TEXT', 'meshur_soz' => 'TEXT'
];
foreach ($yeni_sutunlar as $sutun => $tip) {
    $check = $baglanti->query("SHOW COLUMNS FROM oyuncular LIKE '$sutun'");
    if($check && $check->num_rows == 0) {
        $baglanti->query("ALTER TABLE oyuncular ADD $sutun $tip NULL");
    }
}

$admin_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : "Admin";
$site_title = "FRAME 25 - KADRO YÖNETİMİ";
$mesaj = "";

// --- DOSYA/LİNK FONKSİYONU ---
function processImageInput($file_input, $link_input, $old_image = '') {
    if (isset($_FILES[$file_input]) && $_FILES[$file_input]['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES[$file_input]['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            $new_name = time() . '_' . rand(1000,9999) . '.' . $ext;
            $dest = "uploads/" . $new_name;
            if (!is_dir('uploads')) { mkdir('uploads', 0777, true); }
            if (move_uploaded_file($_FILES[$file_input]['tmp_name'], $dest)) { return $dest; }
        }
    }
    if (isset($_POST[$link_input]) && !empty(trim($_POST[$link_input]))) { return trim($_POST[$link_input]); }
    return $old_image; 
}

// --- SİLME --- (Yönerge Madde 3: Prepared Statements Kullanıldı)
if (isset($_GET['del_kisi'])) {
    $d_id = intval($_GET['del_kisi']);
    
    $stmt1 = $baglanti->prepare("DELETE FROM film_oyuncular WHERE oyuncu_id = ?");
    $stmt1->bind_param("i", $d_id);
    $stmt1->execute();

    $stmt2 = $baglanti->prepare("DELETE FROM oyuncular WHERE id = ?");
    $stmt2->bind_param("i", $d_id);
    $stmt2->execute();
    
    $mesaj = "Kişi başarıyla silindi!";
}

// --- EKLEME / GÜNCELLEME ---
$edit_mode = false; $edit_row = null;
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    // Yönerge Madde 3: Güvenli Veri Çekme
    $stmt = $baglanti->prepare("SELECT * FROM oyuncular WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) { $edit_row = $res->fetch_assoc(); $edit_mode = true; }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_POST['kisi_ekle']) || isset($_POST['kisi_guncelle']))) {
    $ad_soyad = trim($_POST['ad_soyad']);
    $biyografi = trim($_POST['biyografi']);
    $dogum_tarihi = trim($_POST['dogum_tarihi']);
    $dogum_yeri = trim($_POST['dogum_yeri']);
    $aktif_yillar = trim($_POST['aktif_yillar']);
    $oduller = trim($_POST['oduller']);
    $meshur_soz = trim($_POST['meshur_soz']);
    
    $eski_resim = isset($_POST['eski_resim']) ? $_POST['eski_resim'] : '';
    $resim = processImageInput('resim_file', 'resim_link', $eski_resim); 

    if (isset($_POST['kisi_guncelle'])) {
        $id = intval($_POST['kisi_id']);
        // Yönerge Madde 3: Prepared Statement Update
        $stmt = $baglanti->prepare("UPDATE oyuncular SET ad_soyad=?, biyografi=?, resim=?, dogum_tarihi=?, dogum_yeri=?, aktif_yillar=?, oduller=?, meshur_soz=? WHERE id=?");
        $stmt->bind_param("ssssssssi", $ad_soyad, $biyografi, $resim, $dogum_tarihi, $dogum_yeri, $aktif_yillar, $oduller, $meshur_soz, $id);
        
        if ($stmt->execute()) { 
            $mesaj = "Profil güncellendi!"; 
            header("Refresh:1; url=admin_oyuncu.php"); 
        }
    } else {
        // Yönerge Madde 3: Prepared Statement Insert
        $stmt = $baglanti->prepare("INSERT INTO oyuncular (ad_soyad, biyografi, resim, dogum_tarihi, dogum_yeri, aktif_yillar, oduller, meshur_soz) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $ad_soyad, $biyografi, $resim, $dogum_tarihi, $dogum_yeri, $aktif_yillar, $oduller, $meshur_soz);
        if ($stmt->execute()) { $mesaj = "Yeni kişi eklendi!"; }
    }
}

$kisiler_sonuc = $baglanti->query("SELECT * FROM oyuncular ORDER BY id DESC");
$toplam_kisi = $kisiler_sonuc ? $kisiler_sonuc->num_rows : 0;
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?php echo $site_title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; } body { font-family: 'Montserrat', sans-serif; background: #050505; color: #fff; margin: 0; }
        header { background: #000; padding: 20px 40px; border-bottom: 3px solid #b20710; display: flex; justify-content: space-between; align-items: center; position: sticky; top:0; z-index:100;}
        header h1 { margin: 0; font-family: 'Bebas Neue'; color: #b20710; font-size: 2.5em; letter-spacing: 1px;}
        .container { display: flex; }
        aside { width: 220px; background: #111; min-height: 100vh; padding: 20px 0; border-right: 1px solid #222; }
        aside a { display: block; padding: 15px 25px; color: #888; text-decoration: none; font-weight: bold; font-size: 12px; text-transform: uppercase; transition: 0.3s;}
        aside a:hover, aside a.active { color: #fff; background: #b20710; border-right: 4px solid #fff;}
        main { flex: 1; padding: 40px; }
        
        .form-container { background: #111; padding: 30px; border-radius: 8px; border: 1px solid #222; margin-bottom: 40px; border-top: 4px solid #00e054; }
        .form-container h2 { font-family: 'Bebas Neue'; font-size: 2em; margin-top: 0; border-bottom: 1px solid #222; padding-bottom: 15px;}
        .row { display: flex; gap: 20px; } .input-group { flex: 1; margin-bottom: 15px; }
        label { display: block; font-size: 11px; color: #888; font-weight: bold; text-transform: uppercase; margin-bottom: 8px; }
        input[type="text"], textarea { width: 100%; padding: 12px; background: #000; border: 1px solid #333; color: #fff; border-radius: 4px; font-family: inherit; outline: none; transition: 0.3s;}
        input:focus, textarea:focus { border-color: #00e054; }
        input[type="file"] { background: #1a1a1a; padding: 9px; cursor: pointer; margin-bottom: 10px; }
        .btn-add { background: #00e054; color: #000; border: none; padding: 15px; font-weight: bold; width: 100%; cursor: pointer; text-transform: uppercase; border-radius: 4px; transition: 0.3s; margin-top: 15px; font-size: 12px;}
        .btn-add:hover { background: #fff; }
        
        .table-header { display: flex; justify-content: space-between; align-items: center; margin-top: 40px; margin-bottom: 15px; }
        .search-box { padding: 10px 15px; background: #111; border: 1px solid #333; color: #fff; border-radius: 20px; width: 300px; outline: none;}
        table { width: 100%; border-collapse: collapse; background: #111; border-radius: 4px; overflow: hidden; margin-bottom: 40px;}
        th, td { padding: 15px; border-bottom: 1px solid #222; text-align: left; font-size: 13px; }
        th { font-family: 'Bebas Neue'; color: #00e054; font-size: 1.2em; letter-spacing: 1px; background: #0a0a0a;}
        tr:hover { background: #1a1a1a; }
        .avatar-img { width: 40px; height: 40px; object-fit: cover; border-radius: 50%; vertical-align: middle; margin-right: 10px; border: 1px solid #333;}
        .msg { background: rgba(0, 224, 84, 0.1); color: #00e054; padding: 15px; border: 1px solid #00e054; margin-bottom: 30px; border-radius: 4px; font-weight: bold; text-align: center; font-size: 12px;}
        .btn-edit { color: #ffd700; text-decoration: none; margin-right: 15px; font-size: 11px; font-weight: bold;}
        .btn-del { color: #b20710; text-decoration: none; font-size: 11px; font-weight: bold;}
    </style>
</head>
<body>

<header>
    <h1>FRAME 25 <small style="color:#555; font-size:0.4em">CMS</small></h1>
    <div style="display:flex; gap:20px; align-items:center;">
        <span style="color:#888; font-size:12px; font-weight:bold;">HOŞ GELDİN: <?php echo strtoupper($admin_name); ?></span>
        <a href="index.php" style="color:#fff; text-decoration:none; font-weight:bold; font-size: 12px; background:#b20710; padding:8px 15px; border-radius:3px;">SİTEYİ GÖR</a>
    </div>
</header>

<div class="container">
<aside>
    <a href="admin.php"><i class="fas fa-database" style="margin-right:8px;"></i> İçerik Paneli</a>
    <a href="admin_slider.php"><i class="fas fa-sliders-h" style="margin-right:8px;"></i> Slider Yönetimi</a>
    <a href="admin_ayarlar.php"><i class="fas fa-cog" style="margin-right:8px;"></i> Site Ayarları</a>
    <a href="admin_logs.php"><i class="fas fa-history" style="margin-right:8px;"></i> Sistem Günlükleri</a>
    <a href="admin_oyuncu.php" class="active"><i class="fas fa-users" style="margin-right:8px;"></i> Kadro Yönetimi</a>
    <a href="profile.php"><i class="fas fa-user" style="margin-right:8px;"></i> Profilime Dön</a>
    <a href="logout.php" style="color:#b20710;"><i class="fas fa-sign-out-alt" style="margin-right:8px;"></i> Güvenli Çıkış</a>
</aside>

    <main>
        <?php if($mesaj): ?><div id="adminMsg" class="msg"><?php echo htmlspecialchars($mesaj); ?></div><script>setTimeout(()=>document.getElementById('adminMsg').style.display='none', 3000);</script><?php endif; ?>

        <div class="form-container">
            <h2><?php echo $edit_mode ? 'Kişi Profilini Düzenle' : 'Yeni Sanatçı Ekle (Detaylı)'; ?></h2>
            <form action="admin_oyuncu.php" method="POST" enctype="multipart/form-data">
                <?php if($edit_mode): ?>
                    <input type="hidden" name="kisi_id" value="<?php echo $edit_row['id']; ?>">
                    <input type="hidden" name="eski_resim" value="<?php echo $edit_row['resim']; ?>">
                <?php endif; ?>
                
                <div class="row">
                    <div class="input-group"><label>Adı Soyadı</label><input type="text" name="ad_soyad" required value="<?php echo $edit_mode ? htmlspecialchars($edit_row['ad_soyad']) : ''; ?>"></div>
                    <div class="input-group"><label>Aktif Yıllar (Örn: 1998 - Günümüz)</label><input type="text" name="aktif_yillar" value="<?php echo $edit_mode ? htmlspecialchars($edit_row['aktif_yillar']) : ''; ?>"></div>
                </div>

                <div class="row">
                    <div class="input-group"><label>Doğum Tarihi (Örn: 16 Temmuz 1970)</label><input type="text" name="dogum_tarihi" value="<?php echo $edit_mode ? htmlspecialchars($edit_row['dogum_tarihi']) : ''; ?>"></div>
                    <div class="input-group"><label>Doğum Yeri (Örn: Londra, İngiltere)</label><input type="text" name="dogum_yeri" value="<?php echo $edit_mode ? htmlspecialchars($edit_row['dogum_yeri']) : ''; ?>"></div>
                </div>

                <div class="input-group" style="background: #0a0a0a; padding: 15px; border: 1px dashed #333; border-radius: 4px;">
                    <label>Profil Fotoğrafı (Dosya Seç VEYA Link Gir)</label>
                    <input type="file" name="resim_file" accept="image/*">
                    <input type="text" name="resim_link" placeholder="Fotoğraf linkini yapıştırın" value="<?php echo $edit_mode ? htmlspecialchars($edit_row['resim']) : ''; ?>">
                </div>

                <div class="input-group"><label>Biyografi</label><textarea name="biyografi" rows="3" required><?php echo $edit_mode ? htmlspecialchars($edit_row['biyografi']) : ''; ?></textarea></div>
                <div class="input-group"><label>Meşhur Bir Sözü / Alıntı (Opsiyonel)</label><textarea name="meshur_soz" rows="2" placeholder="Örn: Filmlerimi anlasınlar diye değil hissetsinler diye yaparım..."><?php echo $edit_mode ? htmlspecialchars($edit_row['meshur_soz']) : ''; ?></textarea></div>
                <div class="input-group"><label>Ödüller & Başarılar (Opsiyonel, virgülle ayırın)</label><textarea name="oduller" rows="2" placeholder="Örn: 2x Oscar Kazananı, Cannes Altın Palmiye..."><?php echo $edit_mode ? htmlspecialchars($edit_row['oduller']) : ''; ?></textarea></div>

                <button type="submit" name="<?php echo $edit_mode ? 'kisi_guncelle' : 'kisi_ekle'; ?>" class="btn-add" style="<?php echo $edit_mode ? 'background:#ffd700; color:#000' : ''; ?>">
                    <?php echo $edit_mode ? 'DEĞİŞİKLİKLERİ KAYDET' : 'SİSTEME EKLE'; ?>
                </button>
                <?php if($edit_mode): ?><a href="admin_oyuncu.php" style="display:block; text-align:center; margin-top:10px; color:#666; font-size:11px;">İPTAL</a><?php endif; ?>
            </form>
        </div>

        <div class="table-header">
            <h3 style="margin:0; font-family:'Bebas Neue'; font-size:2em; color:#fff;">SİSTEMDEKİ SANATÇILAR</h3>
            <input type="text" id="tableSearch" class="search-box" onkeyup="filterTable()" placeholder="İsimle ara...">
        </div>
        
        <table id="dataTable">
            <thead><tr><th>AD SOYAD</th><th>ID</th><th style="text-align:right;">İŞLEMLER</th></tr></thead>
            <tbody>
                <?php if($kisiler_sonuc && $kisiler_sonuc->num_rows > 0): while($k = $kisiler_sonuc->fetch_assoc()): ?>
                <tr class="searchable-row">
                    <td class="item-title">
                        <img src="<?php echo !empty($k['resim']) ? htmlspecialchars($k['resim']) : 'https://ui-avatars.com/api/?name='.urlencode($k['ad_soyad']).'&background=111&color=fff'; ?>" class="avatar-img">
                        <strong><?php echo htmlspecialchars($k['ad_soyad']); ?></strong>
                    </td>
                    <td style="color:#666;">#<?php echo $k['id']; ?></td>
                    <td style="text-align:right;">
                        <a href="admin_oyuncu.php?edit_id=<?php echo $k['id']; ?>" class="btn-edit">DÜZENLE</a>
                        <a href="admin_oyuncu.php?del_kisi=<?php echo $k['id']; ?>" class="btn-del" onclick="return confirm('Emin misiniz?')">SİL</a>
                    </td>
                </tr>
                <?php endwhile; endif; ?>
            </tbody>
        </table>
    </main>
</div>
<script>
    function filterTable() {
        var filter = document.getElementById("tableSearch").value.toUpperCase();
        var tr = document.getElementsByClassName("searchable-row");
        for (var i = 0; i < tr.length; i++) {
            var td = tr[i].getElementsByClassName("item-title")[0];
            if (td) tr[i].style.display = (td.innerText.toUpperCase().indexOf(filter) > -1) ? "" : "none";
        }
    }
</script>
</body>
</html>