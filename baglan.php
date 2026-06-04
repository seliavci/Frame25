<?php
$host = "localhost";
$kullanici = "root";
$sifre = "root"; 
$veritabani = "frame25";

$baglanti = new mysqli($host, $kullanici, $sifre, $veritabani);

if ($baglanti->connect_error) {
    die("Bağlantı hatası: " . $baglanti->connect_error);
}

$baglanti->set_charset("utf8mb4");

// =====================================================================
// --- OTOMATİK TABLO KURULUMU (Yönerge Madde 2, 11, 12, 13, 18, 20, 31, 22) ---
// =====================================================================

// 1. Filmler Tablosu
$baglanti->query("CREATE TABLE IF NOT EXISTS filmler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    dir VARCHAR(255),
    price DECIMAL(10,2) DEFAULT 0.00,
    stok INT DEFAULT 10,
    category VARCHAR(255),
    description TEXT,
    img VARCHAR(255),
    release_date DATE,
    country_code VARCHAR(10),
    languages VARCHAR(100),
    duration INT,
    studios VARCHAR(100),
    platforms VARCHAR(255),
    trailer VARCHAR(255),
    is_showcase TINYINT(1) DEFAULT 0,
    is_daily_film TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// 2. Kullanıcılar Tablosu (Geliştirilmiş Profil ve Şifre Sıfırlama Alanları ile)
$baglanti->query("CREATE TABLE IF NOT EXISTS kullanicilar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad_soyad VARCHAR(100),
    eposta VARCHAR(100) UNIQUE,
    sifre VARCHAR(255),
    rol ENUM('Super Admin', 'Editor', 'Moderator', 'Kullanici') DEFAULT 'Kullanici',
    location VARCHAR(100) DEFAULT 'Bursa',
    website VARCHAR(255) DEFAULT 'http://letterboxd.com/selin',
    avatar VARCHAR(255) NULL,
    cover VARCHAR(255) NULL,
    reset_token VARCHAR(255) NULL,
    reset_token_expiry DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// [Yönerge Madde 22]: Mevcut tabloda sıfırlama sütunları yoksa otomatik ekle
$check_reset = $baglanti->query("SHOW COLUMNS FROM kullanicilar LIKE 'reset_token'");
if($check_reset && $check_reset->num_rows == 0) {
    $baglanti->query("ALTER TABLE kullanicilar ADD reset_token VARCHAR(255) NULL, ADD reset_token_expiry DATETIME NULL");
}

// 3. Haberler Tablosu
$baglanti->query("CREATE TABLE IF NOT EXISTS haberler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    baslik VARCHAR(255),
    icerik TEXT,
    resim VARCHAR(255),
    tarih TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// 4. Oyuncular Tablosu
$baglanti->query("CREATE TABLE IF NOT EXISTS oyuncular (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad_soyad VARCHAR(255),
    biyografi TEXT,
    resim VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// 5. Navigasyon Menüsü Tablosu (Yönerge Madde 12)
$baglanti->query("CREATE TABLE IF NOT EXISTS menu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    baslik VARCHAR(50) NOT NULL,
    url VARCHAR(100) NOT NULL,
    sira INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// 6. Genel Site Ayarları Tablosu (Yönerge Madde 13)
$baglanti->query("CREATE TABLE IF NOT EXISTS ayarlar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ayar_anahtar VARCHAR(50) UNIQUE,
    ayar_deger TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// 7. Slider Tablosu (Yönerge Madde 11)
$baglanti->query("CREATE TABLE IF NOT EXISTS slider (
    id INT AUTO_INCREMENT PRIMARY KEY,
    baslik VARCHAR(255) NOT NULL,
    aciklama VARCHAR(255),
    resim VARCHAR(255),
    link VARCHAR(255),
    sira INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// 8. Sepet Tablosu (Yönerge Madde 18)
$baglanti->query("CREATE TABLE IF NOT EXISTS sepet (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    film_id INT,
    adet INT DEFAULT 1,
    tarih TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// 9. Siparişler Tablosu (Yönerge Madde 20)
$baglanti->query("CREATE TABLE IF NOT EXISTS siparisler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    toplam_tutar DECIMAL(10,2),
    siparis_notu TEXT,
    durum VARCHAR(50) DEFAULT 'Hazırlanıyor',
    tarih TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// 10. Sistem Logları Tablosu (Yönerge Madde 31)
$baglanti->query("CREATE TABLE IF NOT EXISTS sistem_loglari (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kullanici_id INT,
    islem VARCHAR(255),
    detay TEXT,
    ip_adresi VARCHAR(50),
    tarih TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");


// =====================================================================
// --- VARSAYILAN VERİLERİN EKLENMESİ ---
// =====================================================================

// Admin Hesabı Kontrolü (Yönerge Madde 5 - Hashli Şifre)
$check = $baglanti->query("SELECT id FROM kullanicilar WHERE eposta = 'selin@frame25.com'");
if($check && $check->num_rows == 0) {
    $hash = password_hash("123456", PASSWORD_DEFAULT);
    $baglanti->query("INSERT INTO kullanicilar (ad_soyad, eposta, sifre, rol) VALUES ('Selin Avcı', 'selin@frame25.com', '$hash', 'Super Admin')");
}

// Varsayılan Menü Linkleri (Yönerge Madde 12)
$menu_check = $baglanti->query("SELECT id FROM menu");
if($menu_check && $menu_check->num_rows == 0) {
    $baglanti->query("INSERT INTO menu (baslik, url, sira) VALUES 
    ('Ana Sayfa', 'index.php', 1),
    ('Keşfet', 'kesfet.php', 2),
    ('Sepetim', 'cart.php', 3),
    ('Profilim', 'profile.php', 4)");
}

// Varsayılan Site Ayarları (Yönerge Madde 13)
$ayar_check = $baglanti->query("SELECT id FROM ayarlar");
if($ayar_check && $ayar_check->num_rows == 0) {
    $baglanti->query("INSERT INTO ayarlar (ayar_anahtar, ayar_deger) VALUES 
    ('site_adi', 'FRAME 25'),
    ('slogan', 'pure cinema experience'),
    ('logo', 'logo.png'),
    ('iletisim_mail', 'info@frame25.com'),
    ('facebook', 'https://facebook.com/frame25'),
    ('instagram', 'https://instagram.com/frame25')");
}

// Varsayılan Slider Verisi (Yönerge Madde 11)
$slider_check = $baglanti->query("SELECT id FROM slider");
if($slider_check && $slider_check->num_rows == 0) {
    $varsayilan_resim = 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?q=80&w=2070&auto=format&fit=crop';
    $baglanti->query("INSERT INTO slider (baslik, aciklama, resim, link, sira) VALUES 
    ('HAFTANIN SEÇKİSİ:<br>MODERN AUTEURS', 'KOLEKSİYONU GÖR', '$varsayilan_resim', 'kesfet.php?genre=Modern Auteurs', 1)");
}

// Ayarları diziye al (Her sayfada kullanım için)
$site_ayarlari = [];
$ayarlar_sonuc = $baglanti->query("SELECT * FROM ayarlar");
if($ayarlar_sonuc) {
    while($row = $ayarlar_sonuc->fetch_assoc()){
        $site_ayarlari[$row['ayar_anahtar']] = $row['ayar_deger'];
    }
}

// [Yönerge Madde 31]: Global Log Tutma Fonksiyonu
function logTut($baglanti, $islem, $detay = "") {
    $kullanici_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $baglanti->prepare("INSERT INTO sistem_loglari (kullanici_id, islem, detay, ip_adresi) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $kullanici_id, $islem, $detay, $ip);
    $stmt->execute();
}

// baglan.php en altına ekle:
$baglanti->query("CREATE TABLE IF NOT EXISTS oyuncular (id INT AUTO_INCREMENT PRIMARY KEY, ad_soyad VARCHAR(255), biyografi TEXT, resim TEXT, dogum_tarihi DATE)");
$baglanti->query("CREATE TABLE IF NOT EXISTS film_oyuncular (id INT AUTO_INCREMENT PRIMARY KEY, film_id INT, oyuncu_id INT, rol_adi VARCHAR(255))");
// Sepet ve diğer işlemler için gerekli tablolar
$baglanti->query("CREATE TABLE IF NOT EXISTS izlenenler (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, film_id INT, UNIQUE(user_id, film_id))");
$baglanti->query("CREATE TABLE IF NOT EXISTS begenilenler (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, film_id INT, UNIQUE(user_id, film_id))");

?>