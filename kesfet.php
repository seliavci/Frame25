<?php
session_start();
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 1);
include 'baglan.php';

$site_adi = "FRAME 25";
$slogan = "pure cinema experience";

// --- SQL FİLTRELEME MANTIĞI ---
$where = [];
if (isset($_GET['q']) && !empty($_GET['q'])) {
    $q = $baglanti->real_escape_string($_GET['q']);
    $where[] = "(title LIKE '%$q%' OR dir LIKE '%$q%')";
}
if (isset($_GET['genre']) && !empty($_GET['genre']) && $_GET['genre'] != 'Tümü') {
    $g = $baglanti->real_escape_string($_GET['genre']);
    $where[] = "category = '$g'";
}
if (isset($_GET['year']) && !empty($_GET['year'])) {
    $y = intval($_GET['year']);
    $where[] = "YEAR(release_date) = $y";
}

$order = "id DESC";
if (isset($_GET['sort']) && !empty($_GET['sort'])) {
    if ($_GET['sort'] == 'p_asc') $order = "price ASC";
    if ($_GET['sort'] == 'p_desc') $order = "price DESC";
    if ($_GET['sort'] == 't_asc') $order = "title ASC";
}

$sql = "SELECT * FROM filmler" . (count($where) > 0 ? " WHERE " . implode(" AND ", $where) : "") . " ORDER BY $order";
$sonuc = $baglanti->query($sql);

$turler_res = $baglanti->query("SELECT DISTINCT category FROM filmler");
$yillar_res = $baglanti->query("SELECT DISTINCT YEAR(release_date) as yil FROM filmler ORDER BY yil DESC");
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keşfet | <?php echo $site_adi; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    
    <script>
        const savedTheme = localStorage.getItem('theme') || 'dark';
        if (savedTheme === 'light') { document.documentElement.setAttribute('data-theme', 'light'); }
    </script>

    <style>
        /* ANA SAYFA İLE %100 AYNI DEĞİŞKENLER */
        :root {
            --bg-color: #050505;
            --header-bg: #000000;
            --nav-bg: #111111;
            --card-bg: #111111;
            --text-main: #ffffff;
            --text-muted: #888888;
            --border-color: #222222;
            --accent: #b20710;
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

        * { box-sizing: border-box; }
        body { font-family: 'Montserrat', sans-serif; background-color: var(--bg-color); color: var(--text-main); margin: 0; padding: 0; line-height: 1.5; transition: 0.3s; }
        a { text-decoration: none; color: inherit; transition: 0.3s; }
        
        /* HEADER */
        header { background-color: var(--header-bg); padding: 50px 0; text-align: center; border-bottom: 4px solid var(--accent); transition: 0.3s; }
        header h1 { margin: 0; font-family: 'Bebas Neue', cursive; font-size: 4.5em; color: var(--accent); letter-spacing: 5px; text-shadow: 2px 2px 4px rgba(0,0,0,0.3); }
        header p { color: var(--text-muted); font-size: 11px; text-transform: uppercase; letter-spacing: 12px; margin-top: -25px; margin-bottom: 20px; }
        
        /* NAV */
        nav { background-color: var(--nav-bg); border-bottom: 1px solid var(--border-color); width: 100%; height: 60px; position: sticky; top: 0; z-index: 1000; transition: 0.3s; }
        .nav-wrapper { width: 90%; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; height: 100%; }
        nav ul { display: flex; height: 100%; list-style: none; padding: 0; margin: 0; }
        nav li a { display: block; padding: 20px 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; color: var(--text-muted); }
        nav li a:hover, nav li a.active { color: #fff; background-color: var(--accent); }

        .theme-btn { background: none; border: 1px solid var(--border-color); color: var(--text-main); font-size: 13px; cursor: pointer; padding: 6px 10px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }

        /* CONTENT YAPISI */
        .container { width: 95%; max-width: 1400px; margin: 30px auto; display: flex; gap: 30px; }
        .main-content { width: 75%; }
        aside { width: 25%; padding-left: 20px; border-left: 1px solid var(--border-color); }
        
        .section-head { font-family: 'Bebas Neue'; font-size: 2.2em; border-left: 6px solid var(--accent); padding-left: 15px; margin-bottom: 20px; text-transform: uppercase; color: var(--text-main); }

        /* FİLTRE KUTUSU */
        .filter-box { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 5px; padding: 15px; margin-bottom: 25px; border-top: 3px solid var(--accent); }
        .filter-box h3 { margin: 0 0 12px 0; font-family: 'Bebas Neue'; font-size: 1.6em; color: var(--accent); }
        .f-group { margin-bottom: 12px; }
        .f-group label { display: block; font-size: 9px; font-weight: bold; color: var(--text-muted); text-transform: uppercase; margin-bottom: 5px; }
        input, select { width: 100%; padding: 8px; background: var(--bg-color); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 4px; outline: none; font-size: 11px; }
        
        .btn-apply { background: var(--accent); color: #fff; border: none; padding: 10px; width: 100%; border-radius: 4px; font-weight: bold; cursor: pointer; text-transform: uppercase; font-size: 10px; margin-top: 10px; }

        /* FİLM KARTLARI (GÜNCELLENDİ: Ana sayfadaki gibi 4'lü yapıldı, boyutlar küçüldü) */
        .movie-grid { display: flex; flex-wrap: wrap; gap: 20px; }
        .movie-card { width: calc(25% - 15px); background-color: var(--card-bg); border-radius: 5px; padding: 10px; position: relative; border: 1px solid var(--border-color); transition: 0.3s; text-align: center; }
        .movie-card:hover { border-color: var(--accent); transform: translateY(-5px); }
        
        .price-badge { position: absolute; top: 15px; right: 15px; background: var(--accent); color: white; padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: bold; z-index: 5; }
        
        .watchlist-btn { position: absolute; top: 15px; left: 15px; background: rgba(0,0,0,0.6); color: #fff; border: 1px solid rgba(255,255,255,0.2); padding: 4px 8px; border-radius: 3px; font-size: 12px; z-index: 5; cursor: pointer; transition: 0.3s; }
        .watchlist-btn:hover { background: var(--accent); border-color: var(--accent); }

        .movie-card img { width: 100%; height: 300px; object-fit: cover; border-radius: 3px; filter: brightness(0.9); transition: 0.3s;}
        .movie-card:hover img { filter: brightness(1); }
        
        .movie-card h3 { font-size: 13px; margin: 12px 0 3px; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;}
        .movie-card p { font-size: 10px; color: var(--text-muted); margin-bottom: 10px; }
        
        .btn-card { display: block; width: 100%; padding: 10px; background-color: var(--nav-bg); color: var(--text-main); font-weight: bold; text-transform: uppercase; border: 1px solid var(--border-color); font-size: 10px; border-radius: 3px; transition: 0.3s;}
        .movie-card:hover .btn-card { background-color: var(--accent); color: #fff; }

        footer { background-color: var(--header-bg); padding: 40px 0; text-align: center; border-top: 1px solid var(--border-color); color: var(--text-muted); font-size: 11px; }
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
            <li><a href="kesfet.php" class="active">Keşfet</a></li>
            <li><a href="cart.php">Sepetim</a></li>
            <li><a href="profile.php">Profilim</a></li>
            <li><a href="admin.php" style="color:var(--accent);">YÖNETİM</a></li>
        </ul>
        <button class="theme-btn" id="theme-toggle"><i class="fas fa-moon"></i></button>
    </div>
</nav>

<div class="container">
    <main class="main-content">
        <h2 class="section-head">Arşivi Keşfet</h2>
        
        <div class="movie-grid">
            <?php if($sonuc->num_rows > 0): while($f = $sonuc->fetch_assoc()): ?>
                <div class="movie-card">
                    <div class="watchlist-btn" title="İzleme Listeme Ekle"><i class="far fa-bookmark"></i></div>
                    <div class="price-badge"><?php echo $f['price']; ?> TL</div>
                    
                    <a href="detay.php?id=<?php echo $f['id']; ?>">
                        <img src="<?php echo htmlspecialchars($f['img']); ?>">
                    </a>
                    <h3><?php echo htmlspecialchars($f['title']); ?></h3>
                    <p><?php echo htmlspecialchars($f['dir']); ?></p>
                    <a href="detay.php?id=<?php echo $f['id']; ?>" class="btn-card">İncele</a>
                </div>
            <?php endwhile; else: ?>
                <p style="padding: 50px; color: var(--text-muted); font-size: 11px;">Sonuç bulunamadı.</p>
            <?php endif; ?>
        </div>
    </main>

    <aside>
        <div class="filter-box">
            <h3>Filtreler</h3>
            <form method="GET">
                <div class="f-group">
                    <label>Arama</label>
                    <input type="text" name="q" value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>" placeholder="Film adı...">
                </div>
                <div class="f-group">
                    <label>Tür</label>
                    <select name="genre">
                        <option value="">Tümü</option>
                        <?php while($t = $turler_res->fetch_assoc()): ?>
                            <option value="<?php echo $t['category']; ?>" <?php echo (isset($_GET['genre']) && $_GET['genre'] == $t['category']) ? 'selected' : ''; ?>><?php echo $t['category']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="f-group">
                    <label>Sıralama</label>
                    <select name="sort">
                        <option value="">Varsayılan</option>
                        <option value="p_asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'p_asc') ? 'selected' : ''; ?>>Fiyat (Artan)</option>
                        <option value="p_desc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'p_desc') ? 'selected' : ''; ?>>Fiyat (Azalan)</option>
                        <option value="t_asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 't_asc') ? 'selected' : ''; ?>>A-Z</option>
                    </select>
                </div>
                <button type="submit" class="btn-apply">Uygula</button>
                <a href="kesfet.php" style="display:block; text-align:center; color:var(--text-muted); font-size:9px; margin-top:15px; text-decoration:none; letter-spacing: 1px;">TEMİZLE</a>
            </form>
        </div>
    </aside>
</div>

<footer>
    &copy; 2026 <?php echo $site_adi; ?> - Selin Avcı Tarafından Yapılmıştır.
</footer>

<script>
    const toggleBtn = document.getElementById('theme-toggle');
    if (localStorage.getItem('theme') === 'light') { toggleBtn.innerHTML = '<i class="fas fa-sun"></i>'; }

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
</script>
</body>
</html>