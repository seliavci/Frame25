<?php
session_start();
include 'baglan.php';

// [Yönerge Madde 9]: Yetki Kontrolü [cite: 17, 18]
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'Super Admin') {
    header("Location: login.php");
    exit();
}

$mesaj = "";
$admin_name = $_SESSION['user_name'];

// --- [Yönerge Madde 13]: Ayarları Güncelleme İşlemi [cite: 24, 25] ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ayarlari_guncelle'])) {
    foreach ($_POST['ayar'] as $anahtar => $deger) {
        $deger = htmlspecialchars(trim($deger));
        // [Yönerge Madde 3]: Prepared Statement Kullanımı [cite: 6, 7]
        $stmt = $baglanti->prepare("UPDATE ayarlar SET ayar_deger = ? WHERE ayar_anahtar = ?");
        $stmt->bind_param("ss", $deger, $anahtar);
        $stmt->execute();
    }
    
    // [Yönerge Madde 18]: İşlemi Logla [cite: 34, 35]
    logTut($baglanti, "Site Ayarları Güncellendi", "Genel site konfigürasyonu değiştirildi.");
    
    // [Yönerge Madde 19]: Şık Uyarı Mesajı [cite: 36, 37]
    $mesaj = "Site ayarları başarıyla güncellendi!";
}

// Güncel ayarları tekrar çek 
$ayarlar_sorgu = $baglanti->query("SELECT * FROM ayarlar");
$ayarlar = [];
while ($row = $ayarlar_sorgu->fetch_assoc()) {
    $ayarlar[$row['ayar_anahtar']] = $row['ayar_deger'];
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Genel Ayarlar | FRAME 25 CMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <style>
        /* BOYUT UYUMSUZLUĞUNU BİTİREN GLOBAL RESET KALIPI */
        * { box-sizing: border-box; } 
        body { font-family: 'Montserrat', sans-serif; background: #050505; color: #fff; margin: 0; }
        
        /* HEADER BOYUTU VE HİZALAMASI  */
        header { background: #000; padding: 20px 40px; border-bottom: 3px solid #b20710; display: flex; justify-content: space-between; align-items: center; position: sticky; top:0; z-index:100;}
        header h1 { margin: 0; font-family: 'Bebas Neue'; color: #b20710; font-size: 2.5em; letter-spacing: 1px;}
        
        .container { display: flex; }
        
        /* SIDEBAR GENİŞLİK VE BOYUT EŞİTLEMESİ  */
        aside { width: 220px; background: #111; min-height: 100vh; padding: 20px 0; border-right: 1px solid #222; }
        aside a { display: block; padding: 15px 25px; color: #888; text-decoration: none; font-weight: bold; font-size: 12px; text-transform: uppercase; transition: 0.3s;}
        aside a:hover, aside a.active { color: #fff; background: #b20710; border-right: 4px solid #fff;}
        
        main { flex: 1; padding: 40px; }
        
        /* FORM KONTEYNER BOYUTLARI VE YAPISI */
        .form-container { background: #111; padding: 30px; border-radius: 8px; border: 1px solid #222; margin-bottom: 40px; border-top: 4px solid #b20710; }
        .form-container h2 { font-family: 'Bebas Neue'; font-size: 2em; margin-top: 0; border-bottom: 1px solid #222; padding-bottom: 15px;}
        
        .input-group { margin-bottom: 20px; }
        label { display: block; font-size: 11px; color: #888; font-weight: bold; text-transform: uppercase; margin-bottom: 8px; }
        input[type="text"], input[type="email"] { width: 100%; padding: 12px; background: #000; border: 1px solid #333; color: #fff; border-radius: 4px; font-family: inherit; outline: none; transition: 0.3s;}
        input:focus { border-color: #b20710; }
        
        .btn-save { background: #00e054; color: #000; border: none; padding: 15px; font-weight: bold; width: 100%; cursor: pointer; text-transform: uppercase; border-radius: 4px; font-size: 12px; transition: 0.3s;}
        .btn-save:hover { background: #fff; }
        
        .msg { background: rgba(0, 224, 84, 0.1); color: #00e054; padding: 15px; border: 1px solid #00e054; margin-bottom: 30px; border-radius: 4px; font-weight: bold; text-align: center; font-size: 12px;}
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
    <a href="admin_ayarlar.php" class="active"><i class="fas fa-cog" style="margin-right:8px;"></i> Site Ayarları</a>
    <a href="admin_logs.php"><i class="fas fa-history" style="margin-right:8px;"></i> Sistem Günlükleri</a>
    <a href="admin_oyuncu.php"><i class="fas fa-users" style="margin-right:8px;"></i> Kadro Yönetimi</a>
    <a href="profile.php"><i class="fas fa-user" style="margin-right:8px;"></i> Profilime Dön</a>
    <a href="logout.php" style="color:#b20710;"><i class="fas fa-sign-out-alt" style="margin-right:8px;"></i> Güvenli Çıkış</a>
</aside>

    <main>
        <?php if($mesaj): ?>
            <div id="adminMsg" class="msg"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($mesaj); ?></div>
            <script>setTimeout(()=>document.getElementById('adminMsg').style.display='none', 3000);</script>
        <?php endif; ?>

        <div class="form-container">
            <h2>MERKEZİ SİTE YAPILANDIRMASI</h2>
            <form method="POST">
                <div class="input-group">
                    <label>Site Adı</label>
                    <input type="text" name="ayar[site_adi]" value="<?php echo htmlspecialchars($ayarlar['site_adi']); ?>">
                </div>
                <div class="input-group">
                    <label>Slogan (Pure Cinema Experience)</label>
                    <input type="text" name="ayar[slogan]" value="<?php echo htmlspecialchars($ayarlar['slogan']); ?>">
                </div>
                <div class="input-group">
                    <label>İletişim E-posta Adresi</label>
                    <input type="email" name="ayar[iletisim_mail]" value="<?php echo htmlspecialchars($ayarlar['iletisim_mail']); ?>">
                </div>
                <div class="input-group">
                    <label>Instagram Linki</label>
                    <input type="text" name="ayar[instagram]" value="<?php echo htmlspecialchars($ayarlar['instagram']); ?>">
                </div>
                <div class="input-group">
                    <label>Facebook Linki</label>
                    <input type="text" name="ayar[facebook]" value="<?php echo htmlspecialchars($ayarlar['facebook']); ?>">
                </div>
                <button type="submit" name="ayarlari_guncelle" class="btn-save">DEĞİŞİKLİKLERİ KAYDET</button>
            </form>
        </div>
    </main>
</div>

</body>
</html>