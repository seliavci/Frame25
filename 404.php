<?php
session_start();
include 'baglan.php';

$site_adi = "FRAME 25";
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>404 - Kare Bulunamadı | <?php echo $site_adi; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Montserrat', sans-serif; background: #050505; color: #fff; margin: 0; display: flex; align-items: center; justify-content: center; height: 100vh; overflow: hidden; }
        .error-container { text-align: center; position: relative; z-index: 10; }
        .error-code { font-family: 'Bebas Neue'; font-size: 15em; line-height: 1; color: #b20710; margin: 0; letter-spacing: -5px; opacity: 0.8; }
        .error-msg { font-family: 'Bebas Neue'; font-size: 3em; margin-top: -20px; text-transform: uppercase; letter-spacing: 2px; }
        .error-desc { color: #888; font-size: 14px; margin-bottom: 30px; max-width: 400px; margin-left: auto; margin-right: auto; }
        .btn-back { display: inline-block; padding: 15px 30px; background: #b20710; color: #fff; text-decoration: none; font-weight: bold; border-radius: 4px; text-transform: uppercase; transition: 0.3s; font-size: 12px; }
        .btn-back:hover { background: #fff; color: #b20710; transform: scale(1.05); }
        /* Arkaya hafif bir sinema perdesi efekti */
        .overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: radial-gradient(circle, rgba(178,7,16,0.05) 0%, rgba(0,0,0,1) 80%); z-index: 1; }
    </style>
</head>
<body>

<div class="overlay"></div>

<div class="error-container">
    <p class="error-code">404</p>
    <h1 class="error-msg">SAHNE BULUNAMADI</h1>
    <p class="error-desc">Aradığınız kare veritabanımızda mevcut değil. Belki de bu sahne kurguda atılmıştır?</p>
    <a href="index.php" class="btn-back"><i class="fas fa-home"></i> Ana Sahneye Dön</a>
</div>

</body>
</html>