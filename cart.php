<?php
// Oturumu başlatıyoruz
session_start();

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 1);
include 'baglan.php';

// Yönerge Madde 9: Sipariş verebilmek veya sepeti görmek için giriş yapmış olmalı
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$site_adi = "FRAME 25";
$slogan = "pure cinema experience";

// --- VERİTABANI SEPET İŞLEMLERİ ---

// 1. Sepetten Ürün Çıkarma İşlemi (Veritabanından Siler)
if (isset($_GET['action']) && $_GET['action'] == 'remove' && isset($_GET['id'])) {
    $urun_id = intval($_GET['id']);
    // [Yönerge Madde 3]: Prepared Statement Kullanıldı
    $del_stmt = $baglanti->prepare("DELETE FROM sepet WHERE user_id = ? AND film_id = ?");
    $del_stmt->bind_param("ii", $user_id, $urun_id);
    $del_stmt->execute();
    header("Location: cart.php"); 
    exit();
}

// --- VERİTABANINDAKİ SEPETTEKİ ÜRÜNLERİ ÇEKME ---
$cartItems = [];
$araToplam = 0;

// Giriş yapan kullanıcının sepetindeki filmleri JOIN ile tek hamlede çekiyoruz
$cart_stmt = $baglanti->prepare("SELECT f.id, f.title, f.dir, f.price, f.img, s.adet FROM sepet s JOIN filmler f ON s.film_id = f.id WHERE s.user_id = ?");
$cart_stmt->bind_param("i", $user_id);
$cart_stmt->execute();
$sonuc = $cart_stmt->get_result();

if ($sonuc && $sonuc->num_rows > 0) {
    while($row = $sonuc->fetch_assoc()) { 
        $cartItems[] = $row; 
        // Fiyatı adet miktarıyla çarparak ara toplama ekliyoruz
        $araToplam += ($row['price'] * $row['adet']); 
    }
}

$kdvOrani = 0.20; 
$kdvTutari = $araToplam * $kdvOrani; 
$genelToplam = $araToplam + $kdvTutari;

// 2. Siparişi Tamamlama (Veritabanı Kayıtlı - Madde 20)
if (isset($_GET['action']) && $_GET['action'] == 'checkout' && !empty($cartItems)) {
    // [Yönerge Madde 20]: Siparişi veritabanına kalıcı olarak işliyoruz
    $not = "Dijital Teslimat";
    $stmt = $baglanti->prepare("INSERT INTO siparisler (user_id, toplam_tutar, siparis_notu, durum) VALUES (?, ?, ?, 'Tamamlandı')");
    $stmt->bind_param("ids", $user_id, $genelToplam, $not);
    
    if ($stmt->execute()) {
        // Sipariş tamamlandığı için kullanıcının sepetini veritabanından tamamen temizliyoruz
        $clear_stmt = $baglanti->prepare("DELETE FROM sepet WHERE user_id = ?");
        $clear_stmt->prepare("DELETE FROM sepet WHERE user_id = ?");
        $clear_stmt->bind_param("i", $user_id);
        $clear_stmt->execute();
        
        $odeme_mesaji = "Ödemeniz başarıyla alındı! Sipariş ID: #" . $baglanti->insert_id;
        
        // Sipariş sonrası yerel değişkenleri sıfırla ki arayüz temizlensin
        $cartItems = []; $araToplam = 0; $genelToplam = 0; $kdvTutari = 0;
    } else {
        $odeme_hata = "Sipariş işlenirken bir teknik hata oluştu.";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sepetim | <?php echo htmlspecialchars($site_adi); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@400;500;700&display=swap" rel="stylesheet">
    <script>
        const savedTheme = localStorage.getItem('theme') || 'dark';
        if (savedTheme === 'light') { document.documentElement.setAttribute('data-theme', 'light'); }
    </script>
    <style>
        :root { --bg-color: #050505; --header-bg: #000000; --nav-bg: #111111; --card-bg: #111111; --text-main: #ffffff; --text-muted: #888888; --border-color: #222222; --accent: #b20710; }
        [data-theme="light"] { --bg-color: #f5f2eb; --header-bg: #e8e4d9; --nav-bg: #efede7; --card-bg: #ffffff; --text-main: #1a1a1a; --text-muted: #555555; --border-color: #dcd7ca; }
        
        /* GLOBAL RESET: ALT ÇİZGİLERİ KÖKTEN SİLME */
        * { box-sizing: border-box; } 
        body { font-family: 'Montserrat', sans-serif; background-color: var(--bg-color); color: var(--text-main); margin: 0; padding: 0; line-height: 1.5; transition: 0.3s; }
        a { text-decoration: none; color: inherit; }
        
        /* HEADER KAYMASINI ENGELLEYEN MODERN SİMETRİK YAPI */
        header { background-color: var(--header-bg); padding: 40px 0; text-align: center; border-bottom: 4px solid var(--accent); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 5px; transition: 0.3s; }
        header h1 { margin: 0; font-family: 'Bebas Neue', cursive; font-size: 4.5em; color: var(--accent); letter-spacing: 5px; text-shadow: 2px 2px 4px rgba(0,0,0,0.3); line-height: 1; }
        header p { color: var(--text-muted); font-size: 11px; text-transform: uppercase; letter-spacing: 10px; margin: 0; }
        
        nav { background-color: var(--nav-bg); border-bottom: 1px solid var(--border-color); width: 100%; height: 60px; position: sticky; top: 0; z-index: 1000; transition: 0.3s; }
        .nav-wrapper { width: 90%; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; height: 100%; }
        nav ul { display: flex; height: 100%; list-style: none; padding: 0; margin: 0; }
        nav li a { display: block; padding: 20px 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; color: var(--text-muted); text-decoration: none; transition: 0.3s; }
        nav li a:hover, nav li a.active { color: #fff; background-color: var(--accent); }
        
        .theme-btn { background: none; border: 1px solid var(--border-color); color: var(--text-main); font-size: 13px; cursor: pointer; padding: 6px 10px; border-radius: 50%; transition: 0.3s; display: flex; align-items: center; justify-content: center; width:32px; height:32px;}
        .theme-btn:hover { background-color: var(--accent); color: #fff; border-color: var(--accent); }
        
        .checkout-steps { display: flex; justify-content: center; align-items: center; margin: 30px auto; width: 90%; max-width: 800px; padding: 20px 0; border-bottom: 1px solid var(--border-color); }
        .step { display: flex; align-items: center; color: var(--text-muted); font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        .step.active { color: var(--accent); }
        .step-divider { margin: 0 20px; width: 50px; height: 2px; background-color: var(--border-color); }
        
        .container { width: 90%; max-width: 1200px; margin: 20px auto 60px; display: flex; gap: 40px;}
        .cart-left { width: 65%; }
        .cart-right { width: 35%; flex-shrink: 0; }
        
        .section-head { font-family: 'Bebas Neue'; font-size: 2.2em; border-left: 6px solid var(--accent); padding-left: 15px; margin-bottom: 25px; margin-top: 0; color: var(--text-main); text-transform: uppercase; }
        .cart-item { background-color: var(--card-bg); padding: 15px; margin-bottom: 15px; border: 1px solid var(--border-color); border-radius: 5px; transition: 0.3s; display: flex; align-items: center; justify-content: space-between; position: relative; overflow: hidden;}
        .item-main-info { display: flex; align-items: center; width: 70%;}
        .item-img-area { width: 80px; min-width: 80px; margin-right: 20px; }
        .item-img-area img { width: 100%; height: 110px; border-radius: 3px; object-fit: cover; border: 1px solid var(--border-color); }
        .item-details h4 { font-family: 'Montserrat'; font-size: 14px; margin: 0 0 5px 0; color: var(--text-main); font-weight: bold;}
        .format-badge { display: inline-block; background: rgba(255,255,255,0.05); border: 1px solid var(--border-color); color: var(--text-muted); font-size: 9px; padding: 3px 6px; border-radius: 3px; font-weight: 500; letter-spacing: 0.5px;}
        .item-price-area { text-align: right; width: 30%;}
        .item-price { font-weight: bold; font-size: 16px; display: block; margin-bottom: 10px; color: var(--text-main); }
        
        .btn-remove { background: none; border: 1px solid var(--border-color); color: var(--text-muted); font-weight: bold; font-size: 9px; cursor: pointer; text-transform: uppercase; padding: 6px 12px; border-radius: 3px; transition: 0.3s; text-decoration: none;}
        .btn-remove:hover { border-color: var(--accent); color: var(--accent); }
        
        .summary-box { background-color: var(--card-bg); padding: 30px; border: 1px solid var(--border-color); border-radius: 5px; transition: 0.3s; position: sticky; top: 90px;}
        .summary-box h3 { font-family: 'Bebas Neue'; font-size: 1.8em; margin-top: 0; border-bottom: 2px solid var(--accent); padding-bottom: 15px; margin-bottom: 25px; color: var(--text-main); letter-spacing: 1px;}
        .calc-row { margin-bottom: 15px; font-size: 12px; color: var(--text-muted); display: flex; justify-content: space-between;}
        .total-price { margin-top: 25px; padding-top: 20px; border-top: 1px solid var(--border-color); font-size: 1.4em; font-weight: bold; color: var(--text-main); display: flex; justify-content: space-between; align-items: center;}
        .total-price span { color: var(--accent); font-size: 1.2em; }
        
        .btn-pay { display: block; text-align: center; width: 100%; padding: 16px; background-color: var(--accent); color: white; border: none; font-weight: bold; text-transform: uppercase; font-size: 12px; letter-spacing: 1px; margin-top: 25px; cursor: pointer; transition: 0.3s; border-radius: 3px; text-decoration: none;}
        .btn-pay:hover { background-color: #fff; color: #000; }
        
        .success-msg { background-color: rgba(0, 224, 84, 0.05); border: 1px solid rgba(0, 224, 84, 0.2); border-left: 5px solid #00e054; color: #00e054; padding: 20px; margin-bottom: 30px; font-weight: 500; font-size: 12px; border-radius: 3px; display: flex; align-items: center;}
        .error-msg { background-color: rgba(178,7,16,0.05); border: 1px solid rgba(178,7,16,0.2); border-left: 5px solid var(--accent); color: var(--accent); padding: 20px; margin-bottom: 30px; font-size: 12px; border-radius: 3px;}
        footer { background-color: var(--header-bg); padding: 40px 0; text-align: center; border-top: 1px solid var(--border-color); color: var(--text-muted); font-size: 11px; margin-top: 60px; }
    </style>
</head>
<body>

<header>
    <h1><?php echo htmlspecialchars($site_adi); ?></h1>
    <p><?php echo htmlspecialchars($slogan); ?></p>
</header>

<nav>
    <div class="nav-wrapper">
        <ul>
            <li><a href="index.php">Ana Sayfa</a></li>
            <li><a href="kesfet.php">Keşfet</a></li>
            <li><a href="cart.php" class="active">Sepetim</a></li>
            <li><a href="profile.php">Profilim</a></li>
        </ul>
        <button class="theme-btn" id="theme-toggle"><i class="fas fa-moon"></i></button>
    </div>
</nav>

<div class="checkout-steps">
    <div class="step active"><i class="fas fa-shopping-bag"></i> 1. Sepetim</div>
    <div class="step-divider"></div>
    <div class="step <?php echo isset($odeme_mesaji) ? 'active' : ''; ?>"><i class="fas fa-credit-card"></i> 2. Ödeme</div>
    <div class="step-divider"></div>
    <div class="step <?php echo isset($odeme_mesaji) ? 'active' : ''; ?>"><i class="fas fa-check-circle"></i> 3. Onay</div>
</div>

<div class="container">
    <div class="cart-left">
        <?php if(isset($odeme_mesaji)): ?>
            <div class="success-msg"><i class="fas fa-check-circle" style="margin-right:15px;"></i> <?php echo htmlspecialchars($odeme_mesaji); ?></div>
        <?php endif; ?>
        
        <?php if(isset($odeme_hata)): ?>
            <div class="error-msg"><i class="fas fa-exclamation-triangle" style="margin-right:15px;"></i> <?php echo htmlspecialchars($odeme_hata); ?></div>
        <?php endif; ?>

        <h2 class="section-head">Alışveriş Sepeti</h2>
        
        <?php if(empty($cartItems)): ?>
            <div style="padding:60px; text-align:center; border: 1px dashed var(--border-color); background: var(--card-bg);">
                <i class="fas fa-film" style="font-size: 2em; color: var(--text-muted); margin-bottom:20px;"></i>
                <h3 style="font-family:'Bebas Neue'; font-size:1.8em;">SEPETİNİZ ŞU AN BOŞ</h3>
                <a href="kesfet.php" style="background: var(--accent); color: #fff; padding: 10px 20px; display:inline-block; margin-top:20px; font-size:11px; font-weight:bold; text-decoration:none;">ARŞİVE GÖZ AT</a>
            </div>
        <?php else: ?>
            <?php foreach($cartItems as $urun): ?>
                <div class="cart-item">
                    <div class="item-main-info">
                        <div class="item-img-area"><img src="<?php echo htmlspecialchars($urun['img']); ?>"></div>
                        <div class="item-details">
                            <h4><?php echo htmlspecialchars($urun['title']); ?></h4>
                            <p><i class="fas fa-video"></i> <?php echo htmlspecialchars($urun['dir']); ?></p>
                            <span class="format-badge">4K DİJİTAL KOPYA <?php echo $urun['adet'] > 1 ? '('.$urun['adet'].' ADET)' : ''; ?></span>
                        </div>
                    </div>
                    <div class="item-price-area">
                        <span class="item-price"><?php echo number_format(($urun['price'] * $urun['adet']), 2, ',', '.'); ?> TL</span>
                        <a href="cart.php?action=remove&id=<?php echo $urun['id']; ?>" class="btn-remove">Çıkar</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="cart-right">
        <div class="summary-box">
            <h3>SİPARİŞ ÖZETİ</h3>
            <div class="calc-row"><span>Ara Toplam</span> <span><?php echo number_format($araToplam, 2, ',', '.'); ?> TL</span></div>
            <div class="calc-row"><span>KDV (%20)</span> <span><?php echo number_format($kdvTutari, 2, ',', '.'); ?> TL</span></div>
            <div class="total-price"><span>TOPLAM</span> <span><?php echo number_format($genelToplam, 2, ',', '.'); ?> TL</span></div>
            
            <?php if(!empty($cartItems)): ?>
                <a href="cart.php?action=checkout" class="btn-pay">ÖDEMEYİ TAMAMLA</a>
            <?php else: ?>
                <button class="btn-pay" style="opacity: 0.5; cursor: not-allowed;" disabled>ÖDEMEYİ TAMAMLA</button>
            <?php endif; ?>
        </div>
    </div>
</div>

<footer><p>&copy; 2026 Frame 25 | Selin Avcı Tarafından Yapılmıştır.</p></footer>

<script>
    const toggleBtn = document.getElementById('theme-toggle');
    if (localStorage.getItem('theme') === 'light' && toggleBtn) { toggleBtn.innerHTML = '<i class="fas fa-sun"></i>'; }
    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            let currentTheme = document.documentElement.getAttribute('data-theme');
            if (currentTheme === 'light') {
                document.documentElement.removeAttribute('data-theme'); localStorage.setItem('theme', 'dark'); toggleBtn.innerHTML = '<i class="fas fa-moon"></i>';
            } else {
                document.documentElement.setAttribute('data-theme', 'light'); localStorage.setItem('theme', 'light'); toggleBtn.innerHTML = '<i class="fas fa-sun"></i>';
            }
        });
    }
</script>
</body>
</html>