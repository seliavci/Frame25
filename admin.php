<?php
session_start();

// [Yönerge Madde 9]: Aktif oturum kontrolü. Giriş yapmayan admin paneline giremez.
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['Super Admin', 'Editor'])) {
    header("Location: login.php");
    exit();
}

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 1);
include 'baglan.php';

// YENİ: VERİTABANINA YENİ SÜTUNLARI VE BEĞENİ TABLOSUNU OTOMATİK EKLE
// [DÜZENLEME]: Senin listene TMDB_ID, Kısa Film, Gişe ve Ödül sütunlarını ekledim.
$yeni_sutunlar = [
    'duration' => 'INT', 
    'studios' => 'VARCHAR(255)', 
    'platforms' => 'VARCHAR(255)', 
    'languages' => 'VARCHAR(255)',
    'tmdb_id' => 'INT',
    'is_short_film' => 'INT DEFAULT 0',
    'revenue' => 'VARCHAR(255)',
    'awards' => 'TEXT'
];
foreach ($yeni_sutunlar as $sutun => $tip) {
    $check_col = $baglanti->query("SHOW COLUMNS FROM filmler LIKE '$sutun'");
    if($check_col && $check_col->num_rows == 0) {
        $baglanti->query("ALTER TABLE filmler ADD $sutun $tip NULL");
    }
}
// Beğenilenler tablosunu otomatik kur
$baglanti->query("CREATE TABLE IF NOT EXISTS begenilenler (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, film_id INT, tarih TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");

$admin_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : "Admin";
$site_title = "FRAME 25 - YÖNETİM";
$mesaj = "";

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

// [Yönerge Madde 16]: TOPLU SİLME İŞLEMİ (Loglamalı ve Güvenli)
if (isset($_POST['toplu_sil']) && !empty($_POST['secili_idlere'])) {
    foreach ($_POST['secili_idlere'] as $item) {
        $parca = explode('_', $item);
        $tip = $parca[0]; // film veya haber
        $sil_id = intval($parca[1]);

        if ($tip == 'film') {
            $stmt = $baglanti->prepare("DELETE FROM filmler WHERE id = ?");
            $stmt->bind_param("i", $sil_id);
            if($stmt->execute()) { logTut($baglanti, "Toplu Film Silindi", "ID: $sil_id"); }
        } elseif ($tip == 'haber') {
            $stmt = $baglanti->prepare("DELETE FROM haberler WHERE id = ?");
            $stmt->bind_param("i", $sil_id);
            if($stmt->execute()) { logTut($baglanti, "Toplu Haber Silindi", "ID: $sil_id"); }
        }
    }
    $mesaj = "Seçilen içerikler toplu olarak silindi!";
}

// [Yönerge Madde 3 & 31]: Silme ve Güncelleme işlemleri Prepared Statements + Loglama
if (isset($_GET['del_comment'])) { 
    $stmt = $baglanti->prepare("DELETE FROM liste_yorumlar WHERE id = ?");
    $stmt->bind_param("i", $_GET['del_comment']);
    if($stmt->execute()) {
        logTut($baglanti, "Yorum Silindi", "ID: " . $_GET['del_comment']);
        $mesaj = "Yorum silindi!"; 
    }
}
if (isset($_GET['del_film'])) { 
    $stmt = $baglanti->prepare("DELETE FROM filmler WHERE id = ?");
    $stmt->bind_param("i", $_GET['del_film']);
    if($stmt->execute()) {
        logTut($baglanti, "Film Silindi", "ID: " . $_GET['del_film']);
        $mesaj = "Film silindi!"; 
    }
}
if (isset($_GET['del_news'])) { 
    $stmt = $baglanti->prepare("DELETE FROM haberler WHERE id = ?");
    $stmt->bind_param("i", $_GET['del_news']);
    if($stmt->execute()) {
        logTut($baglanti, "Haber Silindi", "ID: " . $_GET['del_news']);
        $mesaj = "Haber silindi!"; 
    }
}
if (isset($_GET['set_daily'])) { 
    $baglanti->query("UPDATE filmler SET is_daily_film = 0"); 
    $stmt = $baglanti->prepare("UPDATE filmler SET is_daily_film = 1 WHERE id = ?");
    $stmt->bind_param("i", $_GET['set_daily']);
    if($stmt->execute()) {
        logTut($baglanti, "Günün Filmi Değişti", "Yeni Film ID: " . $_GET['set_daily']);
        $mesaj = "Günün filmi güncellendi!"; 
    }
}

$h_edit_mode = false; $h_edit_row = null;
if (isset($_GET['h_edit_id'])) {
    $stmt = $baglanti->prepare("SELECT * FROM haberler WHERE id = ?");
    $stmt->bind_param("i", $_GET['h_edit_id']);
    $stmt->execute();
    $h_res = $stmt->get_result();
    if ($h_res && $h_res->num_rows > 0) { $h_edit_row = $h_res->fetch_assoc(); $h_edit_mode = true; }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_POST['haber_ekle']) || isset($_POST['haber_guncelle']))) {
    $baslik = $_POST['h_baslik'];
    $icerik = $_POST['h_icerik'];
    $resim = processImageInput('h_resim_file', 'h_resim_link', $_POST['eski_h_resim'] ?? ''); 

    if (isset($_POST['haber_guncelle'])) {
        $id = intval($_POST['h_id']);
        $stmt = $baglanti->prepare("UPDATE haberler SET baslik=?, icerik=?, resim=? WHERE id=?");
        $stmt->bind_param("sssi", $baslik, $icerik, $resim, $id);
        if($stmt->execute()) { 
            logTut($baglanti, "Haber Güncellendi", "Başlık: $baslik");
            $mesaj = "Haber güncellendi!"; header("Refresh:1; url=admin.php"); 
        }
    } else {
        $stmt = $baglanti->prepare("INSERT INTO haberler (baslik, icerik, resim, tarih) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("sss", $baslik, $icerik, $resim);
        if($stmt->execute()) { 
            logTut($baglanti, "Yeni Haber Eklendi", "Başlık: $baslik");
            $mesaj = "Haber eklendi!"; 
        }
    }
}

$edit_mode = false; $edit_row = null;
if (isset($_GET['edit_id'])) {
    $stmt = $baglanti->prepare("SELECT * FROM filmler WHERE id = ?");
    $stmt->bind_param("i", $_GET['edit_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) { $edit_row = $res->fetch_assoc(); $edit_mode = true; }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_POST['film_guncelle']) || isset($_POST['film_ekle']))) {
    $title = $_POST['title'];
    $dir = $_POST['dir'];
    $price = floatval($_POST['price']);
    $description = $_POST['description'];
    $trailer = $_POST['trailer'];
    $is_showcase = isset($_POST['is_showcase']) ? 1 : 0;
    $release_date = !empty($_POST['release_date']) ? $_POST['release_date'] : NULL;
    $duration = intval($_POST['duration']);
    $studios = $_POST['studios'];
    $platforms = isset($_POST['platforms']) ? implode(', ', $_POST['platforms']) : '';
    $category = isset($_POST['categories']) ? implode(', ', $_POST['categories']) : '';
    $country_code = isset($_POST['countries']) ? implode(', ', $_POST['countries']) : '';
    $languages = isset($_POST['languages']) ? implode(', ', $_POST['languages']) : '';
    $img = processImageInput('img_file', 'img_link', $_POST['eski_img'] ?? '');
    
    // [DÜZENLEME]: Yeni verileri POST'tan alıyoruz.
    $tmdb_id = intval($_POST['tmdb_id']);
    $is_short_film = isset($_POST['is_short_film']) ? 1 : 0;
    $revenue = $_POST['revenue'];
    $awards = $_POST['awards'];

    if (isset($_POST['film_guncelle'])) {
        $id = intval($_POST['film_id']);
        // [DÜZENLEME]: UPDATE sorgusuna yeni alanları ekledik.
        $stmt = $baglanti->prepare("UPDATE filmler SET title=?, dir=?, price=?, category=?, img=?, description=?, trailer=?, is_showcase=?, release_date=?, country_code=?, languages=?, duration=?, studios=?, platforms=?, tmdb_id=?, is_short_film=?, revenue=?, awards=? WHERE id=?");
        $stmt->bind_param("ssdssssisssissiissi", $title, $dir, $price, $category, $img, $description, $trailer, $is_showcase, $release_date, $country_code, $languages, $duration, $studios, $platforms, $tmdb_id, $is_short_film, $revenue, $awards, $id);
        if ($stmt->execute()) { 
            logTut($baglanti, "Film Güncellendi", "Başlık: $title");
            $mesaj = "Film güncellendi!"; header("Refresh:1; url=admin.php"); 
        }
    } else {
        // [DÜZENLEME]: INSERT sorgusuna yeni alanları ekledik.
        $stmt = $baglanti->prepare("INSERT INTO filmler (title, dir, price, category, img, description, trailer, is_showcase, release_date, country_code, languages, duration, studios, platforms, tmdb_id, is_short_film, revenue, awards) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdssssisssissiiss", $title, $dir, $price, $category, $img, $description, $trailer, $is_showcase, $release_date, $country_code, $languages, $duration, $studios, $platforms, $tmdb_id, $is_short_film, $revenue, $awards);
        if ($stmt->execute()) { 
            logTut($baglanti, "Yeni Film Eklendi", "Başlık: $title");
            $mesaj = "Film başarıyla eklendi!"; 
        }
    }
}

// Veri çekme kısımları aynen devam ediyor...
$filmler_sonuc = $baglanti->query("SELECT * FROM filmler ORDER BY id DESC");
$haberler_sonuc = $baglanti->query("SELECT * FROM haberler ORDER BY id DESC");
$yorumlar_sonuc = $baglanti->query("SHOW TABLES LIKE 'liste_yorumlar'")->num_rows > 0 ? $baglanti->query("SELECT * FROM liste_yorumlar ORDER BY tarih DESC LIMIT 10") : null;
$toplam_siparis_q = $baglanti->query("SHOW TABLES LIKE 'sepet_siparisler'")->num_rows > 0 ? $baglanti->query("SELECT COUNT(*) as c FROM sepet_siparisler") : null;

$toplam_siparis = ($toplam_siparis_q) ? $toplam_siparis_q->fetch_assoc()['c'] : 0;
$toplam_film = $filmler_sonuc ? $filmler_sonuc->num_rows : 0;
$toplam_haber = $haberler_sonuc ? $haberler_sonuc->num_rows : 0;
$toplam_yorum = ($yorumlar_sonuc) ? $yorumlar_sonuc->num_rows : 0;

$gunun_filmi_adi = "Belirlenmedi";
$gf_sorgu = $baglanti->query("SELECT title FROM filmler WHERE is_daily_film = 1 LIMIT 1");
if($gf_sorgu && $gf_sorgu->num_rows > 0) { $gunun_filmi_adi = $gf_sorgu->fetch_assoc()['title']; }

$secili_kategoriler = $edit_mode ? explode(', ', $edit_row['category']) : [];
$secili_ulkeler = $edit_mode ? explode(', ', $edit_row['country_code']) : [];
$secili_diller = ($edit_mode && isset($edit_row['languages'])) ? explode(', ', $edit_row['languages']) : [];
$secili_platformlar = ($edit_mode && isset($edit_row['platforms'])) ? explode(', ', $edit_row['platforms']) : [];

$tum_kategoriler = ["A24 Koleksiyonu", "Aksiyon", "Animasyon", "Art-House", "Bağımsız", "Belgesel", "Bilim Kurgu", "Biyografi", "Deneysel", "Distopya", "Dram", "Dönem", "Fantastik", "Female Gaze", "Fransız Yeni Dalgası", "Gerilim", "Gizem", "Giallo", "Hollywood", "Korku", "Komedi", "Kült", "Macera", "Masters of Cinema", "Mind-Bending", "Mockumentary", "Modern Auteurs", "Müzikal", "Neon Noir & Cyberpunk", "New Hollywood", "Noir", "Oscar", "Palme d'Or", "Politik", "Psikolojik", "Romantik", "Slasher", "Slow Cinema", "Spaghetti Western", "Spor", "Suç", "Savaş", "Tarih", "Varoluşsal Sancılar", "Western", "Whodunit"];
$tum_diller = ["Türkçe", "İngilizce", "Fransızca", "Almanca", "İspanyolca", "İtalyanca", "Japonca", "Korece", "Arapça", "Rusça", "İsveççe", "Danca", "Farsça", "Hintçe", "Çince", "Felemenkçe", "Lehçe", "Portekizce"];
$tum_ulkeler = ["TR - Türkiye", "US - Amerika", "FR - Fransa", "UK - İngiltere", "DE - Almanya", "IT - Italya", "JP - Japonya", "KR - Güney Kore", "ES - Ispanya", "CA - Kanada", "IN - Hindistan", "CN - Çin", "IR - İran", "BR - Brazilya", "SE - İsveç", "DK - Danimarka", "RU - Rusya"];
$tum_platformlar = ["Netflix", "MUBI", "BluTV", "HBO", "Amazon Prime", "Disney+", "Apple TV+", "Exxen", "Gain", "PuhuTV", "Sinemalarda"];
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?php echo $site_title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; } body { font-family: 'Montserrat', sans-serif; background: #050505; color: #fff; margin: 0; }
        header { background: #000; padding: 20px 40px; border-bottom: 3px solid #b20710; display: flex; justify-content: space-between; align-items: center; position: sticky; top:0; z-index:100;}
        header h1 { margin: 0; font-family: 'Bebas Neue'; color: #b20710; font-size: 2.5em; letter-spacing: 1px;}
        .container { display: flex; }
        aside { width: 220px; background: #111; min-height: 100vh; padding: 20px 0; border-right: 1px solid #222; }
        aside a { display: block; padding: 15px 25px; color: #888; text-decoration: none; font-weight: bold; font-size: 12px; text-transform: uppercase; transition: 0.3s;}
        aside a:hover, aside a.active { color: #fff; background: #b20710; border-right: 4px solid #fff;}
        main { flex: 1; padding: 40px; }
        .dashboard { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 40px; }
        .dash-card { background: #111; padding: 20px; border-radius: 4px; border-left: 4px solid #b20710; border-top: 1px solid #222; border-right: 1px solid #222; border-bottom: 1px solid #222;}
        .dash-card h3 { margin: 0 0 10px 0; color: #888; font-size: 10px; text-transform: uppercase; }
        .dash-card p { margin: 0; font-size: 22px; font-family: 'Bebas Neue'; color: #fff; letter-spacing: 1px;}
        .dash-card i { float: right; font-size: 24px; color: #333; }
        .form-container { background: #111; padding: 30px; border-radius: 8px; border: 1px solid #222; margin-bottom: 40px; border-top: 4px solid #b20710; }
        .form-container h2 { font-family: 'Bebas Neue'; font-size: 2em; margin-top: 0; border-bottom: 1px solid #222; padding-bottom: 15px;}
        .row { display: flex; gap: 20px; } .input-group { flex: 1; margin-bottom: 15px; }
        label { display: block; font-size: 11px; color: #888; font-weight: bold; text-transform: uppercase; margin-bottom: 8px; }
        input[type="text"], input[type="number"], input[type="date"], textarea { width: 100%; padding: 12px; background: #000; border: 1px solid #333; color: #fff; border-radius: 4px; font-family: inherit; outline: none; transition: 0.3s;}
        input:focus, textarea:focus { border-color: #b20710; }
        input[type="file"] { background: #1a1a1a; padding: 9px; cursor: pointer; margin-bottom: 10px; }
        .multi-select-box { background: #000; border: 1px solid #333; border-radius: 4px; padding: 10px; height: 120px; overflow-y: auto; }
        .multi-select-box::-webkit-scrollbar { width: 6px; }
        .multi-select-box::-webkit-scrollbar-thumb { background: #b20710; border-radius: 10px; }
        .checkbox-item { display: flex; align-items: center; padding: 4px 0; color: #ccc; font-size: 11px; font-weight: normal; cursor: pointer; text-transform: none; margin: 0;}
        .checkbox-item input { margin-right: 8px; cursor: pointer; transform: scale(1.1); }
        .btn-add { background: #00e054; color: #000; border: none; padding: 15px; font-weight: bold; width: 100%; cursor: pointer; text-transform: uppercase; border-radius: 4px; transition: 0.3s; margin-top: 15px; font-size: 12px;}
        .btn-add:hover { background: #fff; }
        .btn-edit { color: #ffd700; text-decoration: none; margin-right: 15px; font-size: 11px; font-weight: bold; text-transform: uppercase;}
        .btn-del { color: #b20710; text-decoration: none; font-size: 11px; font-weight: bold; text-transform: uppercase;}
        .btn-daily { color: #00e054; text-decoration: none; font-size: 11px; margin-left: 15px; font-weight: bold; text-transform: uppercase;}
        .table-header { display: flex; justify-content: space-between; align-items: center; margin-top: 40px; margin-bottom: 15px; }
        .search-box { padding: 10px 15px; background: #111; border: 1px solid #333; color: #fff; border-radius: 20px; width: 300px; outline: none;}
        table { width: 100%; border-collapse: collapse; background: #111; border-radius: 4px; overflow: hidden; margin-bottom: 40px;}
        th, td { padding: 15px; border-bottom: 1px solid #222; text-align: left; font-size: 13px; }
        th { font-family: 'Bebas Neue'; color: #b20710; font-size: 1.2em; letter-spacing: 1px; background: #0a0a0a;}
        tr:hover { background: #1a1a1a; }
        .msg { background: rgba(0, 224, 84, 0.1); color: #00e054; padding: 15px; border: 1px solid #00e054; margin-bottom: 30px; border-radius: 4px; font-weight: bold; text-align: center; font-size: 12px; text-transform: uppercase;}
        .vitrin-check { display: flex; align-items: center; gap: 10px; background: #0a0a0a; padding: 15px; border: 1px dashed #333; border-radius: 4px; margin-top: 15px;}
        .vitrin-check input { width: auto; transform: scale(1.3); cursor: pointer;}
        
        .btn-export { background: #00e054; color: #000; padding: 8px 15px; border-radius: 20px; font-size: 10px; font-weight: bold; text-decoration: none; transition: 0.3s; display: flex; align-items: center; gap: 8px;}
        .btn-export:hover { background: #fff; }

        .btn-bulk-del { background: #b20710; color: #fff; padding: 8px 15px; border: none; border-radius: 20px; font-size: 10px; font-weight: bold; cursor: pointer; transition: 0.3s; display: flex; align-items: center; gap: 8px; text-transform: uppercase;}
        .btn-bulk-del:hover { background: #fff; color: #b20710; }
    </style>
</head>
<body>

<header>
    <h1>FRAME 25 <small style="color:#555; font-size:0.4em">CMS</small></h1>
    <div style="display:flex; gap:20px; align-items:center;">
        <span style="color:#888; font-size:12px; font-weight:bold;">YÖNETİCİ: <?php echo strtoupper($admin_name); ?></span>
        <a href="index.php" style="color:#fff; text-decoration:none; font-weight:bold; font-size: 12px; background:#b20710; padding:8px 15px; border-radius:3px;">SİTEYİ GÖR</a>
    </div>
</header>

<div class="container">
    <aside>
        <a href="admin.php" class="active"><i class="fas fa-database" style="margin-right:8px;"></i> İçerik Paneli</a>
        <a href="admin_slider.php"><i class="fas fa-images" style="margin-right:8px;"></i> Slider Yönetimi</a>
        <a href="admin_ayarlar.php"><i class="fas fa-cog" style="margin-right:8px;"></i> Site Ayarları</a>
        <a href="admin_logs.php"><i class="fas fa-history" style="margin-right:8px;"></i> Sistem Günlükleri</a>
        <a href="admin_oyuncu.php"><i class="fas fa-users" style="margin-right:8px;"></i> Kadro Yönetimi</a>
        <a href="profile.php"><i class="fas fa-user" style="margin-right:8px;"></i> Profilime Dön</a>
        <a href="logout.php" style="color:#b20710;"><i class="fas fa-sign-out-alt" style="margin-right:8px;"></i> Güvenli Çıkış</a>
    </aside>

    <main>
        <?php if($mesaj): ?>
            <div id="adminMsg" class="msg"><i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($mesaj); ?></div>
            <script>setTimeout(function(){ document.getElementById('adminMsg').style.display='none'; }, 3000);</script>
        <?php endif; ?>

        <div class="dashboard">
            <div class="dash-card"><i class="fas fa-film"></i><h3>Filmler</h3><p><?php echo $toplam_film; ?> ADET</p></div>
            <div class="dash-card"><i class="fas fa-comments"></i><h3>Yorumlar</h3><p><?php echo $toplam_yorum; ?> ADET</p></div>
            <div class="dash-card"><i class="fas fa-shopping-bag"></i><h3>Satışlar</h3><p><?php echo $toplam_siparis; ?> SİPARİŞ</p></div>
            <div class="dash-card" style="border-left-color: #ffd700;"><i class="fas fa-star" style="color: #ffd700;"></i><h3>Günün Filmi</h3><p style="color: #ffd700; font-size: 18px;"><?php echo mb_strimwidth($gunun_filmi_adi, 0, 20, '...'); ?></p></div>
        </div>

        <div class="row">
            <div class="form-container" style="flex:1;">
                <h2><?php echo $h_edit_mode ? 'Haberi Düzenle' : 'Yeni Haber Ekle'; ?></h2>
                <form action="admin.php" method="POST" enctype="multipart/form-data">
                    <?php if($h_edit_mode): ?>
                        <input type="hidden" name="h_id" value="<?php echo $h_edit_row['id']; ?>">
                        <input type="hidden" name="eski_h_resim" value="<?php echo $h_edit_row['resim']; ?>">
                    <?php endif; ?>
                    <div class="input-group"><label>Haber Başlığı</label><input type="text" name="h_baslik" required value="<?php echo $h_edit_mode ? htmlspecialchars($h_edit_row['baslik']) : ''; ?>"></div>
                    <div class="input-group" style="background: #0a0a0a; padding: 15px; border: 1px dashed #333; border-radius: 4px;">
                        <label>Görsel (Dosya VEYA Link)</label>
                        <input type="file" name="h_resim_file" accept="image/*">
                        <div style="text-align:center; color:#555; font-size:10px; margin:5px 0;">-- VEYA --</div>
                        <input type="text" name="h_resim_link" placeholder="http:// ile başlayan link">
                    </div>
                    <div class="input-group"><label>Haber İçeriği</label><textarea name="h_icerik" rows="4" required><?php echo $h_edit_mode ? htmlspecialchars($h_edit_row['icerik']) : ''; ?></textarea></div>
                    <button type="submit" name="<?php echo $h_edit_mode ? 'haber_guncelle' : 'haber_ekle'; ?>" class="btn-add" style="<?php echo $h_edit_mode ? 'background:#ffd700; color:#000' : ''; ?>">YAYINLA</button>
                    <?php if($h_edit_mode): ?><a href="admin.php" style="display:block; text-align:center; margin-top:10px; color:#666; font-size:11px;">İPTAL</a><?php endif; ?>
                </form>
            </div>

            <div class="form-container" style="flex:2;">
                <h2><?php echo $edit_mode ? 'Filmi Düzenle' : 'Yeni Film Ekle'; ?></h2>
                <form action="admin.php" method="POST" enctype="multipart/form-data">
                    <?php if($edit_mode): ?>
                        <input type="hidden" name="film_id" value="<?php echo $edit_row['id']; ?>">
                        <input type="hidden" name="eski_img" value="<?php echo $edit_row['img']; ?>">
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="input-group"><label>TMDB ID</label><input type="number" name="tmdb_id" placeholder="Örn: 550" value="<?php echo $edit_mode ? $edit_row['tmdb_id'] : ''; ?>"></div>
                        <div class="input-group"><label>Gişe Hasılatı</label><input type="text" name="revenue" placeholder="Örn: 1.2 Milyar $" value="<?php echo $edit_mode ? htmlspecialchars($edit_row['revenue']) : ''; ?>"></div>
                    </div>

                    <div class="row">
                        <div class="input-group"><label>Film Adı</label><input type="text" name="title" required value="<?php echo $edit_mode ? htmlspecialchars($edit_row['title']) : ''; ?>"></div>
                        <div class="input-group"><label>Yönetmen</label><input type="text" name="dir" required value="<?php echo $edit_mode ? htmlspecialchars($edit_row['dir']) : ''; ?>"></div>
                    </div>
                    <div class="row">
                        <div class="input-group"><label>Fiyat (TL)</label><input type="number" step="0.01" name="price" required value="<?php echo $edit_mode ? $edit_row['price'] : ''; ?>"></div>
                        <div class="input-group"><label>Vizyon Tarihi (Opsiyonel)</label><input type="date" name="release_date" value="<?php echo $edit_mode ? $edit_row['release_date'] : ''; ?>"></div>
                        <div class="input-group"><label>Süre (Dakika)</label><input type="number" name="duration" placeholder="Örn: 120" value="<?php echo $edit_mode ? $edit_row['duration'] : ''; ?>"></div>
                    </div>
                    <div class="input-group"><label>Yapımcı Stüdyo(lar)</label><input type="text" name="studios" placeholder="Örn: Warner Bros., A24, Ay Yapım" value="<?php echo $edit_mode ? htmlspecialchars($edit_row['studios']) : ''; ?>"></div>
                    
                    <div class="input-group"><label>Ödüller & Festivaller</label><textarea name="awards" rows="2" placeholder="Örn: Cannes Film Festivali - En İyi Senaryo"><?php echo $edit_mode ? htmlspecialchars($edit_row['awards']) : ''; ?></textarea></div>

                    <div class="row">
                        <div class="input-group"><label>Kategoriler</label>
                            <div class="multi-select-box">
                                <?php foreach($tum_kategoriler as $cat): ?>
                                    <label class="checkbox-item"><input type="checkbox" name="categories[]" value="<?php echo htmlspecialchars($cat); ?>" <?php echo in_array($cat, $secili_kategoriler) ? 'checked' : ''; ?>><?php echo htmlspecialchars($cat); ?></label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="input-group"><label>Diller</label>
                            <div class="multi-select-box">
                                <?php foreach($tum_diller as $dil): ?>
                                    <label class="checkbox-item"><input type="checkbox" name="languages[]" value="<?php echo htmlspecialchars($dil); ?>" <?php echo in_array($dil, $secili_diller) ? 'checked' : ''; ?>><?php echo htmlspecialchars($dil); ?></label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="input-group"><label>Ülkeler</label>
                            <div class="multi-select-box">
                                <?php foreach($tum_ulkeler as $ulke): ?>
                                    <label class="checkbox-item"><input type="checkbox" name="countries[]" value="<?php echo htmlspecialchars(substr($ulke, 0, 2)); ?>" <?php echo in_array(substr($ulke, 0, 2), $secili_ulkeler) ? 'checked' : ''; ?>><?php echo htmlspecialchars($ulke); ?></label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="input-group"><label>Platformlar</label>
                            <div class="multi-select-box">
                                <?php foreach($tum_platformlar as $plat): ?>
                                    <label class="checkbox-item"><input type="checkbox" name="platforms[]" value="<?php echo htmlspecialchars($plat); ?>" <?php echo in_array($plat, $secili_platformlar) ? 'checked' : ''; ?>><?php echo htmlspecialchars($plat); ?></label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="input-group" style="background: #0a0a0a; padding: 15px; border: 1px dashed #333; border-radius: 4px;">
                        <label>Film Afişi (Dosya Seç VEYA Link Gir)</label>
                        <input type="file" name="img_file" accept="image/*">
                        <div style="text-align:center; color:#555; font-size:10px; margin:5px 0;">-- VEYA --</div>
                        <input type="text" name="img_link" placeholder="Afiş linkini yapıştırın" value="<?php echo $edit_mode ? htmlspecialchars($edit_row['img']) : ''; ?>">
                    </div>
                    <div class="input-group"><label>YouTube Fragman Linki</label><input type="text" name="trailer" placeholder="Fragman URL'si" value="<?php echo $edit_mode ? htmlspecialchars($edit_row['trailer']) : ''; ?>"></div>
                    <div class="input-group"><label>Film Özeti</label><textarea name="description" rows="3" required><?php echo $edit_mode ? htmlspecialchars($edit_row['description']) : ''; ?></textarea></div>
                    
                    <div style="display:flex; gap:10px;">
                        <div class="vitrin-check" style="flex:1;">
                            <input type="checkbox" name="is_showcase" id="is_showcase" <?php echo ($edit_mode && $edit_row['is_showcase'] == 1) ? 'checked' : ''; ?>>
                            <label for="is_showcase" style="margin:0; cursor:pointer;">Ana Sayfa "Vizyonda" Vitrinine Ekle</label>
                        </div>
                        <div class="vitrin-check" style="flex:1; border-color: #00e054;">
                            <input type="checkbox" name="is_short_film" id="is_short_film" <?php echo ($edit_mode && $edit_row['is_short_film'] == 1) ? 'checked' : ''; ?>>
                            <label for="is_short_film" style="margin:0; cursor:pointer; color:#00e054;">Bu bir "Kısa Film"dir</label>
                        </div>
                    </div>

                    <button type="submit" name="<?php echo $edit_mode ? 'film_guncelle' : 'film_ekle'; ?>" class="btn-add" style="<?php echo $edit_mode ? 'background:#ffd700; color:#000' : ''; ?>">VERİTABANINA KAYDET</button>
                    <?php if($edit_mode): ?><a href="admin.php" style="display:block; text-align:center; margin-top:10px; color:#666; font-size:11px;">İPTAL</a><?php endif; ?>
                </form>
            </div>
        </div>

        <div class="table-header">
            <h3 style="margin:0; font-family:'Bebas Neue'; font-size:2em; color:#fff;">KAYITLI İÇERİKLER</h3>
            <div style="display:flex; gap:15px; align-items:center;">
                <form action="admin.php" method="POST" id="bulkDeleteForm" onsubmit="return confirm('Seçili tüm içerikler silinecek. Emin misiniz?')">
                <button type="submit" name="toplu_sil" class="btn-bulk-del">
                    <i class="fas fa-trash-alt"></i> Seçilenleri Sil
                </button>
                <a href="export.php" class="btn-export" title="Listeyi Excel Olarak İndir">
                    <i class="fas fa-file-excel"></i> EXCEL AKTAR
                </a>
                <input type="text" id="tableSearch" class="search-box" onkeyup="filterTable()" placeholder="Film veya haber ara...">
            </div>
        </div>
        
        <table id="dataTable">
            <thead>
                <tr>
                    <th style="width:40px;"><input type="checkbox" id="checkAll"></th>
                    <th>TÜR</th>
                    <th>BAŞLIK</th>
                    <th style="text-align:right;">İŞLEMLER</th>
                </tr>
            </thead>
            <tbody>
                <?php if($haberler_sonuc): while($h = $haberler_sonuc->fetch_assoc()): ?>
                <tr class="searchable-row">
                    <td><input type="checkbox" name="secili_idlere[]" class="checkItem" value="haber_<?php echo $h['id']; ?>"></td>
                    <td>HABER</td><td class="item-title"><?php echo htmlspecialchars($h['baslik']); ?></td>
                    <td style="text-align:right;"><a href="admin.php?h_edit_id=<?php echo $h['id']; ?>" class="btn-edit">DÜZENLE</a><a href="admin.php?del_news=<?php echo $h['id']; ?>" class="btn-del" onclick="return confirm('Emin misiniz?')">SİL</a></td>
                </tr>
                <?php endwhile; endif; ?>
                <?php if($filmler_sonuc): while($f = $filmler_sonuc->fetch_assoc()): ?>
                <tr class="searchable-row">
                    <td><input type="checkbox" name="secili_idlere[]" class="checkItem" value="film_<?php echo $f['id']; ?>"></td>
                    <td>FİLM <?php echo ($f['is_short_film'] ? '<small style="color:#00e054">(KISA)</small>' : ''); ?></td>
                    <td class="item-title"><?php echo htmlspecialchars($f['title']); ?></td>
                    <td style="text-align:right;">
                        <a href="admin.php?set_daily=<?php echo $f['id']; ?>" class="btn-daily" title="Günün Filmi Yap"><i class="fas fa-star"></i></a>
                        <a href="admin.php?edit_id=<?php echo $f['id']; ?>" class="btn-edit" style="margin-left: 15px;">DÜZENLE</a>
                        <a href="admin.php?del_film=<?php echo $f['id']; ?>" class="btn-del" onclick="return confirm('Emin misiniz?')">SİL</a>
                    </td>
                </tr>
                <?php endwhile; endif; ?>
            </tbody>
        </table>
        </form> 
    </main>
</div>

<script>
    document.getElementById('checkAll').onclick = function() {
        var checkboxes = document.getElementsByClassName('checkItem');
        for (var checkbox of checkboxes) {
            checkbox.checked = this.checked;
        }
    }

    function filterTable() {
        var input = document.getElementById("tableSearch");
        var filter = input.value.toUpperCase();
        var tr = document.getElementsByClassName("searchable-row");
        for (var i = 0; i < tr.length; i++) {
            var td = tr[i].getElementsByClassName("item-title")[0];
            if (td) { tr[i].style.display = (td.innerText.toUpperCase().indexOf(filter) > -1) ? "" : "none"; }
        }
    }
</script>
</body>
</html>