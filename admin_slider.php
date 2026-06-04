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

// --- [Yönerge Madde 11]: Slider Silme İşlemi --- [cite: 20]
if (isset($_GET['del_slider'])) {
    $stmt = $baglanti->prepare("DELETE FROM slider WHERE id = ?");
    $stmt->bind_param("i", $_GET['del_slider']);
    if($stmt->execute()) {
        logTut($baglanti, "Slider Silindi", "ID: " . $_GET['del_slider']);
        $mesaj = "Slider görseli kaldırıldı!";
    }
}

// --- [Yönerge Madde 11]: Yeni Slider Ekleme İşlemi --- [cite: 20]
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['slider_ekle'])) {
    $baslik = htmlspecialchars($_POST['baslik']);
    $aciklama = htmlspecialchars($_POST['aciklama']);
    $link = htmlspecialchars($_POST['link']);
    $sira = intval($_POST['sira']);
    
    // Görsel İşleme
    if (isset($_FILES['resim']) && $_FILES['resim']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['resim']['name'], PATHINFO_EXTENSION));
        $resim_yolu = "uploads/" . time() . "_" . rand(100,999) . "." . $ext;
        move_uploaded_file($_FILES['resim']['tmp_name'], $resim_yolu);
    } else {
        $resim_yolu = $_POST['resim_link'];
    }

    // [Yönerge Madde 3]: Prepared Statement [cite: 6, 7]
    $stmt = $baglanti->prepare("INSERT INTO slider (baslik, aciklama, resim, link, sira) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $baslik, $aciklama, $resim_yolu, $link, $sira);
    
    if ($stmt->execute()) {
        logTut($baglanti, "Yeni Slider Eklendi", "Başlık: $baslik");
        $mesaj = "Slider başarıyla eklendi!";
    }
}

$sliderlar = $baglanti->query("SELECT * FROM slider ORDER BY sira ASC");
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Slider Yönetimi | FRAME 25 CMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <style>
        /* BOYUT UYUMSUZLUĞUNU BİTİREN GLOBAL RESET KALIPI */
        * { box-sizing: border-box; } 
        body { font-family: 'Montserrat', sans-serif; background: #050505; color: #fff; margin: 0; }
        
        /* HEADER BOYUTU VE HİZALAMASI */
        header { background: #000; padding: 20px 40px; border-bottom: 3px solid #b20710; display: flex; justify-content: space-between; align-items: center; position: sticky; top:0; z-index:100;}
        header h1 { margin: 0; font-family: 'Bebas Neue'; color: #b20710; font-size: 2.5em; letter-spacing: 1px;}
        
        .container { display: flex; }
        
        /* SIDEBAR GENİŞLİK VE BOYUT EŞİTLEMESİ */
        aside { width: 220px; background: #111; min-height: 100vh; padding: 20px 0; border-right: 1px solid #222; }
        aside a { display: block; padding: 15px 25px; color: #888; text-decoration: none; font-weight: bold; font-size: 12px; text-transform: uppercase; transition: 0.3s;}
        aside a:hover, aside a.active { color: #fff; background: #b20710; border-right: 4px solid #fff;}
        
        main { flex: 1; padding: 40px; }
        
        /* FORM KONTEYNER BOYUTLARI VE YAPISI */
        .form-container { background: #111; padding: 30px; border-radius: 8px; border: 1px solid #222; margin-bottom: 40px; border-top: 4px solid #b20710; }
        .form-container h2 { font-family: 'Bebas Neue'; font-size: 2em; margin-top: 0; border-bottom: 1px solid #222; padding-bottom: 15px;}
        .row { display: flex; gap: 20px; } 
        .input-group { flex: 1; margin-bottom: 15px; }
        
        label { display: block; font-size: 11px; color: #888; font-weight: bold; text-transform: uppercase; margin-bottom: 8px; }
        input[type="text"], input[type="number"] { width: 100%; padding: 12px; background: #000; border: 1px solid #333; color: #fff; border-radius: 4px; font-family: inherit; outline: none; transition: 0.3s;}
        input:focus { border-color: #b20710; }
        input[type="file"] { background: #1a1a1a; padding: 9px; cursor: pointer; margin-bottom: 10px; width: 100%; color: #fff; border: 1px solid #333;}
        
        .btn-add { background: #00e054; color: #000; border: none; padding: 15px; font-weight: bold; width: 100%; cursor: pointer; text-transform: uppercase; border-radius: 4px; transition: 0.3s; margin-top: 15px; font-size: 12px;}
        .btn-add:hover { background: #fff; }
        
        /* TABLO BOYUTLARI VE YAZI TİPLERİ */
        .table-header { display: flex; justify-content: space-between; align-items: center; margin-top: 40px; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; background: #111; border-radius: 4px; overflow: hidden; margin-bottom: 40px;}
        th, td { padding: 15px; border-bottom: 1px solid #222; text-align: left; font-size: 13px; }
        th { font-family: 'Bebas Neue'; color: #b20710; font-size: 1.2em; letter-spacing: 1px; background: #0a0a0a;}
        tr:hover { background: #1a1a1a; }
        
        .slider-img { width: 80px; height: 45px; object-fit: cover; border-radius: 4px; border: 1px solid #333; vertical-align: middle;}
        .msg { background: rgba(0, 224, 84, 0.1); color: #00e054; padding: 15px; border: 1px solid #00e054; margin-bottom: 30px; border-radius: 4px; font-weight: bold; text-align: center; font-size: 12px;}
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
    <a href="admin_slider.php" class="active"><i class="fas fa-sliders-h" style="margin-right:8px;"></i> Slider Yönetimi</a>
    <a href="admin_ayarlar.php"><i class="fas fa-cog" style="margin-right:8px;"></i> Site Ayarları</a>
    <a href="admin_logs.php"><i class="fas fa-history" style="margin-right:8px;"></i> Sistem Günlükleri</a>
    <a href="admin_oyuncu.php"><i class="fas fa-users" style="margin-right:8px;"></i> Kadro Yönetimi</a>
    <a href="profile.php"><i class="fas fa-user" style="margin-right:8px;"></i> Profilime Dön</a>
    <a href="logout.php" style="color:#b20710;"><i class="fas fa-sign-out-alt" style="margin-right:8px;"></i> Güvenli Çıkış</a>
</aside>

    <main>
        <?php if($mesaj): ?><div id="adminMsg" class="msg"><?php echo htmlspecialchars($mesaj); ?></div><script>setTimeout(()=>document.getElementById('adminMsg').style.display='none', 3000);</script><?php endif; ?>

        <div class="form-container">
            <h2>YENİ SLIDER KARESİ EKLE</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="input-group">
                        <label>Slider Başlığı</label>
                        <input type="text" name="baslik" placeholder="Örn: Haftanın Seçkisi" required>
                    </div>
                    <div class="input-group">
                        <label>Açıklama / Alt Metin</label>
                        <input type="text" name="aciklama" placeholder="Örn: Koleksiyonu Gör">
                    </div>
                </div>
                
                <div class="row">
                    <div class="input-group">
                        <label>Yönlendirme Linki (URL)</label>
                        <input type="text" name="link" placeholder="index.php?kategori=dram">
                    </div>
                    <div class="input-group" style="max-width: 150px;">
                        <label>Sıra No</label>
                        <input type="number" name="sira" value="1">
                    </div>
                </div>
                
                <div class="input-group" style="background: #0a0a0a; padding: 15px; border: 1px dashed #333; border-radius: 4px; margin-bottom: 15px;">
                    <label>Görsel Seç (Dosya VEYA Link Gir)</label>
                    <input type="file" name="resim" accept="image/*">
                    <input type="text" name="resim_link" placeholder="Fotoğraf linkini yapıştırın" style="margin-bottom:0;">
                </div>
                
                <button type="submit" name="slider_ekle" class="btn-add">SLIDER'A EKLE</button>
            </form>
        </div>

        <div class="table-header">
            <h3 style="margin:0; font-family:'Bebas Neue'; font-size:2em; color:#fff;">SİSTEMDEKİ SLIDERLAR</h3>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>GÖRSEL</th>
                    <th>SIRA</th>
                    <th>BAŞLIK</th>
                    <th style="text-align:right;">İŞLEMLER</th>
                </tr>
            </thead>
            <tbody>
                <?php while($s = $sliderlar->fetch_assoc()): ?>
                <tr>
                    <td><img src="<?php echo htmlspecialchars($s['resim']); ?>" class="slider-img"></td>
                    <td style="color:#666;"><b>#<?php echo $s['sira']; ?></b></td>
                    <td><strong><?php echo htmlspecialchars($s['baslik']); ?></strong></td>
                    <td style="text-align:right;">
                        <a href="admin_slider.php?del_slider=<?php echo $s['id']; ?>" class="btn-del" onclick="return confirm('Silmek istediğine emin misin?')">SİL</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </main>
</div>

</body>
</html>