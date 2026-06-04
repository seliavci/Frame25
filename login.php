<?php
session_start();
include 'baglan.php';

$mesaj = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $eposta = htmlspecialchars(trim($_POST['eposta']));
    $sifre = $_POST['sifre'];

    $stmt = $baglanti->prepare("SELECT * FROM kullanicilar WHERE eposta = ?");
    $stmt->bind_param("s", $eposta);
    $stmt->execute();
    $sonuc = $stmt->get_result();

    if ($sonuc->num_rows > 0) {
        $user = $sonuc->fetch_assoc();
        // Şifre Doğrulama (Madde 5)
        if (password_verify($sifre, $user['sifre'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['ad_soyad'];
            $_SESSION['user_role'] = $user['rol'];
            
            // [Yönerge Madde 31]: Başarılı giriş logu
            logTut($baglanti, "Giriş Yapıldı", "Kullanıcı: " . $user['ad_soyad'] . " sisteme giriş yaptı.");

            // Role göre yönlendir
            if ($user['rol'] == 'Super Admin' || $user['rol'] == 'Editor') {
                header("Location: admin.php");
            } else {
                header("Location: profile.php");
            }
            exit();
        } else {
            // [Yönerge Madde 31]: Hatalı şifre denemesi logu
            logTut($baglanti, "Hatalı Şifre", "E-posta: $eposta için yanlış şifre denendi.");
            $mesaj = "Hatalı şifre girdiniz.";
        }
    } else {
        // [Yönerge Madde 31]: Olmayan e-posta ile giriş denemesi
        logTut($baglanti, "Hatalı Giriş", "Sistemde olmayan e-posta ile deneme: $eposta");
        $mesaj = "Bu e-posta adresiyle kayıtlı kullanıcı bulunamadı.";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Giriş Yap | FRAME 25</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Montserrat', sans-serif; background: #050505; color: #fff; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: #111; padding: 40px; border-radius: 8px; border-top: 4px solid #b20710; width: 320px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        h1 { font-family: 'Bebas Neue'; font-size: 3em; color: #b20710; margin-bottom: 10px; }
        input { width: 100%; padding: 12px; margin-bottom: 15px; background: #000; border: 1px solid #222; color: #fff; border-radius: 4px; outline: none; box-sizing: border-box; }
        .btn-login { width: 100%; padding: 15px; background: #b20710; color: #fff; border: none; font-weight: bold; border-radius: 4px; cursor: pointer; text-transform: uppercase; }
        .error { color: #b20710; font-size: 11px; margin-bottom: 15px; }
        .links { margin-top: 20px; font-size: 11px; color: #555; }
        .links a { color: #b20710; text-decoration: none; }
    </style>
</head>
<body>
<div class="login-box">
    <h1>FRAME 25</h1>
    <?php if($mesaj): ?><div class="error"><?php echo $mesaj; ?></div><?php endif; ?>
    <form method="POST">
        <input type="email" name="eposta" placeholder="E-posta" required>
        <input type="password" name="sifre" placeholder="Şifre" required>
        <button type="submit" name="login" class="btn-login">Giriş Yap</button>
    </form>
    <div class="links">Hesabın yok mu? <a href="register.php">Hemen Kayıt Ol</a></div>
</div>
</body>
</html>