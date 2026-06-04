<?php
session_start();
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 1);

// 1. Veritabanı ve Dil Ayarları
include 'baglan.php';

if (!isset($_SESSION['site_lang'])) { $_SESSION['site_lang'] = 'tr'; }
$current_lang = $_SESSION['site_lang'];

// index.php ile birebir uyumlu dil sözlüğü
$lang_dict = [
    'tr' => [
        'anasayfa' => 'Ana Sayfa', 'kesfet' => 'Keşfet', 'profilim' => 'Profilim', 'yonetim' => 'YÖNETİM',
        'gise' => 'Gişe Hasılatı', 'odul' => 'Ödüller & Festivaller', 'galeri' => 'FİLM GALERİSİ',
        'oyuncular' => 'OYUNCU KADROSU', 'yorumlar' => 'ELEŞTİRMEN YORUMLARI', 'sepet' => 'SEPETE EKLE',
        'izledim' => 'İzledim', 'begen' => 'Beğen', 'geri' => 'GERİ DÖN'
    ],
    'en' => [
        'anasayfa' => 'Home', 'kesfet' => 'Discover', 'profilim' => 'Profile', 'yonetim' => 'ADMIN',
        'gise' => 'Box Office', 'odul' => 'Awards & Festivals', 'galeri' => 'FILM GALLERY',
        'oyuncular' => 'CAST', 'yorumlar' => 'CRITIC REVIEWS', 'sepet' => 'ADD TO CART',
        'izledim' => 'Watched', 'begen' => 'Like', 'geri' => 'GO BACK'
    ],
    'fr' => [
        'anasayfa' => 'Accueil', 'kesfet' => 'Découvrir', 'profilim' => 'Profil', 'yonetim' => 'ADMIN',
        'gise' => 'Box Office', 'odul' => 'Prix et Festivals', 'galeri' => 'GALERIE DU FILM',
        'oyuncular' => 'CASTING', 'yorumlar' => 'CRITIQUES', 'sepet' => 'AJOUTER AU PANIER',
        'izledim' => 'Regardé', 'begen' => 'Aimer', 'geri' => 'RETOUR'
    ]
];
$txt = $lang_dict[$current_lang];

// --- [DERS NOTU: TMDB API FONKSİYONU] ---
function getTmdbData($tmdb_id, $endpoint = '') {
    if (!$tmdb_id || $tmdb_id == 0) return null;
    $api_key = "a28be6c73588cbc345d03f2bd9764d3a"; 
    $url = "https://api.themoviedb.org/3/movie/$tmdb_id" . ($endpoint ? "/$endpoint" : "") . "?api_key=$api_key&language=" . ($_SESSION['site_lang'] == 'tr' ? 'tr-TR' : 'en-US');
    $json = @file_get_contents($url);
    return $json ? json_decode($json, true) : null;
}

// Film Bilgilerini Çek
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // [Yönerge Madde 3]: Prepared Statement ile Veri Çekme
    $stmt = $baglanti->prepare("SELECT * FROM filmler WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $film = $stmt->get_result()->fetch_assoc();
    
    if (!$film) { header("Location: index.php"); exit(); }
    $baglanti->query("UPDATE filmler SET view_count = view_count + 1 WHERE id = $id");

    // --- FONKSİYONEL EKLEME: İZLEDİM / BEĞEN BUTONLARININ ARKA PLAN MANTIĞI ---
    if (isset($_SESSION['user_id']) && isset($_GET['islem'])) {
        $user_id = $_SESSION['user_id'];
        $islem = $_GET['islem'];
        
        if ($islem == 'izledim') {
            // İzlenenler tablosuna ekle (Mükerrer kaydı önlemek için INSERT IGNORE)
            $ins_stmt = $baglanti->prepare("INSERT IGNORE INTO izlenenler (user_id, film_id) VALUES (?, ?)");
            $ins_stmt->bind_param("ii", $user_id, $id);
            $ins_stmt->execute();
        } elseif ($islem == 'begen') {
            // Begenilenler (Klaket) tablosuna ekle
            $ins_stmt = $baglanti->prepare("INSERT IGNORE INTO begenilenler (user_id, film_id) VALUES (?, ?)");
            $ins_stmt->bind_param("ii", $user_id, $id);
            $ins_stmt->execute();
        }
        header("Location: detay.php?id=" . $id);
        exit();
    }

    // API Verilerini Çek
    $tmdb_images = getTmdbData($film['tmdb_id'], 'images');
    $tmdb_reviews = getTmdbData($film['tmdb_id'], 'reviews');
} else { header("Location: index.php"); exit(); }
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($film['title']); ?> | FRAME 25</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    
    <script>
        const savedTheme = localStorage.getItem('theme') || 'dark';
        if (savedTheme === 'light') { document.documentElement.setAttribute('data-theme', 'light'); }
    </script>

    <style>
        :root { --bg-color: #050505; --header-bg: #000000; --nav-bg: #111111; --card-bg: #161a1e; --text-main: #ffffff; --text-muted: #888888; --border-color: #2c3440; --accent: #b20710; }
        [data-theme="light"] { --bg-color: #f5f2eb; --header-bg: #e8e4d9; --nav-bg: #efede7; --card-bg: #ffffff; --text-main: #1a1a1a; --text-muted: #555555; --border-color: #dcd7ca; }
        
        * { box-sizing: border-box; }
        body { font-family: 'Montserrat', sans-serif; background-color: var(--bg-color); color: var(--text-main); margin: 0; line-height: 1.6; transition: 0.3s; }
        
        /* HEADER KAYMASINI ENGELLEYEN MODERN YAPI */
        header { background-color: var(--header-bg); padding: 40px 0; text-align: center; border-bottom: 4px solid var(--accent); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 5px; }
        header h1 { margin: 0; font-family: 'Bebas Neue'; font-size: 4.5em; color: var(--accent); letter-spacing: 5px; line-height: 1; }
        
        nav { background-color: var(--nav-bg); border-bottom: 1px solid var(--border-color); height: 60px; position: sticky; top: 0; z-index: 1000; }
        .nav-wrapper { width: 90%; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; height: 100%; }
        nav ul { display: flex; list-style: none; padding: 0; margin: 0; height: 100%; }
        nav li { height: 100%; }
        /* LİNK HİZALAMASI DÜZELTİLDİ */
        nav li a { display: block; padding: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; color: var(--text-muted); text-decoration: none; transition: 0.3s; }
        nav li a:hover, nav li a.active { color: #fff; background-color: var(--accent); }
        
        .container { width: 90%; max-width: 1200px; margin: 40px auto; display: flex; gap: 40px; }
        .poster-area { width: 250px; flex-shrink: 0; }
        .poster-area img { width: 100%; border-radius: 4px; border: 1px solid var(--border-color); box-shadow: 0 4px 15px rgba(0,0,0,0.5); }
        
        .film-title { font-family: 'Bebas Neue'; font-size: 3.5em; margin: 0; line-height: 0.9; color: var(--text-main); }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 25px 0; }
        .info-box { background: var(--card-bg); padding: 15px; border-radius: 6px; border: 1px solid var(--border-color); border-top: 2px solid var(--accent); }
        .info-box h4 { margin: 0 0 5px 0; font-family: 'Bebas Neue'; color: var(--accent); font-size: 14px; letter-spacing: 0.5px; }
        
        .gallery-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-top: 20px; }
        .gallery-item { height: 110px; border-radius: 4px; overflow: hidden; border: 1px solid var(--border-color); }
        .gallery-item img { width: 100%; height: 100%; object-fit: cover; transition: 0.3s; cursor: pointer; }
        .gallery-item img:hover { transform: scale(1.05); filter: brightness(1.1); }
        
        /* EZİLMEYİ ENGELLEYEN GÜÇLENDİRİLMİŞ SAĞ PANEL */
        .action-panel { width: 240px; background: var(--card-bg); padding: 20px; border-radius: 6px; border: 1px solid var(--border-color); height: fit-content; flex-shrink: 0; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        .price-tag { font-size: 26px; font-weight: 700; color: #00e054; text-align: center; font-family: 'Bebas Neue'; margin-bottom: 15px; letter-spacing: 0.5px; }
        .btn-buy { display: block; width: 100%; padding: 14px; background: #b20710; color: #fff; text-align: center; font-weight: bold; border-radius: 4px; font-size: 11px; text-decoration: none; transition: 0.3s; text-transform: uppercase; letter-spacing: 0.5px; }
        .btn-buy:hover { background: #fff; color: #000; }
        
        .review-item { font-size: 11px; color: var(--text-muted); font-style: italic; background: var(--card-bg); padding: 15px; border-radius: 6px; margin-bottom: 10px; border-left: 3px solid var(--accent); }
    </style>
</head>
<body>

<header>
    <h1>FRAME 25</h1>
    <p style="color:var(--text-muted); font-size:11px; text-transform:uppercase; letter-spacing:10px; margin: 0;">pure cinema experience</p>
</header>

<nav>
    <div class="nav-wrapper">
        <ul>
            <li><a href="index.php"><?php echo $txt['anasayfa']; ?></a></li>
            <li><a href="kesfet.php"><?php echo $txt['kesfet']; ?></a></li>
            <li><a href="profile.php"><?php echo $txt['profilim']; ?></a></li>
            <li><a href="admin.php" style="color:var(--accent); font-weight:bold;"><?php echo $txt['yonetim']; ?></a></li>
        </ul>
        <div style="display:flex; align-items:center; gap:20px;">
            <button id="theme-toggle" style="background:none; border:1px solid var(--border-color); color:var(--text-main); border-radius:50%; width:32px; height:32px; cursor:pointer; display:flex; align-items:center; justify-content:center;">
                <i class="fas fa-moon"></i>
            </button>
            <a href="index.php" style="font-size:11px; color:var(--text-muted); font-weight:bold; text-transform:uppercase; text-decoration:none; transition:0.3s;"><?php echo $txt['geri']; ?></a>
        </div>
    </div>
</nav>

<div class="container">
    <div class="poster-area">
        <img src="<?php echo htmlspecialchars($film['img']); ?>">
        <div style="margin-top:20px; font-size:11px; border-top:1px solid var(--border-color); padding-top:10px; line-height: 1.8;">
            Yönetmen: <b style="color:var(--accent);"><?php echo htmlspecialchars($film['dir']); ?></b><br>
            Yıl: <b><?php echo date("Y", strtotime($film['release_date'])); ?></b>
        </div>
    </div>

    <div class="details-area" style="flex:1; min-width: 0;">
        <h1 class="film-title"><?php echo htmlspecialchars($film['title']); ?></h1>
        <div class="info-grid">
            <div class="info-box">
                <h4><i class="fas fa-money-bill-wave"></i> <?php echo $txt['gise']; ?></h4>
                <p style="font-size:11px; margin:0; font-weight: 500;"><?php echo $film['revenue'] ?: 'N/A'; ?></p>
            </div>
            <div class="info-box">
                <h4><i class="fas fa-trophy"></i> <?php echo $txt['odul']; ?></h4>
                <p style="font-size:11px; margin:0; font-weight: 500;"><?php echo $film['awards'] ?: 'N/A'; ?></p>
            </div>
        </div>
        <p style="font-size:12px; color:var(--text-muted); text-align:justify; margin-bottom: 30px;"><?php echo nl2br(htmlspecialchars($film['description'])); ?></p>

        <h3 style="font-family:'Bebas Neue'; color:var(--accent); margin-top:35px; letter-spacing: 0.5px;"><?php echo $txt['galeri']; ?></h3>
        <div class="gallery-grid">
            <?php if($tmdb_images && isset($tmdb_images['backdrops']) && count($tmdb_images['backdrops']) > 0): ?>
                <?php foreach(array_slice($tmdb_images['backdrops'], 0, 4) as $img): ?>
                    <div class="gallery-item"><img src="https://image.tmdb.org/t/p/w500<?php echo $img['file_path']; ?>" onclick="window.open(this.src)"></div>
                <?php endforeach; ?>
            <?php else: echo "<p style='font-size:11px; color:var(--text-muted); grid-column: span 4;'>No images found. (ID: ".($film['tmdb_id'] ?: $id).")</p>"; endif; ?>
        </div>

        <div style="margin-top:40px;">
            <h3 style="font-family:'Bebas Neue'; color:var(--accent); letter-spacing: 0.5px;"><?php echo $txt['yorumlar']; ?></h3>
            <?php if($tmdb_reviews && isset($tmdb_reviews['results']) && count($tmdb_reviews['results']) > 0): ?>
                <?php foreach(array_slice($tmdb_reviews['results'], 0, 2) as $rev): ?>
                    <div class="review-item">"<?php echo mb_strimwidth(htmlspecialchars($rev['content']), 0, 250, "..."); ?>"<br><small style="color:var(--accent); font-weight:bold;">- <?php echo htmlspecialchars($rev['author']); ?></small></div>
                <?php endforeach; ?>
            <?php else: echo "<p style='font-size:11px; color:var(--text-muted);'>No reviews yet.</p>"; endif; ?>
        </div>
    </div>

    <div class="action-panel">
        <div class="price-tag"><?php echo htmlspecialchars($film['price']); ?> TL</div>
        
        <a href="sepet_islem.php?ekle=<?php echo $id; ?>" class="btn-buy"><?php echo $txt['sepet']; ?></a>
        
        <div style="margin-top:20px; border-top:1px solid var(--border-color); padding-top:15px; text-align:center;">
            <div style="font-size:10px; color:var(--text-muted); margin-bottom:12px;"><i class="fas fa-eye"></i> <?php echo number_format($film['view_count']); ?> Görüntülenme</div>
            <a href="detay.php?id=<?php echo $id; ?>&islem=izledim" style="font-size:10px; text-transform:uppercase; font-weight:bold; color:var(--text-muted); margin-right:12px; text-decoration:none; transition:0.3s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='var(--text-muted)'"><i class="fas fa-check"></i> <?php echo $txt['izledim']; ?></a>
            <a href="detay.php?id=<?php echo $id; ?>&islem=begen" style="font-size:10px; text-transform:uppercase; font-weight:bold; color:#ffd700; text-decoration:none; transition:0.3s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#ffd700'"><i class="fas fa-star"></i> <?php echo $txt['begen']; ?></a>
        </div>
    </div>
</div>

<footer style="text-align:center; padding:40px; color:var(--text-muted); font-size:11px; border-top: 1px solid var(--border-color); margin-top: 60px;">
    &copy; 2026 FRAME 25 | Selin Avcı Tarafından Yapılmıştır.
</footer>

<script>
    const toggleBtn = document.getElementById('theme-toggle');
    if (localStorage.getItem('theme') === 'light' && toggleBtn) { toggleBtn.innerHTML = '<i class="fas fa-sun"></i>'; }
    
    toggleBtn.addEventListener('click', () => {
        let theme = document.documentElement.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        toggleBtn.innerHTML = theme === 'light' ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
    });
</script>
</body>
</html>