<?php
session_start();
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 1);
include 'baglan.php';

$site_adi = "FRAME 25";
$slogan = "pure cinema experience";

$kisi = null;
$filmografi = [];
$ortaklar = []; 

// URL'DEN GELEN ID (KADRO İÇİN)
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $kisi_id = intval($_GET['id']);
    $kisi_sorgu = $baglanti->query("SELECT * FROM oyuncular WHERE id = $kisi_id");
    
    if ($kisi_sorgu && $kisi_sorgu->num_rows > 0) {
        $kisi = $kisi_sorgu->fetch_assoc();
        // [DÜZENLEME]: Veritabanından revenue, awards ve is_short_film alanlarını da çekiyoruz
        $filmler_sql = "SELECT f.*, fo.rol_adi, fo.gorev FROM filmler f JOIN film_oyuncular fo ON f.id = fo.film_id WHERE fo.oyuncu_id = $kisi_id ORDER BY f.release_date DESC";
        $filmler_sonuc = $baglanti->query($filmler_sql);
        if ($filmler_sonuc) { while ($row = $filmler_sonuc->fetch_assoc()) { $filmografi[] = $row; } }
        
        $ortaklar_sql = "SELECT o.id, o.ad_soyad, o.resim, COUNT(fo.film_id) as ortak_film FROM film_oyuncular fo JOIN oyuncular o ON fo.oyuncu_id = o.id WHERE fo.film_id IN (SELECT film_id FROM film_oyuncular WHERE oyuncu_id = $kisi_id) AND fo.oyuncu_id != $kisi_id GROUP BY o.id ORDER BY ortak_film DESC LIMIT 4";
        $ortaklar_res = $baglanti->query($ortaklar_sql);
        if($ortaklar_res) { while($row = $ortaklar_res->fetch_assoc()) { $ortaklar[] = $row; } }
    }
} 
// URL'DEN GELEN İSİM (YÖNETMEN İÇİN)
elseif (isset($_GET['isim']) && !empty($_GET['isim'])) {
    $kisi_isim = $baglanti->real_escape_string(urldecode($_GET['isim']));
    $kisi_sorgu = $baglanti->query("SELECT * FROM oyuncular WHERE ad_soyad = '$kisi_isim' LIMIT 1");
    
    if ($kisi_sorgu && $kisi_sorgu->num_rows > 0) {
        $kisi = $kisi_sorgu->fetch_assoc();
    } else {
        $kisi = ['ad_soyad' => $kisi_isim, 'resim' => null, 'biyografi' => null];
    }

    $filmler_sql = "SELECT *, 'Yönetmen' as gorev FROM filmler WHERE dir = '$kisi_isim' ORDER BY release_date DESC";
    $filmler_sonuc = $baglanti->query($filmler_sql);
    if ($filmler_sonuc) { while ($row = $filmler_sonuc->fetch_assoc()) { $filmografi[] = $row; } }
    
    $ortaklar_sql = "SELECT o.id, o.ad_soyad, o.resim, COUNT(fo.film_id) as ortak_film FROM film_oyuncular fo JOIN oyuncular o ON fo.oyuncu_id = o.id JOIN filmler f ON fo.film_id = f.id WHERE f.dir = '$kisi_isim' GROUP BY o.id ORDER BY ortak_film DESC LIMIT 4";
    $ortaklar_res = $baglanti->query($ortaklar_sql);
    if($ortaklar_res) { while($row = $ortaklar_res->fetch_assoc()) { $ortaklar[] = $row; } }
}

if (!$kisi) { header("Location: index.php"); exit(); }

$basyapitlar = array_slice($filmografi, 0, 3);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($kisi['ad_soyad']); ?> | <?php echo $site_adi; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@400;500;700&display=swap" rel="stylesheet">
    
    <script>
        const savedTheme = localStorage.getItem('theme') || 'dark';
        if (savedTheme === 'light') { document.documentElement.setAttribute('data-theme', 'light'); }
    </script>

    <style>
        :root { --bg-color: #050505; --header-bg: #000000; --nav-bg: #111111; --card-bg: #111111; --text-main: #ffffff; --text-muted: #888888; --border-color: #222222; --accent: #b20710; }
        [data-theme="light"] { --bg-color: #f5f2eb; --header-bg: #e8e4d9; --nav-bg: #efede7; --card-bg: #ffffff; --text-main: #1a1a1a; --text-muted: #555555; --border-color: #dcd7ca; }

        * { box-sizing: border-box; } body { font-family: 'Montserrat', sans-serif; background-color: var(--bg-color); color: var(--text-main); margin: 0; padding: 0; line-height: 1.5; transition: 0.3s; }
        a { text-decoration: none; color: inherit; transition: 0.3s; }
        
        header { background-color: var(--header-bg); padding: 50px 0; text-align: center; border-bottom: 4px solid var(--accent); transition: 0.3s; }
        header h1 { margin: 0; font-family: 'Bebas Neue', cursive; font-size: 4.5em; color: var(--accent); letter-spacing: 5px; text-shadow: 2px 2px 4px rgba(0,0,0,0.3); }
        header p { color: var(--text-muted); font-size: 11px; text-transform: uppercase; letter-spacing: 12px; margin-top: -25px; margin-bottom: 20px; }
        
        nav { background-color: var(--nav-bg); border-bottom: 1px solid var(--border-color); width: 100%; height: 60px; position: sticky; top: 0; z-index: 1000; transition: 0.3s; }
        .nav-wrapper { width: 90%; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; height: 100%; }
        nav ul { display: flex; height: 100%; list-style: none; padding: 0; margin: 0; }
        nav li a { display: block; padding: 20px 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; color: var(--text-muted); }
        nav li a:hover, nav li a.active { color: #fff; background-color: var(--accent); }
        .theme-btn { background: none; border: 1px solid var(--border-color); color: var(--text-main); font-size: 13px; cursor: pointer; padding: 6px 10px; border-radius: 50%; transition: 0.3s;}
        .theme-btn:hover { background-color: var(--accent); color: #fff; border-color: var(--accent); }

        .container { width: 90%; max-width: 1200px; margin: 50px auto; }
        
        .person-hero { display: flex; gap: 40px; margin-bottom: 50px; background: var(--card-bg); padding: 40px; border-radius: 8px; border: 1px solid var(--border-color); border-top: 5px solid var(--accent); position: relative; overflow: hidden;}
        .person-photo { width: 220px; flex-shrink: 0; }
        .person-photo img { width: 100%; height: 280px; object-fit: cover; border-radius: 4px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        
        .person-info { flex-grow: 1; display: flex; flex-direction: column; justify-content: center;}
        .person-info h2 { font-family: 'Bebas Neue'; font-size: 4.5em; margin: 0 0 5px 0; color: var(--text-main); letter-spacing: 1px; line-height: 1; }
        .role-badge { display: inline-block; background: var(--accent); color: #fff; padding: 4px 10px; font-size: 10px; font-weight: bold; text-transform: uppercase; border-radius: 3px; letter-spacing: 1px; margin-bottom: 15px; width: fit-content;}
        
        .famous-quote { font-size: 13px; font-style: italic; color: #ffd700; border-left: 3px solid #ffd700; padding-left: 15px; margin-bottom: 20px; font-family: 'Georgia', serif;}
        
        .person-bio { color: var(--text-muted); font-size: 12px; line-height: 1.8; text-align: justify; margin-bottom: 20px; }
        
        .personal-info-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; background: var(--bg-color); padding: 15px; border-radius: 4px; border: 1px solid var(--border-color); margin-bottom: 20px;}
        .info-item h4 { margin: 0 0 3px 0; font-size: 9px; text-transform: uppercase; color: var(--text-muted); font-weight: bold;}
        .info-item p { margin: 0; font-size: 12px; color: var(--text-main); font-weight: 500;}

        .awards-box { background: rgba(255,215,0,0.05); border: 1px dashed rgba(255,215,0,0.3); padding: 12px 15px; border-radius: 4px; color: #ffd700; font-size: 11px; font-weight: bold; display: flex; align-items: center; gap: 10px; margin-bottom: 20px;}
        .awards-box i { font-size: 20px; }

        .stats-row { display: flex; gap: 20px; border-top: 1px dashed var(--border-color); padding-top: 20px; margin-top: auto;}
        .stat-item { color: var(--text-muted); font-size: 11px; text-transform: uppercase; font-weight: bold; letter-spacing: 1px;}
        .stat-item span { font-size: 18px; color: var(--text-main); font-family: 'Bebas Neue'; margin-right: 5px;}

        .content-grid { display: flex; gap: 40px; align-items: flex-start; }
        .main-column { flex: 7; }
        .side-column { flex: 3; }

        .section-head { font-family: 'Bebas Neue'; font-size: 2.2em; border-left: 6px solid var(--accent); padding-left: 15px; margin-bottom: 25px; color: var(--text-main); text-transform: uppercase; }
        
        .movie-grid { display: flex; flex-wrap: wrap; gap: 15px; }
        .movie-card { width: calc(33.33% - 10px); background-color: var(--card-bg); border-radius: 5px; padding: 10px; position: relative; border: 1px solid var(--border-color); transition: 0.3s; text-align: center; }
        .movie-card:hover { border-color: var(--accent); transform: translateY(-5px); }
        .role-tag { position: absolute; top: 15px; right: 15px; background: var(--accent); color: white; padding: 4px 8px; border-radius: 3px; font-size: 9px; font-weight: bold; z-index: 5; text-transform: uppercase; letter-spacing: 0.5px;}
        
        /* [YENİ]: Kısa Film Rozeti (Filmografi İçin) */
        .short-tag { position: absolute; top: 15px; left: 15px; background: #00e054; color: black; padding: 3px 6px; border-radius: 3px; font-size: 8px; font-weight: bold; z-index: 6; }

        .movie-card img { width: 100%; aspect-ratio: 2/3; object-fit: cover; border-radius: 3px; filter: brightness(0.9); transition: 0.3s;}
        
        .movie-card:hover img { filter: brightness(1); }
        .movie-card h3 { font-size: 12px; margin: 12px 0 3px; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;}
        .movie-card p { font-size: 10px; color: var(--text-muted); margin-bottom: 10px; }

        /* [YENİ]: Filmografi Başarı Göstergeleri */
        .success-metrics { display: flex; justify-content: center; gap: 8px; margin-top: 5px; font-size: 9px; color: #ffd700; }
        .success-metrics i { font-size: 10px; }

        .collab-box { background: var(--card-bg); padding: 20px; border-radius: 4px; border: 1px solid var(--border-color); border-top: 3px solid #00e054;}
        .collab-head { font-family: 'Bebas Neue'; font-size: 1.8em; color: #00e054; margin: 0 0 15px 0; border-bottom: 1px dashed var(--border-color); padding-bottom: 10px;}
        .collab-item { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; text-decoration: none; transition: 0.3s; padding: 5px; border-radius: 4px;}
        .collab-item:hover { background: var(--bg-color); padding-left: 10px;}
        .collab-img { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 1px solid var(--border-color);}
        .collab-info h4 { margin: 0 0 3px 0; font-size: 12px; color: var(--text-main);}
        .collab-info p { margin: 0; font-size: 10px; color: var(--text-muted);}

        footer { clear: both; background-color: var(--header-bg); padding: 40px 0; text-align: center; border-top: 1px solid var(--border-color); color: var(--text-muted); font-size: 11px; margin-top: 60px; transition: 0.3s; }
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
            <li><a href="index.php">Ana Sayfa</a></li>
            <li><a href="kesfet.php">Keşfet</a></li>
            <li><a href="cart.php">Sepetim</a></li>
            <li><a href="profile.php">Profilim</a></li>
        </ul>
        <button class="theme-btn" id="theme-toggle" title="Aydınlık/Karanlık Mod">
            <i class="fas fa-moon"></i>
        </button>
    </div>
</nav>

<div class="container">
    
    <div class="person-hero">
        <div class="person-photo">
            <?php $resim_yolu = (!empty($kisi['resim'])) ? $kisi['resim'] : 'https://ui-avatars.com/api/?name='.urlencode($kisi['ad_soyad']).'&background=111&color=fff&size=300'; ?>
            <img src="<?php echo htmlspecialchars($resim_yolu); ?>" alt="<?php echo htmlspecialchars($kisi['ad_soyad']); ?>">
        </div>
        
        <div class="person-info">
            <h2><?php echo htmlspecialchars($kisi['ad_soyad']); ?></h2>
            
            <?php 
            $ana_gorev = "Sinemacı";
            if (!empty($filmografi) && isset($filmografi[0]['rol_adi'])) { $ana_gorev = $filmografi[0]['gorev']; } 
            elseif(isset($_GET['isim'])) { $ana_gorev = "Yönetmen"; }
            ?>
            <span class="role-badge"><i class="fas fa-video" style="margin-right:5px;"></i> <?php echo htmlspecialchars($ana_gorev); ?></span>
            
            <?php if(!empty($kisi['meshur_soz'])): ?>
                <div class="famous-quote">"<?php echo htmlspecialchars($kisi['meshur_soz']); ?>"</div>
            <?php endif; ?>
            
            <div class="personal-info-grid">
                <div class="info-item"><h4>Doğum Tarihi</h4><p><?php echo !empty($kisi['dogum_tarihi']) ? htmlspecialchars($kisi['dogum_tarihi']) : '-'; ?></p></div>
                <div class="info-item"><h4>Doğum Yeri</h4><p><?php echo !empty($kisi['dogum_yeri']) ? htmlspecialchars($kisi['dogum_yeri']) : '-'; ?></p></div>
                <div class="info-item"><h4>Aktif Yıllar</h4><p><?php echo !empty($kisi['aktif_yillar']) ? htmlspecialchars($kisi['aktif_yillar']) : '-'; ?></p></div>
            </div>

            <?php if(!empty($kisi['oduller'])): ?>
                <div class="awards-box">
                    <i class="fas fa-trophy"></i>
                    <span><?php echo htmlspecialchars($kisi['oduller']); ?></span>
                </div>
            <?php endif; ?>

            <div class="person-bio">
                <?php 
                if (!empty($kisi['biyografi'])) { echo nl2br(htmlspecialchars($kisi['biyografi'])); } 
                else { echo "<span style='color:var(--text-muted); font-style:italic; font-size: 11px;'><i class='fas fa-info-circle'></i> Profil verisi panele henüz işlenmemiştir.</span>"; }
                ?>
            </div>
            
            <div class="stats-row">
                <div class="stat-item"><span><?php echo count($filmografi); ?></span> Eser</div>
                <div class="stat-item"><span><i class="fas fa-star" style="color:#ffd700;"></i></span> Frame 25 Arşivi</div>
            </div>
        </div>
    </div>

    <div class="content-grid">
        <div class="main-column">
            <h2 class="section-head">TÜM ESERLERİ</h2>
            <div class="movie-grid">
                <?php if (empty($filmografi)): ?>
                    <p style="color:var(--text-muted); font-size:12px; width:100%; text-align:center; padding:40px; border:1px dashed var(--border-color);">Bu sanatçının arşivimizde henüz bir filmi bulunmuyor.</p>
                <?php else: ?>
                    <?php foreach ($filmografi as $film): ?>
                        <div class="movie-card">
                            <?php if(isset($film['is_short_film']) && $film['is_short_film'] == 1): ?>
                                <div class="short-tag">KISA</div>
                            <?php endif; ?>

                            <div class="role-tag"><?php echo htmlspecialchars($film['gorev'] ?? 'Yönetmen'); ?></div>
                            <a href="detay.php?id=<?php echo $film['id']; ?>"><img src="<?php echo htmlspecialchars($film['img']); ?>"></a>
                            <h3><?php echo htmlspecialchars($film['title']); ?></h3>
                            <p><?php echo $film['release_date'] ? date("Y", strtotime($film['release_date'])) : 'Belirtilmedi'; ?></p>
                            
                            <div class="success-metrics">
                                <?php if(!empty($film['awards'])): ?>
                                    <span title="Ödüllü"><i class="fas fa-award"></i> Ödül</span>
                                <?php endif; ?>
                                <?php if(!empty($film['revenue'])): ?>
                                    <span style="color:#00e054;" title="Gişe Başarısı"><i class="fas fa-dollar-sign"></i> Gişe</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="side-column">
            <?php if(!empty($ortaklar)): ?>
            <div class="collab-box">
                <h3 class="collab-head">Sık Çalıştığı İsimler</h3>
                <?php foreach($ortaklar as $ortak): ?>
                    <a href="oyuncu.php?id=<?php echo $ortak['id']; ?>" class="collab-item">
                        <?php $o_avatar = !empty($ortak['resim']) ? $ortak['resim'] : 'https://ui-avatars.com/api/?name='.urlencode($ortak['ad_soyad']).'&background=111&color=fff'; ?>
                        <img src="<?php echo htmlspecialchars($o_avatar); ?>" class="collab-img">
                        <div class="collab-info">
                            <h4><?php echo htmlspecialchars($ortak['ad_soyad']); ?></h4>
                            <p><?php echo $ortak['ortak_film']; ?> Ortak Proje</p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<footer>&copy; 2026 FRAME 25 | Selin Avcı Tarafından Yapılmıştır.</footer>

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