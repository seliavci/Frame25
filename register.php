<?php
session_start();
include 'baglan.php';

$mesaj = "";
$hata = false;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $ad_soyad = htmlspecialchars(trim($_POST['ad_soyad']));
    $eposta = htmlspecialchars(trim($_POST['eposta']));
    $sifre = $_POST['sifre'];
    $sifre_tekrar = $_POST['sifre_tekrar'];

    // Basit Kontroller
    if (empty($ad_soyad) || empty($eposta) || empty($sifre)) {
        $mesaj = "Lütfen tüm alanları doldurun.";
        $hata = true;
    } elseif ($sifre !== $sifre_tekrar) {
        $mesaj = "Şifreler birbiriyle eşleşmiyor.";
        $hata = true;
    } else {
        // E-posta daha önce alınmış mı? (Madde 3: Prepared Statements)
        $kontrol = $baglanti->prepare("SELECT id FROM kullanicilar WHERE eposta = ?");
        $kontrol->bind_param("s", $eposta);
        $kontrol->execute();
        if ($kontrol->get_result()->num_rows > 0) {
            $mesaj = "bu e-posta adresi zaten kullanımda.";
            $hata = true;
        } else {
            // Şifreyi Hashle (Madde 5: Güvenli Şifreleme)
            $hashed_password = password_hash($sifre, PASSWORD_DEFAULT);
            
            // Kaydı Yap
            $ins = $baglanti->prepare("INSERT INTO kullanicilar (ad_soyad, eposta, sifre, rol) VALUES (?, ?, ?, 'Kullanici')");
            $ins->bind_param("sss", $ad_soyad, $eposta, $hashed_password);
            
            if ($ins->execute()) {
                $mesaj = "Hesabınız başarıyla oluşturuldu! Giriş yapabilirsiniz.";
                header("Refresh:2; url=login.php");
            } else {
                $mesaj = "Bir hata oluştu, lütfen tekrar deneyin.";
                $hata = true;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Aramıza Katıl | FRAME 25</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Montserrat', sans-serif; background: #050505; color: #fff; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .reg-box { background: #111; padding: 40px; border-radius: 8px; border-top: 4px solid #b20710; width: 350px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        h1 { font-family: 'Bebas Neue'; font-size: 3em; color: #b20710; margin-bottom: 10px; letter-spacing: 2px; }
        p { color: #888; font-size: 11px; text-transform: uppercase; margin-bottom: 30px; letter-spacing: 1px; }
        input { width: 100%; padding: 12px; margin-bottom: 15px; background: #000; border: 1px solid #222; color: #fff; border-radius: 4px; outline: none; box-sizing: border-box; }
        input:focus { border-color: #b20710; }
        .btn-reg { width: 100%; padding: 15px; background: #b20710; color: #fff; border: none; font-weight: bold; border-radius: 4px; cursor: pointer; text-transform: uppercase; transition: 0.3s; }
        .btn-reg:hover { background: #fff; color: #b20710; }
        .msg { font-size: 11px; margin-bottom: 15px; padding: 10px; border-radius: 4px; }
        .msg-error { background: rgba(178,7,16,0.1); color: #b20710; border: 1px solid #b20710; }
        .msg-success { background: rgba(0,224,84,0.1); color: #00e054; border: 1px solid #00e054; }
        .footer-link { margin-top: 20px; font-size: 11px; color: #555; }
        .footer-link a { color: #b20710; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

<div class="reg-box">
    <h1>FRAME 25</h1>
    <p>Yeni Bir Sahne Başlat</p>

    <?php if($mesaj): ?>
        <div class="msg <?php echo $hata ? 'msg-error' : 'msg-success'; ?>">
            <?php echo $mesaj; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="ad_soyad" placeholder="Ad Soyad" required>
        <input type="email" name="eposta" placeholder="E-posta Adresi" required>
        <input type="password" name="sifre" placeholder="Şifre" required>
        <input type="password" name="sifre_tekrar" placeholder="Şifre Tekrar" required>
        <button type="submit" name="register" class="btn-reg">Kayıt Ol</button>
    </form>

    <div class="footer-link">
        Zaten bir hesabın var mı? <a href="login.php">Giriş Yap</a>
    </div>
</div>

</body>
</html>