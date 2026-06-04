<?php
session_start();
include 'baglan.php';

$mesaj = "";
$hata = false;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['sifre_iste'])) {
    $eposta = htmlspecialchars(trim($_POST['eposta']));

    if (empty($eposta)) {
        $mesaj = "Lütfen e-posta adresinizi girin.";
        $hata = true;
    } else {
        // [Yönerge Madde 3]: Prepared Statement ile e-posta kontrolü
        $stmt = $baglanti->prepare("SELECT id, ad_soyad FROM kullanicilar WHERE eposta = ?");
        $stmt->bind_param("s", $eposta);
        $stmt->execute();
        $sonuc = $stmt->get_result();

        if ($sonuc->num_rows > 0) {
            $user = $sonuc->fetch_assoc();
            
            // [Yönerge Madde 22]: Benzersiz Token ve Geçerlilik Süresi (1 saat) üretme
            $token = bin2hex(random_bytes(32));
            $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

            // Veritabanını güncelle
            $update = $baglanti->prepare("UPDATE kullanicilar SET reset_token = ?, reset_token_expiry = ? WHERE id = ?");
            $update->bind_param("ssi", $token, $expiry, $user['id']);
            
            if ($update->execute()) {
                // Burada normalde mail gönderilir. Ödevde linki ekrana simüle ediyoruz:
                $reset_link = "sifre-sifirla.php?token=" . $token;
                $mesaj = "Sıfırlama linki oluşturuldu!<br><a href='$reset_link' style='color:#00e054; font-weight:bold;'>BURAYA TIKLAYARAK ŞİFRENİZİ DEĞİŞTİRİN</a>";
                
                // [Yönerge Madde 31]: Log kaydı
                logTut($baglanti, "Şifre Sıfırlama Talebi", "Kullanıcı: " . $user['ad_soyad']);
            }
        } else {
            $mesaj = "Bu e-posta adresiyle kayıtlı bir kullanıcı bulunamadı.";
            $hata = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Şifremi Unuttum | FRAME 25</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Montserrat', sans-serif; background: #050505; color: #fff; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .reset-box { background: #111; padding: 40px; border-radius: 8px; border-top: 4px solid #b20710; width: 350px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        h1 { font-family: 'Bebas Neue'; font-size: 3em; color: #b20710; margin-bottom: 10px; }
        p { color: #888; font-size: 11px; text-transform: uppercase; margin-bottom: 25px; line-height: 1.5; }
        input { width: 100%; padding: 12px; margin-bottom: 15px; background: #000; border: 1px solid #222; color: #fff; border-radius: 4px; outline: none; box-sizing: border-box; }
        input:focus { border-color: #b20710; }
        .btn-reset { width: 100%; padding: 15px; background: #b20710; color: #fff; border: none; font-weight: bold; border-radius: 4px; cursor: pointer; text-transform: uppercase; transition: 0.3s; }
        .btn-reset:hover { background: #fff; color: #b20710; }
        .msg { font-size: 12px; margin-bottom: 20px; padding: 15px; border-radius: 4px; line-height: 1.6; }
        .msg-error { background: rgba(178,7,16,0.1); color: #b20710; border: 1px solid #b20710; }
        .msg-success { background: rgba(0,224,84,0.1); color: #00e054; border: 1px solid #00e054; }
        .back-link { margin-top: 20px; font-size: 11px; }
        .back-link a { color: #555; text-decoration: none; transition: 0.3s; }
        .back-link a:hover { color: #fff; }
    </style>
</head>
<body>

<div class="reset-box">
    <h1>KAREYİ KURTAR</h1>
    <p>E-posta adresini gir, sana özel şifre sıfırlama anahtarını hemen oluşturalım.</p>

    <?php if($mesaj): ?>
        <div class="msg <?php echo $hata ? 'msg-error' : 'msg-success'; ?>">
            <?php echo $mesaj; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="email" name="eposta" placeholder="E-posta Adresiniz" required>
        <button type="submit" name="sifre_iste" class="btn-reset">Sıfırlama Linki Gönder</button>
    </form>

    <div class="back-link">
        <a href="login.php">← Giriş Sayfasına Dön</a>
    </div>
</div>

</body>
</html>