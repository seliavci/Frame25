<?php
session_start();
// Hata raporlama
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 1);

// 1. Veritabanı bağlantısı
include 'baglan.php';
$site_adi = "FRAME 25";
$slogan = "pure cinema experience";

// =====================================================================
// YENİ EKLENEN: ÇOKLU DİL SİSTEMİ (i18n)
// =====================================================================
if (isset($_GET['lang'])) {
    $secilen_dil = strtolower($_GET['lang']);
    if (in_array($secilen_dil, ['tr', 'en', 'fr'])) {
        $_SESSION['site_lang'] = $secilen_dil;
    }
    header("Location: index.php");
    exit();
}

if (!isset($_SESSION['site_lang'])) {
    $_SESSION['site_lang'] = 'tr';
}
$current_lang = $_SESSION['site_lang'];

$lang_dict = [
    'tr' => [
        'katalog' => 'Katalog', 'anasayfa' => 'Ana Sayfa', 'kesfet' => 'Keşfet', 'sepetim' => 'Sepetim',
        'profilim' => 'Profilim', 'yonetim' => 'YÖNETİM', 'film_ara' => 'Film ara...',
        'haftanin_seckisi' => 'HAFTANIN SEÇKİSİ:<br>MODERN AUTEURS', 'koleksiyonu_gor' => 'KOLEKSİYONU GÖR',
        'su_an_vizyonda' => 'Şu Anda Vizyonda', 'bu_filmlere_goz_at' => 'Bu Filmlere Göz At',
        'detayli_incele' => 'Detaylı Incele', 'vizyonda_film_yok' => 'Henüz vizyonda film işaretlenmedi.',
        'aktiviteler' => 'Aktiviteler', 'izledi' => 'izledi', 'aktivite_yok' => 'Henüz bir aktivite paylaşılmadı.',
        'toplam_izlenme' => 'TOPLAM İZLENME', 'kayitli_uye' => 'KAYITLI ÜYE', 'kategoriler' => 'Kategoriler',
        'tumu' => 'Tümü', 'vizyondakiler' => 'Vizyondakiler', 'editorun_seckisi' => 'Editörün Seçkisi',
        'film_secilmedi' => 'Henüz bir film seçilmedi.', 'bulten_baslik' => 'SİNEMA DÜNYASINDAN UZAK KALMA',
        'bulten_aciklama' => 'Haftalık film önerileri, özel listeler ve yönetmen röportajları doğrudan e-posta kutuna gelsin.',
        'email_gir' => 'E-posta adresini gir...', 'kayit_ol' => 'KAYIT OL', 'sinema_haberleri' => 'Sinema Dünyasından Haberler',
        'guncel_haber_yok' => 'Henüz güncel bir haber bulunmuyor.', 'tum_haklari_saklidir' => 'Selin Avcı Tarafından Yapılmıştır. Tüm Hakları Saklıdır.',
        'kategori_film_yok' => 'Bu kategoriye ait film bulunamadı.', 'izleme_listeme_ekle' => 'Izleme Listeme Ekle'
    ],
    'en' => [
        'katalog' => 'Catalog', 'anasayfa' => 'Home', 'kesfet' => 'Discover', 'sepetim' => 'My Cart',
        'profilim' => 'Profile', 'yonetim' => 'ADMIN', 'film_ara' => 'Search movies...',
        'haftanin_seckisi' => 'PICK OF THE WEEK:<br>MODERN AUTEURS', 'koleksiyonu_gor' => 'VIEW COLLECTION',
        'su_an_vizyonda' => 'Now Showing', 'bu_filmlere_goz_at' => 'Check These Out',
        'detayli_incele' => 'View Details', 'vizyonda_film_yok' => 'No movies are currently showing.',
        'aktiviteler' => 'Recent Activities', 'izledi' => 'watched', 'aktivite_yok' => 'No activities shared yet.',
        'toplam_izlenme' => 'TOTAL VIEWS', 'kayitli_uye' => 'REGISTERED USERS', 'kategoriler' => 'Categories',
        'tumu' => 'All', 'vizyondakiler' => 'Now Showing', 'editorun_seckisi' => 'Editor\'s Pick',
        'film_secilmedi' => 'No movie selected yet.', 'bulten_baslik' => 'STAY CONNECTED WITH CINEMA',
        'bulten_aciklama' => 'Get weekly movie recommendations, curated lists, and director interviews straight to your inbox.',
        'email_gir' => 'Enter your email...', 'kayit_ol' => 'SUBSCRIBE', 'sinema_haberleri' => 'Cinema News',
        'guncel_haber_yok' => 'No recent news available.', 'tum_haklari_saklidir' => 'Created by Selin Avcı. All Rights Reserved.',
        'kategori_film_yok' => 'No movies found in this category.', 'izleme_listeme_ekle' => 'Add to Watchlist'
    ],
    'fr' => [
        'katalog' => 'Catalogue', 'anasayfa' => 'Accueil', 'kesfet' => 'Découvrir', 'sepetim' => 'Mon Panier',
        'profilim' => 'Profil', 'yonetim' => 'ADMINISTRATION', 'film_ara' => 'Rechercher...',
        'haftanin_seckisi' => 'SÉLECTION DE LA SEMAINE:<br>AUTEURS MODERNES', 'koleksiyonu_gor' => 'VOIR LA COLLECTION',
        'su_an_vizyonda' => 'À l\'Affiche', 'bu_filmlere_goz_at' => 'Découvrez Ces Films',
        'detayli_incele' => 'Voir les Détails', 'vizyonda_film_yok' => 'Aucun film actuellement à l\'affiche.',
        'aktiviteler' => 'Activités Récentes', 'izledi' => 'a regardé', 'aktivite_yok' => 'Aucune activité partagée.',
        'toplam_izlenme' => 'VUES TOTALES', 'kayitli_uye' => 'MEMBRES INSCRITS', 'kategoriler' => 'Catégories',
        'tumu' => 'Tout', 'vizyondakiler' => 'À l\'affiche', 'editorun_seckisi' => 'Choix de l\'Éditeur',
        'film_secilmedi' => 'Aucun film sélectionné.', 'bulten_baslik' => 'RESTEZ CONNECTÉ AU CINÉMA',
        'bulten_aciklama' => 'Recevez des recommandations hebdomadaires et des interviews directement dans votre boîte de réception.',
        'email_gir' => 'Entrez votre e-mail...', 'kayit_ol' => 'S\'ABONNER', 'sinema_haberleri' => 'Actualités du Cinéma',
        'guncel_haber_yok' => 'Aucune actualité récente.', 'tum_haklari_saklidir' => 'Créé par Selin Avcı. Tous droits réservés.',
        'kategori_film_yok' => 'Aucun film trouvé dans cette catégorie.', 'izleme_listeme_ekle' => 'Ajouter à la Watchlist'
    ]
];

$txt = $lang_dict[$current_lang];

// 2. TÜM VERİLER
$sql = "SELECT * FROM filmler ORDER BY id DESC";
$sonuc = $baglanti->query($sql);
$moviesData = []; 
if ($sonuc && $sonuc->num_rows > 0) {
    while($row = $sonuc->fetch_assoc()) {
        $moviesData[] = $row;
    }
}

// 3. ŞU ANDA VİZYONDA
$sql_vizyon = "SELECT * FROM filmler WHERE is_showcase = 1 ORDER BY id DESC";
$vizyon_sonuc = $baglanti->query($sql_vizyon);

// 4. ÖNERİLEN FİLMLER
$sql_onerilen = "SELECT * FROM filmler WHERE is_showcase = 0 ORDER BY RAND() LIMIT 4";
$onerilen_sonuc = $baglanti->query($sql_onerilen);

// 5. GÜNÜN FİLMİ
$gunun_filmi_sorgu = $baglanti->query("SELECT * FROM filmler WHERE is_daily_film = 1 LIMIT 1");
$gunun_filmi = ($gunun_filmi_sorgu && $gunun_filmi_sorgu->num_rows > 0) ? $gunun_filmi_sorgu->fetch_assoc() : null;

// 6. HABERLER
$haberler_sorgu = $baglanti->query("SELECT * FROM haberler ORDER BY tarih DESC LIMIT 4");

// 7. GERÇEK VERİLER
$stat_izlenme = 0;
$stat_izlenme_q = $baglanti->query("SHOW TABLES LIKE 'izlenenler'");
if($stat_izlenme_q && $stat_izlenme_q->num_rows > 0) {
    $stat_izlenme = $baglanti->query("SELECT COUNT(*) as c FROM izlenenler")->fetch_assoc()['c'];
}

$stat_uye = 0;
$stat_uye_q = $baglanti->query("SHOW TABLES LIKE 'kullanicilar'");
if($stat_uye_q && $stat_uye_q->num_rows > 0) {
    $stat_uye = $baglanti->query("SELECT COUNT(*) as c FROM kullanicilar")->fetch_assoc()['c'];
}

$akt_tablo_kontrol = $baglanti->query("SHOW TABLES LIKE 'aktiviteler'");
$aktiviteler_sorgu = ($akt_tablo_kontrol && $akt_tablo_kontrol->num_rows > 0) ? $baglanti->query("SELECT * FROM aktiviteler ORDER BY tarih DESC LIMIT 3") : null;

// 8. DİNAMİK SLIDER VERİSİ
$slider_sorgu = $baglanti->query("SELECT * FROM slider ORDER BY sira ASC LIMIT 1");
$slider_item = ($slider_sorgu && $slider_sorgu->num_rows > 0) ? $slider_sorgu->fetch_assoc() : null;
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $site_adi; ?> - <?php echo $txt['katalog']; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/lipis/flag-icons@7.0.0/css/flag-icons.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <style>
        /* TEMA DEĞİŞKENLERİ */
        :root {
            --bg-color: #050505;
            --header-bg: #000000;
            --nav-bg: #111111;
            --card-bg: #111111;
            --text-main: #ffffff;
            --text-muted: #888888;
            --border-color: #222222;
        }

        [data-theme="light"] {
            --bg-color: #f5f2eb; 
            --header-bg: #e8e4d9;
            --nav-bg: #efede7;
            --card-bg: #ffffff;
            --text-main: #1a1a1a;
            --text-muted: #555555;
            --border-color: #dcd7ca;
        }

        /* GENEL AYARLAR */
        * { box-sizing: border-box; }
        body { font-family: 'Montserrat', sans-serif; background-color: var(--bg-color); color: var(--text-main); margin: 0; padding: 0; line-height: 1.5; transition: 0.3s; }
        a { text-decoration: none; color: inherit; transition: 0.3s; }
        ul { list-style: none; padding: 0; margin: 0; }
        
        header { background-color: var(--header-bg); padding: 50px 0; text-align: center; border-bottom: 4px solid #b20710; transition: 0.3s; }
        header h1 { margin: 0; font-family: 'Bebas Neue', cursive; font-size: 4.5em; color: #b20710; letter-spacing: 5px; text-shadow: 2px 2px 4px rgba(0,0,0,0.3); }
        header p { color: var(--text-muted); font-size: 11px; text-transform: uppercase; letter-spacing: 12px; margin-top: -25px; margin-bottom: 20px; }
        
        nav { background-color: var(--nav-bg); border-bottom: 1px solid var(--border-color); width: 100%; height: auto; min-height: 60px; position: sticky; top: 0; z-index: 1000; transition: 0.3s; }
        .nav-wrapper { width: 90%; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; min-height: 60px; flex-wrap: wrap; padding: 10px 0; }
        nav ul { display: flex; flex-wrap: wrap; }
        nav li a { display: block; padding: 15px 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; color: var(--text-muted); }
        nav li a:hover, nav li a.active { color: #fff; background-color: #b20710; }
        
        .search-area { display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
        .search-area input { background-color: var(--bg-color); border: 1px solid var(--border-color); color: var(--text-main); padding: 8px 15px; border-radius: 20px; outline: none; width: 180px; font-size: 12px; transition: 0.3s; }
        
        .lang-switcher { display: flex; gap: 5px; border-right: 1px solid var(--border-color); padding-right: 15px;}
        .lang-btn { display: block; width: 24px; height: 24px; border-radius: 50%; overflow: hidden; opacity: 0.5; transition: 0.3s; border: 1px solid transparent;}
        .lang-btn:hover { opacity: 0.8; }
        .lang-btn.active { opacity: 1; border-color: #b20710; transform: scale(1.1); box-shadow: 0 0 5px rgba(178,7,16,0.5);}
        .lang-btn span { width: 100%; height: 100%; display: block; background-size: cover; background-position: center;}

        .theme-btn { background: none; border: 1px solid var(--border-color); color: var(--text-main); font-size: 13px; cursor: pointer; padding: 6px 10px; border-radius: 50%; transition: 0.3s; display: flex; align-items: center; justify-content: center; }
        .theme-btn:hover { background-color: #b20710; color: #fff; border-color: #b20710; }

        .container { width: 95%; max-width: 1400px; margin: 30px auto; display: flex; gap: 30px; } 
        .main-content { width: 80%; }
        aside { width: 20%; padding-left: 20px; border-left: 1px solid var(--border-color); transition: 0.3s; }
        
        .hero-banner { width: 100%; height: auto; min-height: 220px; background-size: cover; background-position: center; border-radius: 8px; margin-bottom: 35px; display: flex; align-items: center; padding: 40px; border: 1px solid var(--border-color); }
        .hero-banner h2 { font-family: 'Bebas Neue'; font-size: 2.8em; color: #fff; margin: 0; }

        .section-head { font-family: 'Bebas Neue'; font-size: 2.2em; border-left: 6px solid #b20710; padding-left: 15px; margin-bottom: 20px; text-transform: uppercase; margin-top: 35px; color: var(--text-main); }
        .section-head:first-child { margin-top: 0; }
        
        #movie-grid { display: flex; overflow-x: auto; scroll-snap-type: x mandatory; gap: 20px; padding-bottom: 15px; }
        #movie-grid::-webkit-scrollbar { height: 8px; }
        #movie-grid::-webkit-scrollbar-track { background: var(--bg-color); border-radius: 10px; }
        #movie-grid::-webkit-scrollbar-thumb { background: #b20710; border-radius: 10px; }
        
        .static-grid { display: flex; flex-wrap: wrap; gap: 20px; }
        
        /* ÖNEMLİ RESPONSIVE FİX: Kart yapısının temel özellikleri */
        .movie-card { min-width: calc(25% - 15px); width: calc(25% - 15px); background-color: var(--card-bg); border-radius: 5px; padding: 10px; position: relative; border: 1px solid var(--border-color); transition: 0.3s; text-align: center; scroll-snap-align: start; flex-shrink: 0;}
        .movie-card:hover { border-color: #b20710; transform: translateY(-5px); }
        
        .price-badge { position: absolute; top: 15px; right: 15px; background: #b20710; color: white; padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: bold; z-index: 5; }
        .watchlist-btn { position: absolute; top: 15px; left: 15px; background: rgba(0,0,0,0.6); color: #fff; border: 1px solid rgba(255,255,255,0.2); padding: 4px 8px; border-radius: 3px; font-size: 12px; z-index: 5; cursor: pointer; transition: 0.3s; }
        .watchlist-btn:hover { background: #b20710; border-color: #b20710; }

        .movie-card img { width: 100%; height: 300px; object-fit: cover; border-radius: 3px; cursor: pointer; filter: brightness(0.9); transition: 0.3s; }
        .movie-card:hover img { filter: brightness(1); }
        .movie-card h3 { font-size: 13px; margin: 12px 0 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--text-main); }
        .movie-card p { font-size: 10px; color: var(--text-muted); margin-bottom: 5px; }
        .release-date { font-size: 9px; color: var(--text-muted); margin-bottom: 10px; border-top: 1px solid var(--border-color); padding-top: 5px; display: inline-block; width: 100%;}
        
        .btn-card { display: block; width: 100%; padding: 10px; background-color: var(--nav-bg); color: var(--text-main); font-weight: bold; text-transform: uppercase; border: 1px solid var(--border-color); cursor: pointer; font-size: 10px; border-radius: 3px; transition: 0.3s;}
        .movie-card:hover .btn-card { background-color: #b20710; color: #fff; border-color: #b20710;}
        
        .friend-log { display: flex; align-items: center; background: var(--card-bg); padding: 12px; border-radius: 5px; margin-bottom: 10px; border: 1px solid var(--border-color); transition: 0.3s; }
        .friend-avatar { width: 40px; height: 40px; border-radius: 50%; margin-right: 15px; border: 1px solid var(--border-color); }
        .friend-info h4 { margin: 0; color: var(--text-main); font-size: 12px; }
        .friend-info h4 span { color: var(--text-muted); font-weight: normal; font-size: 11px; margin-left: 5px;}
        .friend-info p { margin: 4px 0 0; color: var(--text-muted); font-size: 11px; font-style: italic;}
        .friend-rating { color: #00e054; font-size: 12px; letter-spacing: 1px; }
        .friend-time { font-size: 9px; color: var(--text-muted); display: block; margin-top: 5px;}

        .sidebar-box { background-color: var(--card-bg); padding: 15px; margin-bottom: 25px; border-bottom: 3px solid #b20710; border: 1px solid var(--border-color); border-radius: 5px; transition: 0.3s; }
        .sidebar-box h3 { margin: 0 0 12px 0; font-family: 'Bebas Neue'; font-size: 1.6em; color: #b20710; text-transform: uppercase; }
        .category-list ul { max-height: 400px; overflow-y: auto; padding-right: 10px; }
        .category-list li { padding: 6px 0; border-bottom: 1px solid var(--border-color); color: var(--text-muted); cursor: pointer; font-size: 11px; transition: 0.2s;}
        .category-list li:hover { color: var(--text-main); padding-left: 5px; }

        .daily-box { background: linear-gradient(45deg, var(--card-bg), var(--bg-color)); border: 1px solid #ffd700; padding: 15px; border-radius: 5px; margin-top: 20px; text-align: center; }
        .daily-box img { width: 100%; border-radius: 4px; margin-bottom: 10px; object-fit: cover; max-height: 200px;}
        .daily-tag { background: #ffd700; color: #000; font-size: 9px; font-weight: bold; padding: 3px 8px; border-radius: 3px; text-transform: uppercase; margin-bottom: 10px; display: inline-block;}
        
        .newsletter-section { background: var(--card-bg); padding: 40px 0; text-align: center; border-top: 3px solid #b20710; border-bottom: 1px solid var(--border-color); margin-top: 40px; transition: 0.3s; }
        .newsletter-section h3 { font-family: 'Bebas Neue'; font-size: 2.2em; color: var(--text-main); margin: 0 0 5px 0; }
        .newsletter-section p { font-size: 11px; color: var(--text-muted); margin-bottom: 20px; }
        .newsletter-form { display: flex; justify-content: center; align-items: center; flex-wrap: wrap; gap: 10px; }
        .newsletter-form input { background: var(--bg-color); border: 1px solid var(--border-color); color: var(--text-main); padding: 12px 20px; width: 300px; border-radius: 25px; outline: none; font-size: 11px; }
        .newsletter-form button { background: #b20710; color: #fff; border: none; padding: 12px 25px; border-radius: 25px; font-weight: bold; font-size: 11px; cursor: pointer; transition: 0.3s; }
        .newsletter-form button:hover { background: #fff; color: #b20710; }

        .news-section { background: var(--bg-color); padding: 40px 0; margin-top: 0; transition: 0.3s; }
        .news-container { display: flex; gap: 20px; width: 95%; max-width: 1400px; margin: 0 auto; flex-wrap: wrap;}
        .news-card { flex: 1; min-width: 300px; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 5px; overflow: hidden; display: flex; transition: 0.3s; cursor: pointer; text-decoration: none;}
        .news-card:hover { border-color: #b20710; transform: scale(1.02); }
        .news-card img { width: 120px; min-width: 120px; height: 100%; object-fit: cover; }
        .news-content { padding: 15px; }
        .news-content h4 { margin: 0 0 5px 0; color: var(--text-main); font-size: 13px; line-height: 1.4; transition: 0.3s; }
        .news-card:hover h4 { color: #b20710; }
        .news-content p { font-size: 10px; color: var(--text-muted); margin: 0; line-height: 1.4;}
        .news-date { color: #b20710; font-size: 9px; font-weight: bold; display: block; margin-top: 8px; }

        footer { clear: both; background-color: var(--header-bg); padding: 40px 0; text-align: center; border-top: 1px solid var(--border-color); color: var(--text-muted); font-size: 11px; transition: 0.3s; }

        /* =========================================
           KAPSAMLI RESPONSIVE ARTIK ÇALIŞIYOR (Yönerge Madde 1)
           ========================================= */
        @media screen and (max-width: 1024px) {
            .movie-card { min-width: calc(33.33% - 14px); width: calc(33.33% - 14px); }
        }

        @media screen and (max-width: 768px) {
            .container { flex-direction: column; gap: 20px; }
            .main-content { width: 100%; }
            aside { width: 100%; padding-left: 0; border-left: none; }
            
            .nav-wrapper { flex-direction: column; gap: 15px; text-align: center; }
            nav ul { flex-direction: column; width: 100%; }
            nav li a { padding: 10px; border-bottom: 1px solid var(--border-color); }
            .search-area { width: 100%; justify-content: center; }

            #movie-grid .movie-card { min-width: 75%; width: 75%; }
            .static-grid .movie-card { min-width: 100%; width: 100%; }
            .movie-card img { height: auto; max-height: 400px; }

            header h1 { font-size: 3em; }
            header p { letter-spacing: 4px; margin-top: -15px; }
            .hero-banner { padding: 20px; }
            .hero-banner h2 { font-size: 2em; }
        }
    </style>
</head>
<body>

<header>
    <h1><?php echo $site_adi; ?></h1>
    <p><?php echo $slogan; ?></p>
</header>

<nav>
    <div class="nav-wrapper">
        <ul>
            <li><a href="index.php" class="active"><?php echo $txt['anasayfa']; ?></a></li>
            <li><a href="kesfet.php"><?php echo $txt['kesfet']; ?></a></li>
            <li><a href="cart.php"><?php echo $txt['sepetim']; ?></a></li>
            <li><a href="profile.php"><?php echo $txt['profilim']; ?></a></li>
            <li><a href="admin.php" style="color:#b20710;"><?php echo $txt['yonetim']; ?></a></li>
        </ul>
        <div class="search-area">
            <div class="lang-switcher">
                <a href="index.php?lang=tr" class="lang-btn <?php echo $current_lang == 'tr' ? 'active' : ''; ?>" title="Türkçe"><span class="fi fi-tr"></span></a>
                <a href="index.php?lang=en" class="lang-btn <?php echo $current_lang == 'en' ? 'active' : ''; ?>" title="English"><span class="fi fi-gb"></span></a>
                <a href="index.php?lang=fr" class="lang-btn <?php echo $current_lang == 'fr' ? 'active' : ''; ?>" title="Français"><span class="fi fi-fr"></span></a>
            </div>

            <button class="theme-btn" id="theme-toggle" title="Aydınlık/Karanlık Mod">
                <i class="fas fa-moon"></i>
            </button>
            <input type="text" id="searchInput" onkeyup="searchMovies()" placeholder="<?php echo $txt['film_ara']; ?>">
        </div>
    </div>
</nav>

<div class="container">
    <section class="main-content">
        <?php 
            $banner_title = $slider_item ? $slider_item['baslik'] : $txt['haftanin_seckisi'];
            $banner_link = $slider_item ? $slider_item['link'] : "kesfet.php?genre=Modern Auteurs";
            $banner_btn_text = $slider_item ? $slider_item['aciklama'] : $txt['koleksiyonu_gor']; 
            $banner_img = $slider_item ? $slider_item['resim'] : 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?q=80&w=2070&auto=format&fit=crop';
        ?>
        <div class="hero-banner" style="background: linear-gradient(to right, rgba(178,7,16,0.8), rgba(0,0,0,0.4)), url('<?php echo $banner_img; ?>'); background-size: cover; background-position: center;">
            <div>
                <h2><?php echo $banner_title; ?></h2>
                <a href="<?php echo $banner_link; ?>" style="background:#fff; color:#000; padding:10px 20px; border-radius:4px; font-weight:bold; font-size:11px; margin-top:10px; display:inline-block;"><?php echo $banner_btn_text; ?></a>
            </div>
        </div>

        <h2 class="section-head" id="category-title"><?php echo $txt['su_an_vizyonda']; ?></h2>
        <div id="movie-grid">
            <?php if ($vizyon_sonuc && $vizyon_sonuc->num_rows > 0): ?>
                <?php while($f = $vizyon_sonuc->fetch_assoc()): ?>
                    <div class="movie-card">
                        <div class="watchlist-btn" title="<?php echo $txt['izleme_listeme_ekle']; ?>"><i class="far fa-bookmark"></i></div>
                        <div class="price-badge"><?php echo htmlspecialchars($f['price']); ?> TL</div>
                        <a href="detay.php?id=<?php echo htmlspecialchars($f['id']); ?>">
                            <img src="<?php echo htmlspecialchars($f['img']); ?>" alt="<?php echo htmlspecialchars($f['title']); ?>">
                        </a>
                        <h3><?php echo htmlspecialchars($f['title']); ?></h3>
                        <p><a href="oyuncu.php?isim=<?php echo urlencode($f['dir']); ?>" style="color:inherit; transition:0.3s;" onmouseover="this.style.color='#b20710'" onmouseout="this.style.color='inherit'"><?php echo htmlspecialchars($f['dir']); ?></a></p>
                        <a href="detay.php?id=<?php echo htmlspecialchars($f['id']); ?>" class="btn-card"><?php echo $txt['detayli_incele']; ?></a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color:var(--text-muted); padding:20px; font-size:11px;"><?php echo $txt['vizyonda_film_yok']; ?></p>
            <?php endif; ?>
        </div>

        <h2 class="section-head"><?php echo $txt['bu_filmlere_goz_at']; ?></h2>
        <div class="static-grid">
            <?php if ($onerilen_sonuc && $onerilen_sonuc->num_rows > 0): ?>
                <?php while($row = $onerilen_sonuc->fetch_assoc()): ?>
                    <div class="movie-card">
                        <div class="watchlist-btn" title="<?php echo $txt['izleme_listeme_ekle']; ?>"><i class="far fa-bookmark"></i></div>
                        <div class="price-badge"><?php echo htmlspecialchars($row['price']); ?> TL</div>
                        <a href="detay.php?id=<?php echo htmlspecialchars($row['id']); ?>">
                            <img src="<?php echo htmlspecialchars($row['img']); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>">
                        </a>
                        <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                        <p><a href="oyuncu.php?isim=<?php echo urlencode($row['dir']); ?>" style="color:inherit; transition:0.3s;" onmouseover="this.style.color='#b20710'" onmouseout="this.style.color='inherit'"><?php echo htmlspecialchars($row['dir']); ?></a></p>
                        <a href="detay.php?id=<?php echo htmlspecialchars($row['id']); ?>" class="btn-card"><?php echo $txt['detayli_incele']; ?></a>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>

        <h2 class="section-head" style="border-left-color: #00e054;"><?php echo $txt['aktiviteler']; ?></h2>
        <div class="friends-list">
            <?php if ($aktiviteler_sorgu && $aktiviteler_sorgu->num_rows > 0): ?>
                <?php while($akt = $aktiviteler_sorgu->fetch_assoc()): ?>
                    <div class="friend-log">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($akt['isim']); ?>&background=random" class="friend-avatar">
                        <div class="friend-info">
                            <h4><?php echo htmlspecialchars($akt['isim']); ?> <span><?php echo $txt['izledi']; ?>: <?php echo htmlspecialchars($akt['film_adi']); ?></span></h4>
                            <p>"<?php echo htmlspecialchars($akt['yorum']); ?>"</p>
                        </div>
                        <div class="friend-meta">
                            <div class="friend-rating">
                                <?php 
                                $puan = intval($akt['puan']);
                                echo str_repeat("★", $puan) . str_repeat("☆", 5 - $puan); 
                                ?>
                            </div>
                            <span class="friend-time"><?php echo date("H:i", strtotime($akt['tarih'])); ?></span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color:var(--text-muted); padding:10px; font-size:11px;"><?php echo $txt['aktivite_yok']; ?></p>
            <?php endif; ?>
        </div>
    </section>

    <aside>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:20px;">
            <div style="background:var(--card-bg); padding:15px; border:1px solid var(--border-color); text-align:center; border-radius:5px;">
                <i class="fas fa-eye" style="color:#b20710; font-size:16px;"></i>
                <div style="font-family:'Bebas Neue'; font-size:18px; margin-top:5px;"><?php echo $stat_izlenme; ?></div>
                <div style="font-size:8px; color:var(--text-muted); text-transform:uppercase;"><?php echo $txt['toplam_izlenme']; ?></div>
            </div>
            <div style="background:var(--card-bg); padding:15px; border:1px solid var(--border-color); text-align:center; border-radius:5px;">
                <i class="fas fa-users" style="color:#b20710; font-size:16px;"></i>
                <div style="font-family:'Bebas Neue'; font-size:18px; margin-top:5px;"><?php echo $stat_uye; ?></div>
                <div style="font-size:8px; color:var(--text-muted); text-transform:uppercase;"><?php echo $txt['kayitli_uye']; ?></div>
            </div>
        </div>

        <div class="sidebar-box category-list">
            <h3><?php echo $txt['kategoriler']; ?></h3>
            <ul>
                <li onclick="filterMovies('Tümü')"><?php echo $txt['tumu']; ?></li>
                <li onclick="filterMovies('Vizyondakiler')"><?php echo $txt['vizyondakiler']; ?></li>
                <li onclick="filterMovies('Masters of Cinema')">Master of Cinema</li>
                <li onclick="filterMovies('Moder Auteurs')">Modern Auteurs</li>
                <li onclick="filterMovies('Female Gaze')">Female Gaze</li>
                <li onclick="filterMovies('Fransız Yeni Dalgası')">Fransız Yeni Dalgası</li>
                <li onclick="filterMovies('Palme d\'Or')">Palme d'Or</li>
                <li onclick="filterMovies('Oscar')">Oscar</li>
                <li onclick="filterMovies('A24 Koleksiyonu')">A24 Koleksiyonu</li>
                <li onclick="filterMovies('Animasyon')">Animasyon</li>
                <li onclick="filterMovies('Aksiyon')">Aksiyon</li>
                <li onclick="filterMovies('Macera')">Macera</li>
                <li onclick="filterMovies('Fantastik')">Fantastik</li>
                <li onclick="filterMovies('Bilim Kurgu')">Bilim Kurgu</li>
                <li onclick="filterMovies('Distopya')">Distopya</li>
                <li onclick="filterMovies('Mind-Bending')">Mind-Bending</li>
                <li onclick="filterMovies('Western')">Western</li>
                <li onclick="filterMovies('Spaghetti Western')">Spaghetti Western</li>
                <li onclick="filterMovies('Bağımsız')">Bağımsız</li>
                <li onclick="filterMovies('Kült')">Kült</li>
                <li onclick="filterMovies('Hollywood')">Hollywood</li>
                <li onclick="filterMovies('New Hollywood')">New Hollywood</li>
                <li onclick="filterMovies('Dönem')">Dönem</li>
                <li onclick="filterMovies('Biyografi')">Biyografi</li>
                <li onclick="filterMovies('Savaş')">Savaş</li>
                <li onclick="filterMovies('Neon Noir & Cyberpunk')">Neon Noir & Cyberpunk</li>
                <li onclick="filterMovies('Art-House')">Art-House</li>
                <li onclick="filterMovies('Deneysel')">Deneysel</li>
                <li onclick="filterMovies('Müzikal')">Müzikal</li>
                <li onclick="filterMovies('Mockumentary')">Macumentary</li>
                <li onclick="filterMovies('Belgesel')">Belgesel</li>
                <li onclick="filterMovies('Spor')">Spor</li>
                <li onclick="filterMovies('Suç')">Suç & Polisiye</li>
                <li onclick="filterMovies('Slow Cinema')">Slow Cinema</li>
                <li onclick="filterMovies('Varoluşsal Sancılar')">Varoluşsal Sancılar</li>
                <li onclick="filterMovies('Dram')">Dram</li>
                <li onclick="filterMovies('Romantik')">Romantik</li>
                <li onclick="filterMovies('Gerilim')">Gerilim</li>
                <li onclick="filterMovies('Whodunit')">Whodunit</li>
                <li onclick="filterMovies('Korku')">Korku</li>
                <li onclick="filterMovies('Slasher')">Slasher</li>
            </ul>
        </div>

        <div class="daily-box">
            <span class="daily-tag"><?php echo $txt['editorun_seckisi']; ?></span>
            <?php if($gunun_filmi): ?>
                <a href="detay.php?id=<?php echo htmlspecialchars($gunun_filmi['id']); ?>">
                    <img src="<?php echo htmlspecialchars($gunun_filmi['img']); ?>" alt="<?php echo htmlspecialchars($gunun_filmi['title']); ?>">
                    <h4 style="margin:5px 0; color:var(--text-main); font-size:13px;"><?php echo htmlspecialchars($gunun_filmi['title']); ?></h4>
                </a>
                <p style="font-size:10px; color:var(--text-muted); margin:0;">
                    <a href="oyuncu.php?isim=<?php echo urlencode($gunun_filmi['dir']); ?>" style="color:inherit; transition:0.3s;" onmouseover="this.style.color='#b20710'" onmouseout="this.style.color='inherit'"><?php echo htmlspecialchars($gunun_filmi['dir']); ?></a>
                </p>
            <?php else: ?>
                <p style="color:var(--text-muted); font-size:11px;"><?php echo $txt['film_secilmedi']; ?></p>
            <?php endif; ?>
        </div>
    </aside>
</div>

<section class="newsletter-section">
    <h3><?php echo $txt['bulten_baslik']; ?></h3>
    <p><?php echo $txt['bulten_aciklama']; ?></p>
    <form class="newsletter-form" onsubmit="event.preventDefault(); alert('Bültene başarıyla abone oldunuz!');">
        <input type="email" placeholder="<?php echo $txt['email_gir']; ?>" required>
        <button type="submit"><?php echo $txt['kayit_ol']; ?></button>
    </form>
</section>

<section class="news-section">
    <div style="width: 95%; max-width: 1400px; margin: 0 auto;">
        <h2 class="section-head" style="border-left-color: #fff; margin-bottom: 25px;"><?php echo $txt['sinema_haberleri']; ?></h2>
    </div>
    <div class="news-container">
        <?php 
        if ($haberler_sorgu && $haberler_sorgu->num_rows > 0): 
            while($haber = $haberler_sorgu->fetch_assoc()): ?>
            
            <a href="haber_detay.php?id=<?php echo htmlspecialchars($haber['id']); ?>" class="news-card">
                <img src="<?php echo !empty($haber['resim']) ? htmlspecialchars($haber['resim']) : 'https://via.placeholder.com/150x100/111/444?text=FRAME25'; ?>" alt="Haber">
                <div class="news-content">
                    <h4><?php echo htmlspecialchars($haber['baslik']); ?></h4>
                    <p><?php echo htmlspecialchars(mb_substr($haber['icerik'], 0, 85, 'UTF-8')); ?>...</p>
                    <span class="news-date">
                        <i class="far fa-clock"></i> <?php echo date("d.m.Y", strtotime($haber['tarih'])); ?>
                    </span>
                </div>
            </a>

            <?php endwhile; 
        else: ?>
            <p style="color:var(--text-muted); padding-left:20px; font-size:11px;"><?php echo $txt['guncel_haber_yok']; ?></p>
        <?php endif; ?>
    </div>
</section>

<footer>
    &copy; 2026 <?php echo $site_adi; ?> | <?php echo $txt['tum_haklari_saklidir']; ?> 
</footer>

<script>
    const toggleBtn = document.getElementById('theme-toggle');
    const currentTheme = localStorage.getItem('theme') || 'dark';

    if (currentTheme === 'light') {
        document.documentElement.setAttribute('data-theme', 'light');
        toggleBtn.innerHTML = '<i class="fas fa-sun"></i>';
    }

    toggleBtn.addEventListener('click', () => {
        let theme = document.documentElement.getAttribute('data-theme');
        if (theme === 'light') {
            document.documentElement.removeAttribute('data-theme');
            localStorage.setItem('theme', 'dark');
            toggleBtn.innerHTML = '<i class="fas fa-moon"></i>';
        } else {
            document.documentElement.setAttribute('data-theme', 'light');
            localStorage.setItem('theme', 'light');
            toggleBtn.innerHTML = '<i class="fas fa-sun"></i>';
        }
    });

    var moviesData = <?php echo json_encode($moviesData); ?>;
    
    var txt_detayli_incele = "<?php echo $txt['detayli_incele']; ?>";
    var txt_kategori_yok = "<?php echo $txt['kategori_film_yok']; ?>";
    var txt_izleme_listesi = "<?php echo $txt['izleme_listeme_ekle']; ?>";
    var txt_su_an_vizyonda = "<?php echo $txt['su_an_vizyonda']; ?>";
    var txt_tumu = "<?php echo $txt['anasayfa'] == 'Home' ? 'All Movies' : ($txt['anasayfa'] == 'Accueil' ? 'Tous Les Films' : 'Tüm Filmler'); ?>";

    function renderMovies(arr) {
        var grid = document.getElementById('movie-grid');
        grid.innerHTML = "";
        if(arr.length === 0) {
            grid.innerHTML = "<p style='color:var(--text-muted); font-size:11px;'>" + txt_kategori_yok + "</p>";
            return;
        }
        arr.forEach(film => {
            var card = `
                <div class="movie-card">
                    <div class="watchlist-btn" title="${txt_izleme_listesi}"><i class="far fa-bookmark"></i></div>
                    <div class="price-badge">${film.price} TL</div>
                    <a href="detay.php?id=${film.id}">
                        <img src="${film.img}" alt="${film.title}">
                    </a>
                    <h3>${film.title}</h3>
                    <p><a href="oyuncu.php?isim=${encodeURIComponent(film.dir)}" style="color:inherit; transition:0.3s;" onmouseover="this.style.color='#b20710'" onmouseout="this.style.color='inherit'">${film.dir}</a></p>
                    <span class="release-date"><i class="fas fa-globe"></i> 15 Mart 2026 🇹🇷</span>
                    <a href="detay.php?id=${film.id}" class="btn-card">${txt_detayli_incele}</a>
                </div>`;
            grid.innerHTML += card;
        });
    }

    function filterMovies(cat) {
        if (cat === 'Vizyondakiler') {
            document.getElementById('category-title').innerText = txt_su_an_vizyonda;
            renderMovies(moviesData.filter(f => f.is_showcase == 1));
        } else if (cat === 'Tümü') {
            document.getElementById('category-title').innerText = txt_tumu;
            renderMovies(moviesData);
        } else {
            document.getElementById('category-title').innerText = cat;
            renderMovies(moviesData.filter(f => f.category && f.category.includes(cat)));
        }
    }
    
    function searchMovies() {
        var val = document.getElementById('searchInput').value.toLowerCase();
        renderMovies(moviesData.filter(f => f.title.toLowerCase().includes(val)));
    }
</script>
</body>
</html>