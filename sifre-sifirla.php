<?php
session_start();
include 'baglan.php';

$mesaj = "";
$hata = false;
$token_gecerli = false;

// 1. Token Kontrolü
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // [Yönerge Madde 3]: Token'ı ve süresini kontrol et
    $stmt = $baglanti->prepare("SELECT id, ad_soyad FROM kullanicilar WHERE reset_token = ? AND reset_token_expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $sonuc = $stmt->get_result();

    if ($sonuc->num_rows > 0) {
        $user = $sonuc->fetch_assoc();
        $token_gecerli = true;
    } else {
        $mesaj = "Geçersiz veya süresi dolmuş sıfırlama anahtarı.";
        $hata = true;
    }
} else {
    header("Location: login.php");
    exit();
}

// 2. Şifre Güncelleme İşlemi
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['sifre_guncelle']) && $token_gecerli) {
    $yeni_sifre = $_POST['sifre'];
    $yeni_sifre_tekrar = $_POST['sifre_tekrar'];

    if (strlen($yeni_sifre) < 6) {
        $mesaj = "Şifre en az 6 karakter olmalıdır.";
        $hata = true;
    } elseif ($yeni_sifre !== $yeni_sifre_tekrar) {
        $mesaj = "Şifreler birbiriyle eşleşmiyor.";
        $hata = true;
    } else {
        // [Yönerge Madde 5]: Yeni şifreyi hashle
        $hashed_password = password_hash($yeni_sifre, PASSWORD_DEFAULT);
        
        // Şifreyi güncelle ve token'ı temizle
        $update = $baglanti->prepare("UPDATE kullanicilar SET sifre = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?");
        $update->bind_param("si", $hashed_password, $user['id']);
        
        if ($update->execute()) {
            $mesaj = "Şifreniz başarıyla güncellendi! Giriş yapabilirsiniz.";
            // [Yönerge Madde 31]: Log kaydı
            logTut($baglanti, "Şifre Yenilendi", "Kullanıcı: " . $user['ad_soyad'] . " şifresini başarıyla sıfırladı.");
            header("Refresh:3; url=login.php");
        } else {
            $mesaj = "Bir hata oluştu, lütfen tekrar deneyin.";
            $hata = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Şifre Yenile | FRAME 25</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Montserrat', sans-serif; background: #050505; color: #fff; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .reset-box { background: #111; padding: 40px; border-radius: 8px; border-top: 4px solid #b20710; width: 350px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        h1 { font-family: 'Bebas Neue'; font-size: 3em; color: #b20710; margin-bottom: 10px; }
        p { color: #888; font-size: 11px; text-transform: uppercase; margin-bottom: 25px; }
        input { width: 100%; padding: 12px; margin-bottom: 15px; background: #000; border: 1px solid #222; color: #fff; border-radius: 4px; outline: none; box-sizing: border-box; }
        input:focus { border-color: #b20710; }
        .btn-update { width: 100%; padding: 15px; background: #b20710; color: #fff; border: none; font-weight: bold; border-radius: 4px; cursor: pointer; text-transform: uppercase; transition: 0.3s; }
        .btn-update:hover { background: #fff; color: #b20710; }
        .msg { font-size: 12px; margin-bottom: 20px; padding: 15px; border-radius: 4px; line-height: 1.6; }
        .msg-error { background: rgba(178,7,16,0.1); color: #b20710; border: 1px solid #b20710; }
        .msg-success { background: rgba(0, 224, 84, 0.1); color: #00e054; border: 1px solid #00e054; }
    </style>
</head>
<body>

<div class="reset-box">
    <h1>YENİDEN KURGULA</h1>
    <p>Hesabın için yeni ve güçlü bir şifre belirle.</p>

    <?php if($mesaj): ?>
        <div class="msg <?php echo $hata ? 'msg-error' : 'msg-success'; ?>">
            <?php echo $mesaj; ?>
        </div>
    <?php endif; ?>

    <?php if($token_gecerli): ?>
    <form method="POST">
        <input type="password" name="sifre" placeholder="Yeni Şifre" required>
        <input type="password" name="sifre_tekrar" placeholder="Yeni Şifre Tekrar" required>
        <button type="submit" name="sifre_guncelle" class="btn-update">Şifreyi Güncelle</button>
    </form>
    <?php else: ?>
        <a href="sifremi-unuttum.php" style="color:#888; font-size:11px; text-decoration:none;">Yeni bir link talep etmek için tıkla</a>
    <?php endif; ?>
</div>

</body>
</html>