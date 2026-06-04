<?php
session_start();
include 'baglan.php';

// [Yönerge Madde 9]: Güvenlik kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'Super Admin') { 
    header("Location: index.php"); 
    exit(); 
}

$benim_id = $_SESSION['user_id'];
$admin_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : "Admin";

// --- SİDEBARIN SENİN SÜPER ADMIN OLDUĞUNU ANLAMASI İÇİN KULLANICIYI ÇEKİYORUZ ---
$user_sorgu = $baglanti->prepare("SELECT * FROM kullanicilar WHERE id = ?");
$user_sorgu->bind_param("i", $benim_id);
$user_sorgu->execute();
$db_user = $user_sorgu->get_result()->fetch_assoc();

// Diğer sayfalarla uyum için hem session'ı hem dilersen diğer değişkenleri senkronize ediyoruz
$_SESSION['rol'] = $db_user['rol'];

// [Yönerge Madde 18]: Logları kullanıcı adlarıyla birlikte çekiyoruz 
$logs = $baglanti->query("SELECT l.*, k.ad_soyad FROM sistem_loglari l LEFT JOIN kullanicilar k ON l.kullanici_id = k.id ORDER BY l.tarih DESC LIMIT 100");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sistem Günlükleri | FRAME 25</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        /* admin.php'deki orijinal stillerinle birebir uyumlu hale getirildi */
        * { box-sizing: border-box; } 
        body { font-family: 'Montserrat', sans-serif; background: #050505; color: #fff; margin: 0; }
        header { background: #000; padding: 20px 40px; border-bottom: 3px solid #b20710; display: flex; justify-content: space-between; align-items: center; position: sticky; top:0; z-index:100;}
        header h1 { margin: 0; font-family: 'Bebas Neue'; color: #b20710; font-size: 2.5em; letter-spacing: 1px;}
        
        .container { display: flex; }
        aside { width: 220px; background: #111; min-height: 100vh; padding: 20px 0; border-right: 1px solid #222; position: sticky; top: 80px; height: calc(100vh - 80px); }
        aside a { display: block; padding: 15px 25px; color: #888; text-decoration: none; font-weight: bold; font-size: 12px; text-transform: uppercase; transition: 0.3s;}
        aside a:hover { color: #fff; background: #b20710; }
        aside a.active { color: #fff; background: #b20710; border-right: 4px solid #fff;}
        
        main { flex: 1; padding: 40px; }
        .section-title { font-family: 'Bebas Neue'; font-size: 2.5em; color: #fff; margin-bottom: 25px; border-bottom: 1px solid #222; padding-bottom: 10px; display: flex; align-items: center; gap: 15px; }
        .section-title i { color: #b20710; }

        table { width: 100%; border-collapse: collapse; background: #111; border-radius: 4px; overflow: hidden; }
        th, td { padding: 15px; border-bottom: 1px solid #222; text-align: left; font-size: 13px; }
        th { font-family: 'Bebas Neue'; color: #b20710; font-size: 1.2em; letter-spacing: 1px; background: #0a0a0a; text-transform: uppercase; }
        tr:hover { background: #1a1a1a; }
        
        .badge-islem { background: rgba(0, 224, 84, 0.1); color: #00e054; padding: 4px 8px; border-radius: 3px; font-weight: bold; font-size: 11px; text-transform: uppercase; }
        .ip-text { color: #555; font-family: monospace; font-size: 11px; }
        .date-text { color: #888; font-weight: 500; }
    </style>
</head>
<body>

<header>
    <h1>FRAME 25 <small style="color:#555; font-size:0.4em">CMS</small></h1>
    <div style="display:flex; gap:20px; align-items:center;">
        <span style="color:#888; font-size:12px; font-weight:bold;">YÖNETİCİ <?php echo strtoupper($admin_name); ?></span>
        <a href="index.php" style="color:#fff; text-decoration:none; font-weight:bold; font-size: 12px; background:#b20710; padding:8px 15px; border-radius:3px;">SİTEYİ GÖR</a>
    </div>
</header>

<div class="container">
<aside>
    <a href="admin.php"><i class="fas fa-database" style="margin-right:8px;"></i> İçerik Paneli</a>
    <a href="admin_slider.php"><i class="fas fa-sliders-h" style="margin-right:8px;"></i> Slider Yönetimi</a>
    <a href="admin_ayarlar.php"><i class="fas fa-cog" style="margin-right:8px;"></i> Site Ayarları</a>
    <a href="admin_logs.php" class="active"><i class="fas fa-history" style="margin-right:8px;"></i> Sistem Günlükleri</a>
    <a href="admin_oyuncu.php"><i class="fas fa-users" style="margin-right:8px;"></i> Kadro Yönetimi</a>
    <a href="profile.php"><i class="fas fa-user" style="margin-right:8px;"></i> Profilime Dön</a>
    <a href="logout.php" style="color:#b20710;"><i class="fas fa-sign-out-alt" style="margin-right:8px;"></i> Güvenli Çıkış</a>
</aside>

    <main>
        <div class="section-title">
            <i class="fas fa-stream"></i> SİSTEM HAREKETLERİ
        </div>

        <table>
            <thead>
                <tr>
                    <th>Tarih / Saat</th>
                    <th>Kullanıcı</th>
                    <th>İşlem</th>
                    <th>Açıklama / Detay</th>
                    <th>IP Adresi</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $logs->fetch_assoc()): ?>
                <tr>
                    <td class="date-text"><?php echo date("d.m.Y H:i", strtotime($row['tarih'])); ?></td>
                    <td>
                        <i class="fas fa-user-circle" style="color:#333; margin-right:5px;"></i>
                        <?php echo $row['ad_soyad'] ? htmlspecialchars($row['ad_soyad']) : '<span style="color:#444">Ziyaretçi</span>'; ?>
                    </td>
                    <td><span class="badge-islem"><?php echo htmlspecialchars($row['islem']); ?></span></td>
                    <td><?php echo htmlspecialchars($row['detay']); ?></td>
                    <td class="ip-text"><?php echo $row['ip_adresi']; ?></td>
                </tr>
                <?php endwhile; ?>
                <?php if($logs->num_rows == 0): ?>
                <tr>
                    <td colspan="5" style="text-align:center; padding:50px; color:#555;">Henüz bir sistem hareketi kaydedilmedi.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
</div>

</body>
</html>