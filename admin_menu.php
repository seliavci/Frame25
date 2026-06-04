<?php
session_start();
include 'baglan.php';

// --- [DERS NOTU: Çerez Zırhlama] ---
// Session çalınmasını (Hijacking) önlemek için HttpOnly ve SameSite bayrakları
session_set_cookie_params([
    'httponly' => true, 
    'secure' => true, 
    'samesite' => 'Strict'
]); //

// [Yönerge Madde 9]: Yetki Kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'Super Admin') {
    header("Location: login.php");
    exit();
}

// --- [DERS NOTU: Anti-CSRF Token Üretimi] ---
// Her oturum için tahmin edilemez, gizli bir jeton üret
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); //[cite: 6]
}

$mesaj = "";
$admin_name = $_SESSION['user_name'];

// --- [Yönerge Madde 12]: Yeni Menü Ekleme ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['menu_ekle'])) {
    
    // --- [DERS NOTU: CSRF Token Doğrulama] ---
    // Gelen jeton oturumdakiyle eşleşiyor mu?[cite: 6]
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Güvenlik ihlali tespit edildi: Geçersiz CSRF Token.'); //[cite: 6]
    }

    $baslik = htmlspecialchars(trim($_POST['baslik']));
    $url = htmlspecialchars(trim($_POST['url']));
    $sira = intval($_POST['sira']);

    $stmt = $baglanti->prepare("INSERT INTO menu (baslik, url, sira) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $baslik, $url, $sira);
    
    if ($stmt->execute()) {
        logTut($baglanti, "Menü Eklendi", "Yeni link: $baslik");
        $mesaj = "Menü öğesi başarıyla eklendi!";
    }
}

// --- [Yönerge Madde 12]: Menü Silme ---
if (isset($_GET['del_menu'])) {
    
    // --- [DERS NOTU: GET Üzerinden Silme Güvenliği] ---
    // Silme gibi kritik işlemler normalde POST ile yapılmalı veya token içermeli[cite: 6]
    if (!isset($_GET['token']) || !hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        die('Güvenlik hatası: Yetkisiz silme isteği.'); //[cite: 6]
    }

    $stmt = $baglanti->prepare("DELETE FROM menu WHERE id = ?");
    $stmt->bind_param("i", $_GET['del_menu']);
    if($stmt->execute()) {
        logTut($baglanti, "Menü Silindi", "ID: " . $_GET['del_menu']);
        $mesaj = "Menü öğesi kaldırıldı!";
    }
}

$menuler = $baglanti->query("SELECT * FROM menu ORDER BY sira ASC");
?>

<!-- ... (HTML Kısımları Aynı) ... -->

        <div class="form-container">
            <h2 style="font-family:'Bebas Neue'; margin-top:0;">YENİ NAVİGASYON ÖĞESİ EKLE</h2>
            <form method="POST">
                <!-- [DERS NOTU: Gizli CSRF Inputu] -->
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>"> <!--[cite: 6] -->
                
                <div style="display:flex; gap:15px;">
                    <div style="flex:2;">
                        <label>Menü Başlığı</label>
                        <input type="text" name="baslik" placeholder="Örn: Keşfet" required>
                    </div>
                    <div style="flex:2;">
                        <label>Bağlantı Adresi (URL)</label>
                        <input type="text" name="url" placeholder="kesfet.php" required>
                    </div>
                    <div style="flex:1;">
                        <label>Sıralama</label>
                        <input type="number" name="sira" value="1" required>
                    </div>
                </div>
                <button type="submit" name="menu_ekle" class="btn-add">MENÜYE EKLE</button>
            </form>
        </div>

        <table>
            <!-- ... (Thead Aynı) ... -->
            <tbody>
                <?php while($m = $menuler->fetch_assoc()): ?>
                <tr>
                    <td><b>#<?php echo $m['sira']; ?></b></td>
                    <td><?php echo htmlspecialchars($m['baslik']); ?></td>
                    <td style="color:#555;"><?php echo htmlspecialchars($m['url']); ?></td>
                    <td>
                        <!-- [DÜZENLEME]: Silme linkine güvenlik token'ı eklendi -->
                        <a href="admin_menu.php?del_menu=<?php echo $m['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" 
                           style="color:#b20710; font-weight:bold; text-decoration:none;" 
                           onclick="return confirm('Bu menü öğesini silmek istediğine emin misin?')">SİL</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>