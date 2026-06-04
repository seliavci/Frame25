<?php
// Ortak değişkenler (Veritabanındaki merkezi ayarları kullan)
$site_adi = isset($site_ayarlari['site_adi']) ? $site_ayarlari['site_adi'] : "FRAME 25";
$slogan = isset($site_ayarlari['slogan']) ? $site_ayarlari['slogan'] : "pure cinema experience";
$sayfa_basligi = isset($sayfa_basligi) ? $sayfa_basligi : $site_adi;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($sayfa_basligi); ?></title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/lipis/flag-icons@7.0.0/css/flag-icons.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    
    <script>
        const savedTheme = localStorage.getItem('theme') || 'dark';
        if (savedTheme === 'light') { document.documentElement.setAttribute('data-theme', 'light'); }
    </script>

    <style>
        /* Senin gönderdiğin tüm CSS kodları burada kalacak (Boyut tasarrufu için buraya yazmıyorum) */
        :root { --bg-color: #050505; --header-bg: #000000; --nav-bg: #111111; --card-bg: #111111; --text-main: #ffffff; --text-muted: #888888; --border-color: #222222; }
        [data-theme="light"] { --bg-color: #f5f2eb; --header-bg: #e8e4d9; --nav-bg: #efede7; --card-bg: #ffffff; --text-main: #1a1a1a; --text-muted: #555555; --border-color: #dcd7ca; }
        * { box-sizing: border-box; }
        body { font-family: 'Montserrat', sans-serif; background-color: var(--bg-color); color: var(--text-main); margin: 0; padding: 0; line-height: 1.6; transition: background-color 0.3s, color 0.3s; }
        header { background-color: var(--header-bg); padding: 60px 0; text-align: center; border-bottom: 4px solid #b20710; transition: 0.3s; }
        header h1 { margin: 0; font-family: 'Bebas Neue', cursive; font-size: 5.5em; color: #b20710; letter-spacing: 5px; text-shadow: 2px 2px 4px rgba(0,0,0,0.3); }
        header p { color: var(--text-muted); font-size: 13px; text-transform: uppercase; letter-spacing: 12px; margin-top: -35px; margin-bottom: 25px; }
        nav { background-color: var(--nav-bg); border-bottom: 1px solid var(--border-color); width: 100%; height: 70px; position: sticky; top: 0; z-index: 1000; transition: 0.3s; }
        .nav-wrapper { width: 90%; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; height: 100%; }
        nav ul { display: flex; height: 100%; }
        nav li a { display: block; padding: 25px 20px; font-size: 13px; font-weight: bold; text-transform: uppercase; color: var(--text-muted); }
        nav li a:hover, nav li a.active { color: #fff; background-color: #b20710; }
        .search-area { display: flex; align-items: center; gap: 15px; }
        .search-area input { background-color: var(--bg-color); border: 1px solid var(--border-color); color: var(--text-main); padding: 10px 15px; border-radius: 20px; outline: none; width: 200px; transition: 0.3s; }
        .theme-btn { background: none; border: 1px solid var(--border-color); color: var(--text-main); font-size: 14px; cursor: pointer; padding: 8px 12px; border-radius: 50%; transition: 0.3s; display: flex; align-items: center; justify-content: center; }
        .theme-btn:hover { background-color: #b20710; color: #fff; border-color: #b20710; }
        .lang-switcher { display: flex; gap: 5px; border-right: 1px solid var(--border-color); padding-right: 15px;}
        .lang-btn { width: 24px; height: 24px; border-radius: 50%; overflow: hidden; opacity: 0.6; transition: 0.3s; border: 1px solid transparent;}
        .lang-btn.active { opacity: 1; border-color: #b20710; transform: scale(1.1); }
    </style>
</head>
<body>
<header>
    <h1><?php echo htmlspecialchars($site_adi); ?></h1>
    <p><?php echo htmlspecialchars($slogan); ?></p>
</header>