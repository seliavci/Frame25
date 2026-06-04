<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'baglan.php';

$benim_id = 1; // Gerçek sistemde $_SESSION['user_id'] olacak.

// Mevcut bilgileri al
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : "";
$user_bio = isset($_SESSION['user_bio']) ? $_SESSION['user_bio'] : "";
$user_location = isset($_SESSION['user_location']) ? $_SESSION['user_location'] : "";
$user_link = isset($_SESSION['user_link']) ? $_SESSION['user_link'] : "";
$user_avatar = (isset($_SESSION['user_avatar']) && !empty($_SESSION['user_avatar'])) ? $_SESSION['user_avatar'] : "https://ui-avatars.com/api/?name=".urlencode($user_name)."&background=222&color=fff&size=200";

$hata = "";

// FORM GÖNDERİLDİĞİNDE
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Profil Fotoğrafını Kaldırma İşlemi
    if (isset($_POST['remove_avatar'])) {
        $_SESSION['user_avatar'] = ""; 
        header("Location: edit_profile.php");
        exit();
    }
    
    // 2. Yeni Profil Fotoğrafı Yükleme İşlemi (Hafta 5 - Dosya Yükleme)
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $izin_verilenler = ['jpg', 'jpeg', 'png', 'gif'];
        $dosya_uzantisi = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        
        if (in_array($dosya_uzantisi, $izin_verilenler)) {
            $yeni_isim = time() . "_" . $benim_id . "." . $dosya_uzantisi; // Örn: 1712214400_1.jpg
            $hedef_klasor = "uploads/";
            
            // Klasör yoksa oluştur
            if (!is_dir($hedef_klasor)) { mkdir($hedef_klasor, 0777, true); }
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $hedef_klasor . $yeni_isim)) {
                $_SESSION['user_avatar'] = $hedef_klasor . $yeni_isim;
                // Gerçek DB Update: $baglanti->query("UPDATE kullanicilar SET avatar='$hedef_klasor$yeni_isim' WHERE id=$benim_id");
            } else {
                $hata = "Dosya yüklenirken bir sorun oluştu.";
            }
        } else {
            $hata = "Sadece JPG, JPEG, PNG veya GIF yükleyebilirsiniz.";
        }
    }

    // 3. Metin Alanlarını Güncelleme
    if (isset($_POST['save_profile'])) {
        $yeni_ad = htmlspecialchars(trim($_POST['in_name']));
        $yeni_bio = htmlspecialchars(trim($_POST['in_bio']));
        $yeni_loc = htmlspecialchars(trim($_POST['in_loc']));
        $yeni_link = htmlspecialchars(trim($_POST['in_link']));
        
        if (!empty($yeni_ad)) {
            $_SESSION['user_name'] = $yeni_ad;
            $_SESSION['user_bio'] = $yeni_bio;
            $_SESSION['user_location'] = $yeni_loc;
            $_SESSION['user_link'] = $yeni_link;
            
            // Gerçek DB Update: $baglanti->query("UPDATE kullanicilar SET ad='$yeni_ad', bio='$yeni_bio' ... WHERE id=$benim_id");
            
            // Başarılı olursa profile geri dön
            header("Location: profile.php?success=1");
            exit();
        } else {
            $hata = "Kullanıcı adı boş bırakılamaz!";
        }
    }
}

$sayfa_basligi = "Profili Düzenle - FRAME 25";
include 'header.php';
?>

<style>
    .settings-container { width: 95%; max-width: 800px; margin: 50px auto; display: flex; gap: 40px; font-family: 'Montserrat', sans-serif;}
    .settings-sidebar { width: 25%; }
    .settings-sidebar a { display: block; padding: 12px 15px; color: var(--text-muted); font-size: 12px; font-weight: bold; border-radius: 4px; margin-bottom: 5px; transition: 0.3s;}
    .settings-sidebar a:hover { background: var(--card-bg); color: var(--text-main); }
    .settings-sidebar a.active { background: #b20710; color: #fff; }
    
    .settings-main { width: 75%; background: var(--card-bg); padding: 40px; border-radius: 6px; border: 1px solid var(--border-color); }
    .settings-main h2 { margin-top: 0; font-family: 'Bebas Neue'; font-size: 2.5em; border-bottom: 1px solid var(--border-color); padding-bottom: 15px; margin-bottom: 30px;}
    
    .avatar-edit-section { display: flex; align-items: center; gap: 30px; margin-bottom: 40px; padding-bottom: 30px; border-bottom: 1px dashed var(--border-color);}
    .avatar-preview { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 2px solid var(--border-color); }
    .avatar-actions { display: flex; flex-direction: column; gap: 10px; }
    
    /* Dosya Yükleme Buton Tasarımı */
    .custom-file-upload { display: inline-block; padding: 8px 15px; cursor: pointer; background: #00e054; color: #000; font-size: 11px; font-weight: bold; text-transform: uppercase; border-radius: 3px; text-align: center; transition: 0.3s;}
    .custom-file-upload:hover { opacity: 0.8; }
    input[type="file"] { display: none; } /* Orijinal çirkin butonu gizle */
    
    .btn-remove { background: transparent; border: 1px solid var(--border-color); color: var(--text-muted); padding: 8px 15px; font-size: 11px; font-weight: bold; cursor: pointer; text-transform: uppercase; border-radius: 3px; transition: 0.3s;}
    .btn-remove:hover { border-color: #b20710; color: #b20710; }

    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-size: 11px; font-weight: bold; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px; }
    .form-group input, .form-group textarea { width: 100%; padding: 12px; background: var(--bg-color); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 4px; outline: none; font-family: inherit;}
    .form-group input:focus, .form-group textarea:focus { border-color: #b20710; }
    
    .btn-save { padding: 15px 30px; background: #b20710; color: #fff; border: none; font-weight: bold; text-transform: uppercase; cursor: pointer; border-radius: 4px; font-size: 12px; float: right; transition: 0.3s;}
    .btn-save:hover { background: #fff; color: #b20710; }
    
    .msg-error { background: rgba(178, 7, 16, 0.1); border: 1px solid #b20710; color: #b20710; padding: 12px; margin-bottom: 20px; font-size: 12px; font-weight: bold; border-radius: 4px; text-align: center;}
</style>

<div class="settings-container">
    <div class="settings-sidebar">
        <a href="edit_profile.php" class="active">Profili Düzenle</a>
        <a href="#">Hesap Ayarları</a>
        <a href="#">Bildirimler</a>
        <a href="#">Gizlilik</a>
        <a href="profile.php" style="margin-top: 30px; color: #b20710;"><i class="fas fa-arrow-left"></i> Profile Dön</a>
    </div>

    <div class="settings-main">
        <h2>PROFİL AYARLARI</h2>
        
        <?php if($hata): ?>
            <div class="msg-error"><i class="fas fa-exclamation-triangle"></i> <?php echo $hata; ?></div>
        <?php endif; ?>

        <form method="POST" action="edit_profile.php" enctype="multipart/form-data">
            
            <div class="avatar-edit-section">
                <img src="<?php echo htmlspecialchars($user_avatar); ?>" class="avatar-preview" alt="Avatar Preview">
                <div class="avatar-actions">
                    <p style="margin:0 0 10px 0; font-size: 11px; color: var(--text-muted);">JPG, PNG veya GIF. Maksimum 2MB.</p>
                    <div style="display:flex; gap:10px;">
                        <label class="custom-file-upload">
                            <input type="file" name="avatar" accept="image/*" onchange="this.form.submit()">
                            <i class="fas fa-upload"></i> Fotoğraf Yükle
                        </label>
                        <button type="submit" name="remove_avatar" class="btn-remove" onclick="return confirm('Fotoğrafı kaldırmak istediğine emin misin?');"><i class="fas fa-trash"></i> Kaldır</button>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Kullanıcı Adı</label>
                <input type="text" name="in_name" value="<?php echo htmlspecialchars($user_name); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Kısa Biyografi</label>
                <textarea name="in_bio" rows="3" placeholder="Kendinden bahset..."><?php echo htmlspecialchars($user_bio); ?></textarea>
            </div>
            
            <div style="display: flex; gap: 20px;">
                <div class="form-group" style="flex: 1;">
                    <label>Konum</label>
                    <input type="text" name="in_loc" value="<?php echo htmlspecialchars($user_location); ?>" placeholder="Bursa, Türkiye">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Web Sitesi / Link</label>
                    <input type="url" name="in_link" value="<?php echo htmlspecialchars($user_link); ?>" placeholder="https://...">
                </div>
            </div>
            
            <div style="clear:both; margin-top: 30px;">
                <button type="submit" name="save_profile" class="btn-save"><i class="fas fa-save"></i> Değişiklikleri Kaydet</button>
            </div>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>
</body>
</html>