<?php
session_start();
include 'baglan.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// --- SEPETE FİLM EKLEME İŞLEMI ---
if (isset($_GET['ekle'])) {
    $film_id = intval($_GET['ekle']);
    
    try {
        // [Yönerge Madde 3]: Prepared Statement ile Tam Güvenli Hale Getirildi
        // Hata Önleme: Sırf 'id' sütunu yüzünden çökmesin diye kontrol için 'film_id' seçiyoruz
        $stmt = $baglanti->prepare("SELECT film_id FROM sepet WHERE user_id = ? AND film_id = ?");
        $stmt->bind_param("ii", $user_id, $film_id);
        $stmt->execute();
        $kontrol = $stmt->get_result();
        
        if ($kontrol->num_rows > 0) {
            // Film zaten sepette varsa adedini 1 arttırıyoruz
            $up = $baglanti->prepare("UPDATE sepet SET adet = adet + 1 WHERE user_id = ? AND film_id = ?");
            $up->bind_param("ii", $user_id, $film_id);
            $up->execute();
        } else {
            // Film sepette yoksa ilk defa 1 adet olarak ekliyoruz
            $ins = $baglanti->prepare("INSERT INTO sepet (user_id, film_id, adet) VALUES (?, ?, 1)");
            $ins->bind_param("ii", $user_id, $film_id);
            $ins->execute();
        }
        header("Location: cart.php");
        exit();
        
    } catch (Exception $e) {
        // Eğer veritabanında sepet tablosu yoksa sessizce geçiştirmeyip hatayı yüzümüze söylesin
        die("<div style='background:#111; color:#b20710; padding:20px; font-family:sans-serif; text-align:center;'>
                <h3>Sepet Veritabanı Hatası Yakalandı!</h3>
                <p>Hata Detayı: <b>" . $e->getMessage() . "</b></p>
                <p><i>Eğer sepet tablosu bulunamadı diyorsa hemen aşağıdaki SQL kodunu phpMyAdmin'de çalıştırın.</i></p>
             </div>");
    }
}

// --- SEPETTEN FİLM SİLME İŞLEMİ ---
if (isset($_GET['sil'])) {
    $sepet_id = intval($_GET['sil']);
    try {
        // Çift Yönlü Güvenlik: cart.php'den sepet satır id'si de gelse, film_id de gelse nizamî siler
        $del = $baglanti->prepare("DELETE FROM sepet WHERE (id = ? OR film_id = ?) AND user_id = ?");
        $del->bind_param("iii", $sepet_id, $sepet_id, $user_id);
        $del->execute();
    } catch (Exception $e) {}
    header("Location: cart.php");
    exit();
}
?>