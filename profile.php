<?php
session_start();
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 1);
include 'baglan.php';

// [Yönerge Madde 8]: Eğer login sistemin çalışıyorsa id'yi session'dan alır, yoksa varsayılan 1 olur.
$benim_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

// Yüklenen resimlerin kaydedileceği klasör
$upload_dir = 'uploads'; 
if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

// --- VERİTABANINDAN GÜNCEL BİLGİLERİ ÇEK (Madde 10: Dinamik İçerik) ---
$user_sorgu = $baglanti->prepare("SELECT * FROM kullanicilar WHERE id = ?");
$user_sorgu->bind_param("i", $benim_id);
$user_sorgu->execute();
$db_user = $user_sorgu->get_result()->fetch_assoc();

// --- KULLANICI BİLGİLERİ (Önce DB, yoksa Session, yoksa Varsayılan) ---
$user_name = !empty($db_user['ad_soyad']) ? $db_user['ad_soyad'] : (isset($_SESSION['user_name']) ? $_SESSION['user_name'] : "Selin");
$user_location = !empty($db_user['location']) ? $db_user['location'] : (isset($_SESSION['user_location']) ? $_SESSION['user_location'] : "Bursa");
$user_link = !empty($db_user['website']) ? $db_user['website'] : (isset($_SESSION['user_link']) ? $_SESSION['user_link'] : "http://letterboxd.com/selin");
$user_email = !empty($db_user['eposta']) ? $db_user['eposta'] : (isset($_SESSION['user_email']) ? $_SESSION['user_email'] : "selin@frame25.com");

$user_avatar = !empty($db_user['avatar']) ? $db_user['avatar'] : (isset($_SESSION['user_avatar']) && !empty($_SESSION['user_avatar']) ? $_SESSION['user_avatar'] : "https://ui-avatars.com/api/?name=".urlencode($user_name)."&background=random&color=fff&size=200");
$user_cover = !empty($db_user['cover']) ? $db_user['cover'] : (isset($_SESSION['user_cover']) && !empty($_SESSION['user_cover']) ? $_SESSION['user_cover'] : "https://images.unsplash.com/photo-1485846234645-a62644f84728?q=80&w=2059&auto=format&fit=crop");

$mesaj = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $yeni_ad = htmlspecialchars(trim($_POST['in_name']));
    $yeni_loc = htmlspecialchars(trim($_POST['in_loc']));
    $yeni_link = htmlspecialchars(trim($_POST['in_link']));
    $yeni_email = htmlspecialchars(trim($_POST['in_email']));
    $current_pass = $_POST['current_password'];
    $yeni_pass = $_POST['in_password'];
    
    if (!empty($yeni_ad)) {
        
        $sifre_hatasi = false;
        $sifre_guncelle = false;

        // Kullanıcı yeni şifre girmeye çalışıyorsa mevcut şifresini doğrula
        if (!empty($yeni_pass)) {
            if (empty($current_pass)) {
                $mesaj = "Şifrenizi değiştirmek için mevcut şifrenizi girmeniz gerekir!";
                $sifre_hatasi = true;
            } elseif (!password_verify($current_pass, $db_user['sifre'])) {
                $mesaj = "Mevcut şifreniz hatalı! Bilgiler güncellenemedi.";
                $sifre_hatasi = true;
            } else {
                $sifre_guncelle = true;
            }
        }

        // Eğer şifre aşamasında bir hata yaşanmadıysa güncellemeye geç
        if (!$sifre_hatasi) {
            $_SESSION['user_name'] = $yeni_ad;
            $_SESSION['user_location'] = $yeni_loc;
            $_SESSION['user_link'] = $yeni_link;
            $_SESSION['user_email'] = $yeni_email;
            
            $db_avatar = $user_avatar;
            $db_cover = $user_cover;

            // FOTOĞRAFLARI KALDIRMA MANTIĞI
            if (isset($_POST['remove_avatar']) && $_POST['remove_avatar'] == '1') {
                $_SESSION['user_avatar'] = '';
                $db_avatar = '';
            }
            if (isset($_POST['remove_cover']) && $_POST['remove_cover'] == '1') {
                $_SESSION['user_cover'] = '';
                $db_cover = '';
            }

            // PROFİL FOTOĞRAFI YÜKLEME MANTIĞI
            if (isset($_FILES['in_avatar_file']) && $_FILES['in_avatar_file']['error'] == 0) {
                $avatar_file = $_FILES['in_avatar_file'];
                $ext = strtolower(pathinfo($avatar_file['name'], PATHINFO_EXTENSION));
                if (in_array($ext, array('jpg', 'jpeg', 'png', 'webp', 'gif'))) {
                    $avatar_dest = $upload_dir . "/avatar_" . time() . "." . $ext;
                    if (move_uploaded_file($avatar_file['tmp_name'], $avatar_dest)) {
                        $_SESSION['user_avatar'] = $avatar_dest;
                        $db_avatar = $avatar_dest; 
                    }
                }
            }

            // KAPAK FOTOĞRAFI YÜKLEME MANTIĞI
            if (isset($_FILES['in_cover_file']) && $_FILES['in_cover_file']['error'] == 0) {
                $cover_file = $_FILES['in_cover_file'];
                $ext = strtolower(pathinfo($cover_file['name'], PATHINFO_EXTENSION));
                if (in_array($ext, array('jpg', 'jpeg', 'png', 'webp', 'gif'))) {
                    $cover_dest = $upload_dir . "/cover_" . time() . "." . $ext;
                    if (move_uploaded_file($cover_file['tmp_name'], $cover_dest)) {
                        $_SESSION['user_cover'] = $cover_dest;
                        $db_cover = $cover_dest; 
                    }
                }
            }

            // --- VERİTABANINA GÜVENLİ KAYIT ---
            if ($sifre_guncelle) {
                $hashed_pass = password_hash($yeni_pass, PASSWORD_DEFAULT);
                $up = $baglanti->prepare("UPDATE kullanicilar SET ad_soyad=?, location=?, website=?, avatar=?, cover=?, eposta=?, sifre=? WHERE id=?");
                $up->bind_param("sssssssi", $yeni_ad, $yeni_loc, $yeni_link, $db_avatar, $db_cover, $yeni_email, $hashed_pass, $benim_id);
            } else {
                $up = $baglanti->prepare("UPDATE kullanicilar SET ad_soyad=?, location=?, website=?, avatar=?, cover=?, eposta=? WHERE id=?");
                $up->bind_param("ssssssi", $yeni_ad, $yeni_loc, $yeni_link, $db_avatar, $db_cover, $yeni_email, $benim_id);
            }
            $up->execute();

            $user_name = $yeni_ad; 
            $user_location = $yeni_loc;
            $user_link = $yeni_link;
            $user_email = $yeni_email;
            $user_avatar = !empty($db_avatar) ? $db_avatar : "https://ui-avatars.com/api/?name=".urlencode($user_name)."&background=random&color=fff&size=200";
            $user_cover = !empty($db_cover) ? $db_cover : "https://images.unsplash.com/photo-1485846234645-a62644f84728?q=80&w=2059&auto=format&fit=crop";

            $mesaj = "Profil bilgileri başarıyla güncellendi!";
        }
    }
}

// FAVORİ EKLEME/SİLME (Geliştirilmiş ve Sütun Hataları Önlenmiş Sürüm)
if (isset($_GET['add_fav'])) {
    $fav_id = intval($_GET['add_fav']);
    try {
        // NOT: Eğer veritabanında sütun adın user_id değil de kullanici_id ise aşağıdakileri kullanici_id yap!
        $say_sorgu = $baglanti->prepare("SELECT COUNT(*) as c FROM favoriler WHERE user_id = ?");
        $say_sorgu->bind_param("i", $benim_id);
        $say_sorgu->execute();
        $say_row = $say_sorgu->get_result()->fetch_assoc();
        
        if ($say_row['c'] < 5) {
            // HATA ÖNLEME: Tabloda 'id' sütunu olmayabileceği için 'film_id' seçerek kontrol ediyoruz
            $kontrol = $baglanti->prepare("SELECT film_id FROM favoriler WHERE user_id = ? AND film_id = ?");
            $kontrol->bind_param("ii", $benim_id, $fav_id);
            $kontrol->execute();
            if ($kontrol->get_result()->num_rows == 0) { 
                $ins = $baglanti->prepare("INSERT INTO favoriler (user_id, film_id) VALUES (?, ?)");
                $ins->bind_param("ii", $benim_id, $fav_id);
                $ins->execute();
            }
        }
    } catch(Exception $e) {
        // Eğer hala eklenmiyorsa ne hatası verdiğini görmek için alttaki satırın yorumunu kaldırıp test edebilirsin:
        // die("SQL Hatası: " . $e->getMessage());
    } 
    header("Location: profile.php"); exit();
}

if (isset($_GET['remove_fav'])) {
    $fav_id = intval($_GET['remove_fav']);
    try { 
        $del = $baglanti->prepare("DELETE FROM favoriler WHERE user_id = ? AND film_id = ?");
        $del->bind_param("ii", $benim_id, $fav_id);
        $del->execute();
    } catch(Exception $e) {}
    header("Location: profile.php"); exit();
}

$favori_filmler = []; $istek_listesi = []; $kullanici_listeleri = [];
$izlenen_filmler = []; $gunluk_kayitlari = [];

try { $fav_res = $baglanti->query("SELECT f.* FROM favoriler fav JOIN filmler f ON fav.film_id = f.id WHERE fav.user_id = $benim_id LIMIT 5"); if($fav_res) { while($row = $fav_res->fetch_assoc()) { $favori_filmler[] = $row; } } } catch(Exception $e) {}
try { $watch_res = $baglanti->query("SELECT f.* FROM istek_listesi i JOIN filmler f ON i.film_id = f.id WHERE i.user_id = $benim_id LIMIT 4"); if($watch_res) { while($row = $watch_res->fetch_assoc()) { $istek_listesi[] = $row; } } } catch(Exception $e) {}
try { $liste_res = $baglanti->query("SELECT l.*, (SELECT COUNT(*) FROM liste_begeniler lb WHERE lb.liste_id = l.id) as begeni_sayisi, (SELECT COUNT(*) FROM liste_yorumlar ly WHERE ly.liste_id = l.id) as yorum_sayisi FROM listeler l WHERE user_id = $benim_id ORDER BY tarih DESC"); if($liste_res) { while($row = $liste_res->fetch_assoc()) { $kullanici_listeleri[] = $row; } } } catch(Exception $e) {}
try { $izl_sql = "SELECT f.* FROM izlenenler izl JOIN filmler f ON izl.film_id = f.id WHERE izl.user_id = $benim_id ORDER BY f.release_date DESC"; $izl_res = $baglanti->query($izl_sql); if($izl_res) { while($row = $izl_res->fetch_assoc()) { $izlenen_filmler[] = $row; } } } catch(Exception $e) {}
try { $gnl_sql = "SELECT f.*, g.izleme_tarihi FROM gunluk g JOIN filmler f ON g.film_id = f.id WHERE g.user_id = $benim_id ORDER BY g.izleme_tarihi DESC"; $gnl_res = $baglanti->query($gnl_sql); if($gnl_res) { while($row = $gnl_res->fetch_assoc()) { $gunluk_kayitlari[] = $row; } } } catch(Exception $e) {}

$begenilen_filmler = [];
try { $fav_res = $baglanti->query("SELECT f.* FROM favoriler fav JOIN filmler f ON fav.film_id = f.id WHERE fav.user_id = $benim_id LIMIT 5"); if($fav_res) { while($row = $fav_res->fetch_assoc()) { $favori_filmler[] = $row; } } } catch(Exception $e) {}

$yonetmen_sayilari = [];
foreach($izlenen_filmler as $f) {
    $dir = trim($f['dir']);
    if(!empty($dir)) { if(!isset($yonetmen_sayilari[$dir])) $yonetmen_sayilari[$dir] = 0; $yonetmen_sayilari[$dir]++; }
}
arsort($yonetmen_sayilari);
$favori_yonetmen = !empty($yonetmen_sayilari) ? key($yonetmen_sayilari) : null;
$favori_yonetmen_sayi = !empty($yonetmen_sayilari) ? current($yonetmen_sayilari) : 0;

$tur_sayilari = [];
foreach($izlenen_filmler as $f) {
    if(!empty($f['category'])) {
        $turler = explode(',', $f['category']); 
        foreach($turler as $t) { $t = trim($t); if(!isset($tur_sayilari[$t])) $tur_sayilari[$t] = 0; $tur_sayilari[$t]++; }
    }
}
arsort($tur_sayilari); 
$top_turler = array_slice($tur_sayilari, 0, 5); 

$stat_filmler = count($izlenen_filmler); 
$stat_bu_yil = count($gunluk_kayitlari); 
$stat_liste = count($kullanici_listeleri);

$tum_filmler_js = [];
$tum_res = $baglanti->query("SELECT id, title, img, dir FROM filmler");
if($tum_res) { while($row = $tum_res->fetch_assoc()) { $tum_filmler_js[] = $row; } }

$site_adi = "FRAME 25";
$slogan = "pure cinema experience";
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilim | <?php echo htmlspecialchars($site_adi); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        const savedTheme = localStorage.getItem('theme') || 'dark';
        if (savedTheme === 'light') { document.documentElement.setAttribute('data-theme', 'light'); }
    </script>

    <style>
        :root { --bg-color: #050505; --header-bg: #000000; --nav-bg: #111111; --card-bg: #111111; --text-main: #ffffff; --text-muted: #888888; --border-color: #222222; --accent: #b20710; }
        [data-theme="light"] { --bg-color: #f5f2eb; --header-bg: #e8e4d9; --nav-bg: #efede7; --card-bg: #ffffff; --text-main: #1a1a1a; --text-muted: #555555; --border-color: #dcd7ca; }

        * { box-sizing: border-box; } body { font-family: 'Montserrat', sans-serif; background-color: var(--bg-color); color: var(--text-main); margin: 0; padding: 0; line-height: 1.5; transition: 0.3s; }
        a { text-decoration: none; color: inherit; transition: 0.3s; }
        header { background-color: var(--header-bg); padding: 50px 0; text-align: center; border-bottom: 4px solid var(--accent); transition: 0.3s; }
        header h1 { margin: 0; font-family: 'Bebas Neue', cursive; font-size: 4.5em; color: var(--accent); letter-spacing: 5px; text-shadow: 2px 2px 4px rgba(0,0,0,0.3); }
        header p { color: var(--text-muted); font-size: 11px; text-transform: uppercase; letter-spacing: 12px; margin-top: -25px; margin-bottom: 20px; }
        
        nav { background-color: var(--nav-bg); border-bottom: 1px solid var(--border-color); width: 100%; height: 60px; position: sticky; top: 0; z-index: 1000; transition: 0.3s; }
        .nav-wrapper { width: 90%; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; height: 100%; }
        nav ul { display: flex; height: 100%; list-style: none; padding: 0; margin: 0; }
        nav li a { display: block; padding: 20px 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; color: var(--text-muted); }
        nav li a:hover, nav li a.active { color: #fff; background-color: var(--accent); }
        .theme-btn { background: none; border: 1px solid var(--border-color); color: var(--text-main); font-size: 13px; cursor: pointer; padding: 6px 10px; border-radius: 50%; transition: 0.3s;}
        .theme-btn:hover { background-color: var(--accent); color: #fff; border-color: var(--accent); }

        .lb-container { width: 95%; max-width: 960px; margin: 0 auto 40px auto; }
        .profile-cover { width: 100%; height: 220px; border-radius: 0 0 8px 8px; margin-bottom: -60px; position: relative;}
        .lb-header { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 1px solid var(--border-color); padding-bottom: 25px; margin-bottom: 15px; position: relative; z-index: 10; padding-left: 20px; padding-right: 20px;}
        .lb-user-info { display: flex; align-items: flex-end; gap: 20px; }
        .lb-avatar { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid var(--bg-color); box-shadow: 0 4px 10px rgba(0,0,0,0.3); background: var(--bg-color);}
        .lb-details { display: flex; flex-direction: column; justify-content: center; gap: 5px; margin-bottom: 10px;}
        .lb-name-row { display: flex; align-items: center; gap: 15px; }
        .lb-name-row h2 { margin: 0; font-family: 'Bebas Neue', cursive; font-size: 2.5em; color: var(--text-main); letter-spacing: 1px; }
        .btn-edit { background: var(--nav-bg); color: var(--text-muted); border: 1px solid var(--border-color); padding: 5px 12px; font-size: 9px; font-weight: bold; text-transform: uppercase; border-radius: 3px; cursor: pointer; transition: 0.3s;}
        .btn-edit:hover { color: var(--text-main); border-color: var(--text-main); }
        .lb-meta-row { display: flex; gap: 15px; font-size: 11px; color: var(--text-muted); }
        .lb-meta-row a { color: var(--text-muted); transition: 0.3s; }
        .lb-meta-row a:hover { color: var(--text-main); }
        
        .badges-area { display: flex; gap: 8px; margin-top: 5px; }
        .badge { background: rgba(178,7,16,0.1); border: 1px solid rgba(178,7,16,0.3); color: var(--accent); padding: 3px 8px; border-radius: 20px; font-size: 9px; font-weight: bold; display: flex; align-items: center; gap: 4px; text-transform: uppercase; letter-spacing: 0.5px;}
        .badge.gold { background: rgba(255,215,0,0.1); border-color: rgba(255,215,0,0.3); color: #ffd700; }

        .lb-stats { display: flex; gap: 30px; text-align: center; margin-bottom: 10px;}
        .lb-stat-box { display: flex; flex-direction: column; }
        .lb-stat-val { font-family: 'Bebas Neue'; font-size: 1.8em; color: var(--text-main); line-height: 1; }
        .lb-stat-lbl { font-size: 9px; color: var(--text-muted); text-transform: uppercase; margin-top: 6px; font-weight: bold; letter-spacing: 0.5px;}

        .msg-alert { border: 1px solid #00e054; color: #00e054; background: rgba(0, 224, 84, 0.05); padding: 8px 15px; margin: 20px auto; font-weight: bold; text-align: center; border-radius: 3px; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; width: fit-content;}

        .lb-tabs { display: flex; justify-content: center; gap: 20px; border-bottom: 1px solid var(--border-color); margin-bottom: 30px; }
        .tab-link { padding: 10px 0; color: var(--text-muted); font-size: 11px; font-weight: bold; text-transform: uppercase; border-bottom: 2px solid transparent; transition: 0.3s; margin-bottom: -1px; cursor: pointer; letter-spacing: 1px;}
        .tab-link:hover { color: var(--text-main); }
        .tab-link.active { color: var(--text-main); border-bottom: 2px solid #00e054; }

        .tab-pane { display: none; }
        .tab-pane.active { display: block; }
        .lb-content-grid { display: flex; gap: 40px; }
        .lb-main { width: 70%; }
        .lb-sidebar { width: 30%; }
        .lb-section-title { display: flex; justify-content: space-between; align-items: center; font-size: 11px; font-weight: bold; color: var(--text-muted); border-bottom: 1px solid var(--border-color); padding-bottom: 5px; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 1px; }

        .fav-grid { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 40px; }
        .lb-poster { width: calc(20% - 8px); position: relative; border-radius: 4px; overflow: hidden; border: 1px solid var(--border-color); transition: 0.3s; background: var(--bg-color);}
        .lb-poster:hover { border-color: #00e054; transform: translateY(-3px); }
        .lb-poster img { width: 100%; height: 210px; object-fit: cover; display: block; filter: brightness(0.9); transition: 0.3s;}
        .lb-poster:hover img { filter: brightness(1); }
        .empty-slot { display: flex; align-items: center; justify-content: center; height: 210px; border: 1px dashed var(--border-color); cursor: pointer; background: var(--card-bg); transition: 0.3s;}
        .empty-slot i { color: var(--text-muted); font-size: 20px; transition: 0.3s; }
        .empty-slot:hover { border-color: #00e054; }
        .empty-slot:hover i { color: #00e054; transform: scale(1.2); }
        
        .remove-fav-btn { position: absolute; top: 5px; right: 5px; background: rgba(0,0,0,0.7); color: #fff; border-radius: 50%; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; font-size: 9px; opacity: 0; transition: 0.3s; z-index: 10; border: 1px solid rgba(255,255,255,0.2);}
        .remove-fav-btn:hover { background: #b20710; border-color: #b20710; }
        .lb-poster:hover .remove-fav-btn { opacity: 1; }
        
        .goal-box { background: var(--card-bg); padding: 20px; border-radius: 4px; border: 1px solid var(--border-color); margin-bottom: 20px; text-align: center; border-top: 3px solid #00e054;}
        .goal-head { font-size: 11px; font-weight: bold; text-transform: uppercase; color: var(--text-muted); margin: 0 0 10px 0; letter-spacing: 1px;}
        .goal-bar-bg { width: 100%; height: 8px; background: var(--bg-color); border-radius: 10px; overflow: hidden; margin-bottom: 10px; border: 1px solid var(--border-color);}
        .goal-bar-fill { height: 100%; background: #00e054; width: 0%; transition: 1s ease-out;}
        .goal-text { font-size: 10px; color: var(--text-main); font-weight: bold;}
        
        .fav-director-box { background: var(--card-bg); padding: 15px; border-radius: 4px; border: 1px solid var(--border-color); margin-bottom: 20px; display: flex; align-items: center; gap: 15px; transition: 0.3s;}
        .fav-director-box:hover { border-color: var(--accent); }
        .fav-director-box img { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 1px solid var(--border-color);}
        .fav-director-info h4 { margin: 0 0 3px 0; font-size: 9px; text-transform: uppercase; color: var(--text-muted); font-weight: bold;}
        .fav-director-info p { margin: 0 0 2px 0; font-size: 12px; color: var(--text-main); font-weight: bold;}
        .fav-director-info span { font-size: 9px; color: var(--accent); font-weight: bold;}
        
        .chart-box { background: var(--card-bg); border: 1px solid var(--border-color); padding: 20px; border-radius: 4px; margin-bottom: 20px; text-align: center;}
        .chart-box h4 { margin: 0 0 15px 0; font-family: 'Bebas Neue'; font-size: 1.5em; color: var(--text-main); letter-spacing: 1px; border-bottom: 1px dashed var(--border-color); padding-bottom: 10px;}
        
        .diary-row { display: flex; align-items: center; border-bottom: 1px solid var(--border-color); padding: 10px 0; transition: 0.3s; text-decoration: none;}
        .diary-row:hover { background: var(--card-bg); padding-left: 10px; }
        .diary-date { width: 80px; font-size: 13px; color: var(--text-muted); font-family: 'Bebas Neue'; letter-spacing: 1px; }
        .diary-img { width: 35px; height: 50px; object-fit: cover; border-radius: 2px; margin-right: 15px;}
        .diary-info h4 { margin: 0; color: var(--text-main); font-size: 13px; font-weight: 500;}

        /* MODALLAR VE INPUT TASARIMLARI */
        .modal { display: none; position: fixed; z-index: 2000; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); }
        .modal-box { background: var(--card-bg); width: 400px; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); padding: 30px; border: 1px solid var(--border-color); border-radius: 4px; }
        .modal-box h3 { font-family: 'Bebas Neue'; font-size: 1.8em; color: var(--text-main); margin-top: 0; margin-bottom: 20px; letter-spacing: 1px; border-bottom: 2px solid var(--accent); padding-bottom: 10px;}
        .modal-box input[type="text"], .modal-box textarea { width: 100%; padding: 12px; margin-bottom: 15px; background: var(--bg-color); border: 1px solid var(--border-color); color: var(--text-main); outline: none; font-size: 11px; border-radius: 3px; font-family: 'Montserrat', sans-serif;}
        .modal-box input:focus { border-color: #00e054; }
        
        .file-input-label { display: block; width: 100%; padding: 12px; background: #0a0a0a; border: 1px dashed var(--border-color); color: var(--text-muted); font-size: 10px; font-weight: bold; text-align: center; text-transform: uppercase; letter-spacing: 1px; border-radius: 3px; cursor: pointer; transition: 0.3s; margin-bottom: 5px;}
        .file-input-label:hover { border-color: #ffd700; color: #ffd700; }
        .modal-box input[type="file"] { display: none; }
        
        .remove-photo-check { display: block; text-align: right; margin-bottom: 15px; font-size: 9px; color: var(--accent); cursor: pointer; font-weight: bold;}
        .remove-photo-check input { transform: scale(1.1); margin-right: 4px; cursor: pointer; }

        .btn-action { width: 100%; padding: 12px; background: #00e054; color: #000; border: none; font-weight: bold; cursor: pointer; text-transform: uppercase; font-size: 10px; letter-spacing: 1px; border-radius: 3px;}
        #favResults { display: flex; flex-direction: column; gap: 5px; max-height: 250px; overflow-y: auto; margin-bottom: 10px;}
        .fav-result-item { display: flex; align-items: center; gap: 15px; background: var(--bg-color); border: 1px solid var(--border-color); padding: 8px; cursor: pointer; text-decoration: none; color: var(--text-main); border-radius: 3px; transition: 0.2s;}
        .fav-result-item:hover { border-color: #00e054; background: rgba(0,224,84,0.05); }
        .fav-result-item img { width: 35px; height: 50px; object-fit: cover; border-radius: 2px;}
        .fav-result-item span { font-size: 12px; font-weight: bold; display: block;}
        .fav-result-item small { font-size: 9px; color: var(--text-muted); }

        /* Üyelik Yükseltme Kartı Tasarımı */
        .upgrade-card { background: linear-gradient(135deg, var(--card-bg) 0%, #1a1a1a 100%); border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; margin-top: 20px; text-align: center; position: relative; overflow: hidden; transition: 0.3s; }
        .upgrade-card:hover { border-color: #ffd700; transform: translateY(-3px); }
        .upgrade-title { font-family: 'Bebas Neue'; font-size: 1.4em; color: #ffd700; letter-spacing: 2px; margin-bottom: 10px; }
        .upgrade-text { font-size: 10px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; line-height: 1.4; }
        .upgrade-options { display: flex; gap: 10px; justify-content: center; }
        .btn-upgrade { flex: 1; padding: 10px; font-size: 9px; font-weight: bold; text-transform: uppercase; border-radius: 4px; cursor: pointer; transition: 0.3s; border: 1px solid var(--border-color); background: var(--bg-color); color: var(--text-main); }
        .btn-upgrade.sekans:hover { border-color: #00e054; color: #00e054; }
        .btn-upgrade.master { background: #ffd700; color: #000; border-color: #ffd700; }
        .btn-upgrade.master:hover { background: #fff; border-color: #fff; }

        footer { background-color: var(--header-bg); padding: 40px 0; text-align: center; border-top: 1px solid var(--border-color); color: var(--text-muted); font-size: 11px; margin-top: 60px; transition: 0.3s; }
    </style>
</head>
<body>

<header>
    <h1><?php echo htmlspecialchars($site_adi); ?></h1>
    <p><?php echo htmlspecialchars($slogan); ?></p>
</header>

<nav>
    <div class="nav-wrapper">
        <ul>
            <li><a href="index.php">Ana Sayfa</a></li>
            <li><a href="kesfet.php">Keşfet</a></li>
            <li><a href="cart.php">Sepetim</a></li>
            <li><a href="profile.php" class="active">Profilim</a></li>
        </ul>
        <button class="theme-btn" id="theme-toggle" title="Aydınlık/Karanlık Mod">
            <i class="fas fa-moon"></i>
        </button>
    </div>
</nav>

<div class="lb-container">
    <div class="profile-cover" style="background: linear-gradient(to bottom, rgba(0,0,0,0.1), var(--bg-color)), url('<?php echo htmlspecialchars($user_cover); ?>') center/cover;"></div>

    <?php if($mesaj): ?>
        <div id="basariMesaji" class="msg-alert"><i class="fas fa-check-circle" style="margin-right:5px;"></i> <?php echo htmlspecialchars($mesaj); ?></div>
        <script>setTimeout(function() { var msgBox = document.getElementById('basariMesaji'); if(msgBox) msgBox.style.display = 'none'; }, 2000);</script>
    <?php endif; ?>

    <div class="lb-header">
        <div class="lb-user-info">
            <img src="<?php echo htmlspecialchars($user_avatar); ?>" class="lb-avatar" alt="Profil">
            <div class="lb-details">
                <div class="lb-name-row">
                    <h2><?php echo htmlspecialchars($user_name); ?></h2>
                    <button onclick="openModal('editModal')" class="btn-edit">Profili Düzenle</button>
                </div>
                <div class="lb-meta-row">
                    <?php if(!empty($user_location)): ?><span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($user_location); ?></span><?php endif; ?>
                    <?php if(!empty($user_link)): ?><span><i class="fas fa-link"></i> <a href="<?php echo htmlspecialchars($user_link); ?>" target="_blank"><?php echo htmlspecialchars(substr($user_link, 0, 25)); ?>...</a></span><?php endif; ?>
                </div>
                <div class="badges-area">
                    <?php if($stat_filmler >= 5): ?>
                        <div class="badge gold" title="Platformda Aktif İzleyici"><i class="fas fa-ticket-alt"></i> SİNEFİL</div>
                    <?php endif; ?>
                    <?php if(array_key_exists('Dram', $top_turler) && $top_turler['Dram'] >= 2): ?>
                        <div class="badge" title="Dram türünü çok seviyor"><i class="fas fa-theater-masks"></i> DRAM USTASI</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="lb-stats">
            <div class="lb-stat-box"><span class="lb-stat-val"><?php echo number_format($stat_filmler); ?></span><span class="lb-stat-lbl">FİLM İZLEDİ</span></div>
            <div class="lb-stat-box"><span class="lb-stat-val"><?php echo number_format($stat_bu_yil); ?></span><span class="lb-stat-lbl">BU YIL</span></div>
            <div class="lb-stat-box"><span class="lb-stat-val"><?php echo number_format($stat_liste); ?></span><span class="lb-stat-lbl">LİSTESİ VAR</span></div>
        </div>
    </div>

    <div class="lb-tabs">
        <a class="tab-link active" onclick="switchTab(event, 'tab-profil')">Profil</a>
        <a class="tab-link" onclick="switchTab(event, 'tab-filmler')">Filmler</a>
        <a class="tab-link" onclick="switchTab(event, 'tab-gunluk')">Günlük</a>
        <a class="tab-link" onclick="switchTab(event, 'tab-listeler')">Listeler</a>
    </div>

    <div id="tab-profil" class="tab-pane active">
        <div class="lb-content-grid">
            <div class="lb-main">
                <h3 class="lb-section-title">Favori Filmlerim</h3>
                <div class="fav-grid">
                    <?php for ($i = 0; $i < 5; $i++): ?>
                        <?php if (isset($favori_filmler[$i])): ?>
                            <div class="lb-poster">
                                <a href="profile.php?remove_fav=<?php echo $favori_filmler[$i]['id']; ?>" class="remove-fav-btn" title="Favorilerden Çıkar"><i class="fas fa-times"></i></a>
                                <a href="detay.php?id=<?php echo $favori_filmler[$i]['id']; ?>"><img src="<?php echo htmlspecialchars($favori_filmler[$i]['img']); ?>"></a>
                            </div>
                        <?php else: ?>
                            <div class="lb-poster empty-slot" onclick="openModal('favModal')" title="Favori Film Ekle"><i class="fas fa-plus"></i></div>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>

                <h3 class="lb-section-title" style="margin-top: 40px; border-left-color: #ffd700;">Klaketlediklerim</h3>
                <div class="fav-grid">
                    <?php if(empty($begenilen_filmler)): ?>
                        <p style="color:var(--text-muted); font-size:11px; width:100%; text-align:center; padding:20px; border:1px dashed var(--border-color);">Henüz bir filmi klaketlemedin.</p>
                    <?php else: ?>
                        <?php foreach($begenilen_filmler as $film): ?>
                            <div class="lb-poster" style="width: calc(25% - 10px); position:relative;">
                                <div style="position:absolute; top:5px; right:5px; background:rgba(0,0,0,0.7); color:#ffd700; padding:4px; border-radius:3px; font-size:10px; z-index:5;"><i class="fas fa-film"></i></div>
                                <a href="detay.php?id=<?php echo $film['id']; ?>">
                                    <img src="<?php echo htmlspecialchars($film['img']); ?>" style="height:230px;">
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <h3 class="lb-section-title" style="margin-top: 40px; border-left-color: #00a8ff;">Yakında İzlenecekler</h3>
                <div class="fav-grid">
                    <?php if(empty($istek_listesi)): ?>
                        <p style="color:var(--text-muted); font-size:11px; width:100%; text-align:center; padding:20px; border:1px dashed var(--border-color);">İzleme listen şu an boş.</p>
                    <?php else: ?>
                        <?php foreach($istek_listesi as $film): ?>
                            <div class="lb-poster" style="width: calc(25% - 10px);">
                                <a href="detay.php?id=<?php echo $film['id']; ?>">
                                    <img src="<?php echo htmlspecialchars($film['img']); ?>" style="height:230px;">
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <h3 class="lb-section-title" style="margin-top: 40px;">Son Günlük Kayıtları</h3>
                <div class="fav-grid">
                    <?php foreach(array_slice($gunluk_kayitlari, 0, 4) as $film): ?>
                        <div class="lb-poster" style="width: calc(25% - 10px);">
                            <a href="detay.php?id=<?php echo $film['id']; ?>">
                                <img src="<?php echo htmlspecialchars($film['img']); ?>" style="height:230px;">
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="lb-sidebar">
                
                <?php 
                $hedef = 100; 
                $yuzde = ($stat_bu_yil / $hedef) * 100;
                if($yuzde > 100) $yuzde = 100;
                ?>
                <div class="goal-box">
                    <h4 class="goal-head">2026 SİNEMA HEDEFİ</h4>
                    <div class="goal-bar-bg">
                        <div class="goal-bar-fill" style="width: <?php echo $yuzde; ?>%;"></div>
                    </div>
                    <div class="goal-text"><?php echo $stat_bu_yil; ?> / <?php echo $hedef; ?> FİLM (%<?php echo round($yuzde); ?>)</div>
                </div>

                <?php if($favori_yonetmen): ?>
                <div class="fav-director-box">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($favori_yonetmen); ?>&background=111&color=fff">
                    <div class="fav-director-info">
                        <h4>En Çok İzlenen Yönetmen</h4>
                        <p><a href="oyuncu.php?isim=<?php echo urlencode($favori_yonetmen); ?>" style="color:inherit;"><?php echo htmlspecialchars($favori_yonetmen); ?></a></p>
                        <span><?php echo $favori_yonetmen_sayi; ?> FİLM</span>
                    </div>
                </div>
                <?php endif; ?>

                <h3 class="lb-section-title" style="margin-top:20px;">2026 İstatistikleri</h3>
                
                <div class="chart-box">
                    <h4>Favori Türlerim</h4>
                    <canvas id="genreChart" height="200"></canvas>
                </div>
                
                <div class="chart-box">
                    <h4>Aylık İzleme Hızı</h4>
                    <canvas id="monthChart" height="150"></canvas>
                </div>

                <!-- Üyelik Yükseltme Alanı -->
                <div class="upgrade-card">
                    <div class="upgrade-title"><i class="fas fa-crown"></i> DENEYİMİ YÜKSELT</div>
                    <p class="upgrade-text">Arşivini genişlet ve <br> sınırsız özelliklere erişim sağla.</p>
                    <div class="upgrade-options">
                        <button onclick="alert('Sekans planına yönlendiriliyorsunuz...')" class="btn-upgrade sekans">
                            SEKANS OL
                        </button>
                        <button onclick="alert('Master planına yönlendiriliyorsunuz...')" class="btn-upgrade master">
                            MASTER'A GEÇ
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div id="tab-filmler" class="tab-pane">
        <h3 class="lb-section-title">Tüm İzlenen Filmler (<?php echo count($izlenen_filmler); ?>)</h3>
        <div class="fav-grid">
            <?php foreach($izlenen_filmler as $film): ?>
                <div class="lb-poster" style="width: calc(16.66% - 10px);"> 
                    <a href="detay.php?id=<?php echo $film['id']; ?>"><img src="<?php echo htmlspecialchars($film['img']); ?>" style="height:180px;"></a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="tab-gunluk" class="tab-pane">
        <h3 class="lb-section-title">Günlük Kayıtları</h3>
        <div>
            <?php foreach($gunluk_kayitlari as $film): ?>
                <?php 
                $aylar = array(1=>"Oca", 2=>"Şub", 3=>"Mar", 4=>"Nis", 5=>"May", 6=>"Haz", 7=>"Tem", 8=>"Ağu", 9=>"Eyl", 10=>"Eki", 11=>"Kas", 12=>"Ara");
                $tarih = strtotime($film['izleme_tarihi']);
                $gun = date("d", $tarih);
                $ay = $aylar[(int)date("m", $tarih)];
                ?>
                <a href="detay.php?id=<?php echo $film['id']; ?>" class="diary-row">
                    <div class="diary-date"><?php echo $gun; ?> <?php echo $ay; ?></div>
                    <img src="<?php echo htmlspecialchars($film['img']); ?>" class="diary-img">
                    <div class="diary-info">
                        <h4><?php echo htmlspecialchars($film['title']); ?></h4>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="tab-listeler" class="tab-pane">
        <h3 class="lb-section-title">Oluşturduğun Listeler</h3>
        <div style="text-align:center; padding:50px 20px; border:1px dashed var(--border-color); border-radius:4px; background:var(--card-bg);">
            <i class="fas fa-list-ul" style="font-size:30px; color:var(--text-muted); margin-bottom:15px;"></i>
            <h4 style="margin:0 0 10px 0; color:var(--text-main); font-size:14px;">Henüz bir liste oluşturmadınız.</h4>
            <p style="font-size:11px; color:var(--text-muted); margin-bottom:20px;">Favori yönetmenlerinizi, aktörlerinizi veya tematik film derlemelerinizi oluşturun.</p>
            <button onclick="alert('Yakında: Liste Oluşturma Modülü')" style="background:var(--accent); color:#fff; border:none; padding:10px 20px; font-size:10px; font-weight:bold; text-transform:uppercase; border-radius:3px; cursor:pointer;">+ Yeni Liste Oluştur</button>
        </div>
    </div>
</div>

<div id="editModal" class="modal">
    <div class="modal-box" style="width: 420px; max-height: 85vh; overflow-y: auto; padding-right: 5px;">
        <h3>Profili Düzenle</h3>
        <form method="POST" enctype="multipart/form-data">
            
            <input type="text" name="in_name" value="<?php echo htmlspecialchars($user_name); ?>" placeholder="Adınız" required>
            <input type="text" name="in_loc" value="<?php echo htmlspecialchars($user_location); ?>" placeholder="Konum (Şehir, Ülke)">
            <input type="text" name="in_link" value="<?php echo htmlspecialchars($user_link); ?>" placeholder="Web Sitesi veya Profil Linki">
            
            <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 20px 0;">

            <div style="text-align: left; margin-bottom: 15px;">
                <label style="font-size: 10px; color: var(--text-muted); text-transform: uppercase; font-weight: bold; letter-spacing: 1px;">E-Posta Adresiniz</label>
                <input type="email" name="in_email" value="<?php echo htmlspecialchars($user_email ?? ''); ?>" required style="width: 100%; background: #111111; border: 1px solid var(--border-color); color: var(--text-main); padding: 12px; margin-top: 6px; border-radius: 4px; font-size: 13px; outline: none; transition: 0.3s;">
            </div>

            <h4 style="color:#b20710; font-size:11px; margin:20px 0 10px 0; text-transform:uppercase; font-weight:bold; letter-spacing: 1px; text-align: left;">Şifreni Değiştir</h4>

            <div style="text-align: left; margin-bottom: 15px;">
                <label style="font-size: 10px; color: var(--text-muted); text-transform: uppercase; font-weight: bold; letter-spacing: 1px;">Mevcut Şifreniz</label>
                <input type="password" name="current_password" placeholder="Değişiklik için mevcut şifrenizi girin" style="width: 100%; background: #111111; border: 1px solid var(--border-color); color: var(--text-main); padding: 12px; margin-top: 6px; border-radius: 4px; font-size: 13px; outline: none; transition: 0.3s;">
            </div>

            <div style="text-align: left; margin-bottom: 20px;">
                <label style="font-size: 10px; color: var(--text-muted); text-transform: uppercase; font-weight: bold; letter-spacing: 1px;">Yeni Şifre</label>
                <input type="password" name="in_password" placeholder="Yeni şifrenizi girin" style="width: 100%; background: #111111; border: 1px solid var(--border-color); color: var(--text-main); padding: 12px; margin-top: 6px; border-radius: 4px; font-size: 13px; outline: none; transition: 0.3s;">
            </div>

            <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 20px 0;">
            
            <h4 style="color:var(--text-muted); font-size:10px; margin:15px 0 5px 0; text-transform:uppercase; font-weight:bold; text-align: left;">Görselleri Değiştir</h4>
            
            <label for="avatarFile" class="file-input-label" id="avatarLabel">
                <i class="fas fa-image" style="margin-right:5px;"></i> Profil Fotoğrafı Seç
            </label>
            <input type="file" name="in_avatar_file" id="avatarFile" accept="image/*" onchange="updateLabel('avatarFile', 'avatarLabel')">
            <label class="remove-photo-check"><input type="checkbox" name="remove_avatar" value="1"> Mevcut Profil Fotoğrafını Kaldır</label>
            
            <label for="coverFile" class="file-input-label" id="coverLabel">
                <i class="fas fa-object-group" style="margin-right:5px;"></i> Kapak Fotoğrafı Seç
            </label>
            <input type="file" name="in_cover_file" id="coverFile" accept="image/*" onchange="updateLabel('coverFile', 'coverLabel')">
            <label class="remove-photo-check"><input type="checkbox" name="remove_cover" value="1"> Mevcut Kapak Fotoğrafını Kaldır</label>
            
            <button type="submit" name="update_profile" class="btn-action" style="background:var(--accent); color:#fff; margin-top:10px;">Kaydet</button>
            <p onclick="closeModal('editModal')" style="text-align:center; color:var(--text-muted); cursor:pointer; margin-top:15px; font-size:10px; text-transform: uppercase; letter-spacing:1px;">İptal</p>
        </form>
    </div>
</div>

<div id="favModal" class="modal">
    <div class="modal-box" style="width: 450px;">
        <h3>Favori Film Seç</h3>
        <input type="text" id="favSearchInput" onkeyup="searchFavMovie()" placeholder="Arşivde film ara..." autocomplete="off">
        <div id="favResults"></div>
        <p onclick="closeModal('favModal')" style="text-align:center; color:var(--text-muted); cursor:pointer; margin-top:15px; font-size:10px; text-transform: uppercase; letter-spacing:1px;">Vazgeç</p>
    </div>
</div>

<footer>
    <p>© 2026 Frame 25 | Selin Avcı Tarafından Yapılmıştır.</p>
</footer>

<script>
    const toggleBtn = document.getElementById('theme-toggle');
    if (localStorage.getItem('theme') === 'light' && toggleBtn) { toggleBtn.innerHTML = '<i class="fas fa-sun"></i>'; }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            let currentTheme = document.documentElement.getAttribute('data-theme');
            if (currentTheme === 'light') {
                document.documentElement.removeAttribute('data-theme'); localStorage.setItem('theme', 'dark'); toggleBtn.innerHTML = '<i class="fas fa-moon"></i>';
            } else {
                document.documentElement.setAttribute('data-theme', 'light'); localStorage.setItem('theme', 'light'); toggleBtn.innerHTML = '<i class="fas fa-sun"></i>';
            }
            setTimeout(() => { location.reload(); }, 100); 
        });
    }

    function switchTab(event, tabId) {
        let panes = document.getElementsByClassName("tab-pane");
        for (let i = 0; i < panes.length; i++) { panes[i].classList.remove("active"); }
        let links = document.getElementsByClassName("tab-link");
        for (let i = 0; i < links.length; i++) { links[i].classList.remove("active"); }
        document.getElementById(tabId).classList.add("active");
        event.currentTarget.classList.add("active");
    }

    function openModal(modalId) { 
        document.getElementById(modalId).style.display = 'block'; 
        if(modalId === 'favModal') { searchFavMovie(); }
    }
    function closeModal(modalId) { document.getElementById(modalId).style.display = 'none'; }
    window.onclick = function(e) { if (e.target.classList.contains('modal')) e.target.style.display = 'none'; }

    var allMovies = <?php echo json_encode($tum_filmler_js); ?>;
    function searchFavMovie() {
        var input = document.getElementById("favSearchInput").value.toLowerCase();
        var resultsBox = document.getElementById("favResults");
        resultsBox.innerHTML = ""; 
        var matches = 0;
        for (var i = 0; i < allMovies.length; i++) {
            var film = allMovies[i];
            if (film.title.toLowerCase().indexOf(input) > -1) {
                resultsBox.innerHTML += `
                    <a href="profile.php?add_fav=${film.id}" class="fav-result-item">
                        <img src="${film.img}">
                        <div><span>${film.title}</span><small>${film.dir}</small></div>
                    </a>`;
                matches++;
            }
            if(matches >= 6) break;
        }
        if(matches === 0 && input !== "") {
            resultsBox.innerHTML = "<p style='color:var(--text-muted); font-size:11px; text-align:center; padding:20px;'>Film bulunamadı.</p>";
        }
    }

    function updateLabel(inputId, labelId) {
        var input = document.getElementById(inputId);
        var label = document.getElementById(labelId);
        if (input.files && input.files[0]) {
            label.innerHTML = "<i class='fas fa-check' style='color:#00e054;'></i> Resim Seçildi";
            label.style.borderColor = "#00e054";
            label.style.color = "#00e054";
        }
    }

    const isLightMode = localStorage.getItem('theme') === 'light';
    const textColor = isLightMode ? '#555555' : '#aaaaaa';
    
    var genreLabels = <?php echo json_encode(array_keys($top_turler)); ?>;
    var genreData = <?php echo json_encode(array_values($top_turler)); ?>;
    
    if (genreLabels.length === 0) {
        genreLabels = ['Dram', 'Korku', 'Sci-Fi', 'Aksiyon', 'Gizem'];
        genreData = [12, 8, 5, 4, 3];
    }

    const ctxGenre = document.getElementById('genreChart').getContext('2d');
    new Chart(ctxGenre, {
        type: 'doughnut',
        data: {
            labels: genreLabels,
            datasets: [{
                data: genreData,
                backgroundColor: ['#b20710', '#00e054', '#ffd700', '#003366', '#888888'],
                borderWidth: 0
            }]
        },
        options: { plugins: { legend: { position: 'bottom', labels: { color: textColor, font: { size: 10, family: 'Montserrat' } } } }, cutout: '70%' }
    });

    const ctxMonth = document.getElementById('monthChart').getContext('2d');
    new Chart(ctxMonth, {
        type: 'bar',
        data: {
            labels: ['Oca', 'Şub', 'Mar', 'Nis'],
            datasets: [{
                label: 'İzlenen Film',
                data: [4, 7, <?php echo count($gunluk_kayitlari); ?>, 0],
                backgroundColor: '#b20710',
                borderRadius: 4
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: isLightMode ? '#e0e0e0' : '#222222' }, ticks: { color: textColor, stepSize: 2 } },
                x: { grid: { display: false }, ticks: { color: textColor } }
            }
        }
    });
</script>
</body>
</html>