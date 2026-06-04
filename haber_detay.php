<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'baglan.php';

// 1. HABERİ VE KATEGORİSİNİ ÇEK
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "SELECT * FROM haberler WHERE id = ?";
    $stmt = mysqli_prepare($baglanti, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $sonuc = mysqli_stmt_get_result($stmt);
    $haber = $sonuc->fetch_assoc();
    if (!$haber) { header("Location: index.php"); exit(); }
} else { header("Location: index.php"); exit(); }

// 2. SAĞ PANEL: POPÜLER FİLMLER (Fiyatı en yüksek veya rastgele)
$populer_filmler = [];
$f_res = $baglanti->query("SELECT id, title, img, category, release_date FROM filmler ORDER BY RAND() LIMIT 4");
if($f_res) { while($row = $f_res->fetch_assoc()) { $populer_filmler[] = $row; } }

// 3. SAĞ PANEL: DİĞER SICAK GELİŞMELER
$sicak_gelismeler = [];
$h_res = $baglanti->query("SELECT id, baslik, resim FROM haberler WHERE id != $id ORDER BY id DESC LIMIT 3");
if($h_res) { while($row = $h_res->fetch_assoc()) { $sicak_gelismeler[] = $row; } }

$site_adi = "FRAME 25";
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($haber['baslik']); ?> | <?php echo $site_adi; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    
    <script>
        const savedTheme = localStorage.getItem('theme') || 'dark';
        if (savedTheme === 'light') { document.documentElement.setAttribute('data-theme', 'light'); }
    </script>

    <style>
        :root {
            --bg-color: #050505; --nav-bg: #000; --card-bg: #111; --text-main: #ffffff; 
            --text-muted: #888888; --border-color: #1a1a1a; --accent: #b20710; --glass: rgba(255,255,255,0.03);
        }
        [data-theme="light"] {
            --bg-color: #f5f2eb; --nav-bg: #e8e4d9; --card-bg: #fff; --text-main: #1a1a1a; 
            --text-muted: #555555; --border-color: #dcd7ca; --glass: rgba(0,0,0,0.05);
        }

        body { font-family: 'Montserrat', sans-serif; background-color: var(--bg-color); color: var(--text-main); margin: 0; transition: 0.4s ease; }
        
        /* NAV - GLASSMORPHISM */
        nav { background: var(--nav-bg); border-bottom: 1px solid var(--border-color); height: 70px; position: sticky; top: 0; z-index: 1000; backdrop-filter: blur(10px); }
        .nav-wrapper { width: 95%; max-width: 1300px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; height: 100%; }
        .nav-logo { font-family: 'Bebas Neue'; font-size: 28px; color: var(--accent); text-decoration: none; letter-spacing: 2px; }
        .nav-links { display: flex; gap: 25px; align-items: center; }
        .nav-links a { font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); letter-spacing: 1px; }
        .nav-links a:hover { color: var(--accent); }

        /* PORTAL GRID */
        .portal-container { width: 95%; max-width: 1300px; margin: 40px auto; display: grid; grid-template-columns: 240px 1fr 320px; gap: 50px; }

        /* SOL PANEL: KÜNYE & PAYLAŞIM */
        .sidebar-info { position: sticky; top: 110px; }
        .info-label { font-size: 10px; font-weight: 800; color: var(--accent); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 5px; display: block; }
        .info-val { font-size: 13px; color: var(--text-main); margin-bottom: 25px; display: block; font-weight: 600; }
        .share-group { margin-top: 30px; border-top: 1px solid var(--border-color); padding-top: 20px; }
        .share-circle { width: 45px; height: 45px; border-radius: 50%; background: var(--glass); border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; color: var(--text-muted); margin-bottom: 15px; cursor: pointer; transition: 0.3s; }
        .share-circle:hover { background: var(--accent); color: #fff; border-color: var(--accent); transform: rotate(15deg); }

        /* ORTA PANEL: EDİTÖRYAL İÇERİK */
        .main-article h1 { font-family: 'Bebas Neue'; font-size: 5.5em; line-height: 0.95; margin: 0 0 30px 0; letter-spacing: -1px; text-transform: uppercase; }
        .featured-wrap { position: relative; margin-bottom: 40px; border-radius: 12px; overflow: hidden; }
        .featured-wrap img { width: 100%; display: block; filter: brightness(0.9); }
        .img-caption { position: absolute; bottom: 0; left: 0; right: 0; padding: 20px; background: linear-gradient(transparent, rgba(0,0,0,0.8)); color: #fff; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        
        .article-body { font-size: 1.2em; line-height: 1.9; color: var(--text-main); font-weight: 400; text-align: justify; letter-spacing: -0.2px; }
        .article-body p::first-letter { float: left; font-size: 4em; line-height: 0.8; font-family: 'Bebas Neue'; color: var(--accent); margin-right: 10px; margin-top: 5px; }
        .article-body p { margin-bottom: 30px; opacity: 0.9; }

        /* SAĞ PANEL: WIDGETLAR */
        .sidebar-widgets { position: sticky; top: 110px; }
        .widget { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 25px; margin-bottom: 30px; }
        .widget-head { font-family: 'Bebas Neue'; font-size: 1.6em; color: var(--accent); margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        
        .trending-movie { display: flex; gap: 15px; margin-bottom: 20px; text-decoration: none; align-items: center; padding-bottom: 15px; border-bottom: 1px solid var(--glass); }
        .trending-movie:last-child { border: none; margin: 0; padding: 0; }
        .trending-movie img { width: 60px; height: 90px; object-fit: cover; border-radius: 6px; }
        .tm-info h4 { margin: 0; font-size: 14px; color: var(--text-main); line-height: 1.2; }
        .tm-info span { font-size: 11px; color: var(--text-muted); font-weight: bold; }

        /* ALT KISIM: BENZER HABERLER */
        .related-section { grid-column: 1 / -1; margin-top: 60px; border-top: 1px solid var(--border-color); padding-top: 60px; }
        .related-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; }
        .related-card { text-decoration: none; }
        .related-card img { width: 100%; height: 200px; object-fit: cover; border-radius: 8px; margin-bottom: 15px; transition: 0.3s; }
        .related-card:hover img { transform: scale(1.03); }
        .related-card h3 { font-family: 'Bebas Neue'; font-size: 1.8em; margin: 0; color: var(--text-main); line-height: 1.1; }

        footer { background: var(--nav-bg); padding: 80px 0; border-top: 1px solid var(--border-color); text-align: center; color: var(--text-muted); font-size: 12px; }

        /* TEMA TOGGLE */
        .theme-btn { background: var(--glass); border: 1px solid var(--border-color); color: var(--text-main); width: 35px; height: 35px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; }

        @media (max-width: 1100px) { .portal-container { grid-template-columns: 1fr 300px; } .sidebar-info { display: none; } }
        @media (max-width: 800px) { .portal-container { grid-template-columns: 1fr; } .sidebar-widgets { display: none; } .main-article h1 { font-size: 3.5em; } }
    </style>
</head>
<body>

<nav>
    <div class="nav-wrapper">
        <a href="index.php" class="nav-logo">FRAME 25</a>
        <div class="nav-links">
            <a href="index.php">SİNEMA</a>
            <a href="index.php">HABERLER</a>
            <a href="profile.php">PROFİL</a>
            <button class="theme-btn" id="theme-btn"><i class="fas fa-moon"></i></button>
        </div>
    </div>
</nav>

<div class="portal-container">
    
    <aside class="sidebar-info">
        <span class="info-label">KATEGORİ</span>
        <span class="info-val">SİNEMA DÜNYASI</span>
        
        <span class="info-label">YAYIN TARİHİ</span>
        <span class="info-val"><?php echo date("d F, Y", strtotime($haber['tarih'])); ?></span>
        
        <span class="info-label">OKUMA SÜRESİ</span>
        <span class="info-val">4 DAKİKA</span>

        <div class="share-group">
            <div class="share-circle" title="WhatsApp"><i class="fab fa-whatsapp"></i></div>
            <div class="share-circle" title="X / Twitter"><i class="fab fa-x-twitter"></i></div>
            <div class="share-circle" title="Bağlantıyı Kopyala"><i class="fas fa-link"></i></div>
        </div>
    </aside>

    <main class="main-article">
        <article>
            <h1><?php echo htmlspecialchars($haber['baslik']); ?></h1>
            
            <div class="featured-wrap">
                <img src="<?php echo htmlspecialchars($haber['resim']); ?>" alt="Banner">
                <div class="img-caption">F25 EXCLUSIVE: SİNEMANIN KALBİ BURADA ATIYOR</div>
            </div>
            
            <div class="article-body">
                <?php echo nl2br(htmlspecialchars($haber['icerik'])); ?>
            </div>
        </article>
    </main>

    <aside class="sidebar-widgets">
        
        <div class="widget">
            <div class="widget-head">TREND FİLMLER <i class="fas fa-fire"></i></div>
            <?php foreach($populer_filmler as $f): ?>
                <a href="detay.php?id=<?php echo $f['id']; ?>" class="trending-movie">
                    <img src="<?php echo htmlspecialchars($f['img']); ?>" alt="...">
                    <div class="tm-info">
                        <h4><?php echo htmlspecialchars($f['title']); ?></h4>
                        <span><?php echo htmlspecialchars($f['category']); ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="widget" style="background: var(--accent); border:none;">
            <div class="widget-head" style="color:#fff; border-bottom-color: rgba(255,255,255,0.2);">BÜLTEN <i class="fas fa-envelope"></i></div>
            <p style="font-size:12px; color:#eee; margin-bottom:15px;">Özel haberler ve film incelemeleri haftalık olarak e-postana gelsin.</p>
            <input type="email" placeholder="E-posta adresin..." style="width:100%; padding:10px; border-radius:4px; border:none; outline:none; font-size:12px;">
            <button style="width:100%; padding:10px; background:#000; color:#fff; border:none; border-radius:4px; margin-top:10px; font-weight:bold; font-size:11px; cursor:pointer;">ABONE OL</button>
        </div>

    </aside>

    <section class="related-section">
        <div class="widget-head" style="font-size:2.5em; margin-bottom:40px;">BENZER HABERLER <i class="fas fa-plus"></i></div>
        <div class="related-grid">
            <?php foreach($sicak_gelismeler as $sh): ?>
                <a href="haber_detay.php?id=<?php echo $sh['id']; ?>" class="related-card">
                    <img src="<?php echo htmlspecialchars($sh['resim']); ?>" alt="...">
                    <h3><?php echo htmlspecialchars($sh['baslik']); ?></h3>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

</div>

<footer>
    <div style="font-family:'Bebas Neue'; font-size:2em; color:var(--accent); margin-bottom:20px;">FRAME 25</div>
    <p>&copy; 2026 Selin Avcı Tarafından Sinema Sanatı İçin Tasarlanmıştır.</p>
</footer>

<script>
    const themeBtn = document.getElementById('theme-btn');
    if (localStorage.getItem('theme') === 'light') { themeBtn.innerHTML = '<i class="fas fa-sun"></i>'; }

    themeBtn.addEventListener('click', () => {
        let theme = document.documentElement.getAttribute('data-theme');
        if (theme === 'light') {
            document.documentElement.removeAttribute('data-theme');
            localStorage.setItem('theme', 'dark');
            themeBtn.innerHTML = '<i class="fas fa-moon"></i>';
        } else {
            document.documentElement.setAttribute('data-theme', 'light');
            localStorage.setItem('theme', 'light');
            themeBtn.innerHTML = '<i class="fas fa-sun"></i>';
        }
    });
</script>
</body>
</html>