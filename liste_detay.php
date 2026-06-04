<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. Veritabanı Bağlantısı
include 'baglan.php';

// Geçici oturum ID'si (Gerçek sistemde giriş yapan kullanıcının ID'si olur)
$benim_id = 1; 

// Liste ID'si URL'den gelmiyorsa veya geçersizse profile geri yolla
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: profile.php");
    exit();
}

$liste_id = intval($_GET['id']);
$mesaj = "";

// --- 1. LİSTEYİ BEĞENME / BEĞENİ GERİ ALMA (GET İŞLEMİ) ---
if (isset($_GET['action']) && $_GET['action'] == 'like') {
    try {
        // Önce beğenmiş mi kontrol et
        $kontrol = $baglanti->query("SELECT id FROM liste_begeniler WHERE liste_id = $liste_id AND user_id = $benim_id");
        if ($kontrol && $kontrol->num_rows > 0) {
            // Zaten beğenmişse beğeniyi kaldır
            $baglanti->query("DELETE FROM liste_begeniler WHERE liste_id = $liste_id AND user_id = $benim_id");
        } else {
            // Beğenmemişse ekle
            $baglanti->query("INSERT INTO liste_begeniler (liste_id, user_id) VALUES ($liste_id, $benim_id)");
        }
    } catch(Exception $e) {}
    header("Location: liste_detay.php?id=$liste_id");
    exit();
}

// --- 2. LİSTEYE FİLM EKLEME (GET İŞLEMİ) ---
if (isset($_GET['add_movie'])) {
    $film_id = intval($_GET['add_movie']);
    try {
        // Listede bu film zaten var mı kontrol et
        $kontrol = $baglanti->query("SELECT id FROM liste_filmler WHERE liste_id = $liste_id AND film_id = $film_id");
        if ($kontrol && $kontrol->num_rows == 0) {
            // Yoksa ekle
            $baglanti->query("INSERT INTO liste_filmler (liste_id, film_id) VALUES ($liste_id, $film_id)");
            $mesaj = "Film listeye eklendi!";
        }
    } catch(Exception $e) {}
    header("Location: liste_detay.php?id=$liste_id");
    exit();
}

// --- 3. LİSTEDEN FİLM SİLME (GET İŞLEMİ) ---
if (isset($_GET['remove_movie'])) {
    $film_id = intval($_GET['remove_movie']);
    try {
        $baglanti->query("DELETE FROM liste_filmler WHERE liste_id = $liste_id AND film_id = $film_id");
        $mesaj = "Film listeden çıkarıldı.";
    } catch(Exception $e) {}
    header("Location: liste_detay.php?id=$liste_id");
    exit();
}

// --- 4. YORUM EKLEME (POST İŞLEMİ - HAFTA 4 GÜVENLİK) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_comment'])) {
    $yorum_metni = htmlspecialchars(trim($_POST['yorum_icerik']));
    if (!empty($yorum_metni)) {
        try {
            $stmt = $baglanti->prepare("INSERT INTO liste_yorumlar (liste_id, user_id, yorum, tarih) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iis", $liste_id, $benim_id, $yorum_metni);
            $stmt->execute();
            $mesaj = "Yorumunuz paylaşıldı!";
        } catch(Exception $e) {}
    }
}

// --- VERİLERİ ÇEKME ---

// 1. Liste Bilgileri
$liste_bilgi = null;
try {
    // Listeyi oluşturanın adını (şimdi basit simüle ediyoruz ama JOIN ile gerçek tablodan çekilir)
    $sorgu = $baglanti->query("SELECT * FROM listeler WHERE id = $liste_id");
    if($sorgu && $sorgu->num_rows > 0) { $liste_bilgi = $sorgu->fetch_assoc(); }
} catch(Exception $e) {}

// Eğer böyle bir liste yoksa profile at
if (!$liste_bilgi) { header("Location: profile.php"); exit(); }

// Bu listenin sahibi ben miyim? (Sadece sahibi film ekleyip silebilir)
$benim_listem_mi = ($liste_bilgi['user_id'] == $benim_id) ? true : false;

// 2. Listedeki Filmleri Çekme
$liste_filmleri = [];
try {
    // Eklendiği sıraya göre (id ASC) çekiyoruz
    $f_sorgu = $baglanti->query("SELECT f.* FROM liste_filmler lf JOIN filmler f ON lf.film_id = f.id WHERE lf.liste_id = $liste_id ORDER BY lf.id ASC");
    if($f_sorgu) { while($row = $f_sorgu->fetch_assoc()) { $liste_filmleri[] = $row; } }
} catch(Exception $e) {}

// 3. Beğeni Kontrolü
$begeni_sayisi = 0;
$ben_begendim_mi = false;
try {
    $b_say = $baglanti->query("SELECT COUNT(*) as c FROM liste_begeniler WHERE liste_id = $liste_id");
    if($b_say) { $r = $b_say->fetch_assoc(); $begeni_sayisi = $r['c']; }
    
    $b_ben = $baglanti->query("SELECT id FROM liste_begeniler WHERE liste_id = $liste_id AND user_id = $benim_id");
    if($b_ben && $b_ben->num_rows > 0) { $ben_begendim_mi = true; }
} catch(Exception $e) {}

// 4. Yorumları Çekme
$yorumlar = [];
try {
    $y_sorgu = $baglanti->query("SELECT * FROM liste_yorumlar WHERE liste_id = $liste_id ORDER BY tarih DESC");
    if($y_sorgu) { while($row = $y_sorgu->fetch_assoc()) { $yorumlar[] = $row; } }
} catch(Exception $e) {}

// ARAMA MODALI İÇİN TÜM FİLMLERİ ÇEK (Sadece sahibiyse JS yüklesin)
$tum_filmler_js = [];
if ($benim_listem_mi) {
    try {
        $tum_res = $baglanti->query("SELECT id, title, img, dir FROM filmler");
        if($tum_res) { while($row = $tum_res->fetch_assoc()) { $tum_filmler_js[] = $row; } }
    } catch(Exception $e) {}
}

$sayfa_basligi = htmlspecialchars($liste_bilgi['baslik']) . " - FRAME 25";
include 'header.php';
?>

<style>
    .list-container { width: 95%; max-width: 960px; margin: 50px auto; font-family: 'Montserrat', sans-serif; }
    
    /* Üst Başlık Alanı */
    .list-header { border-bottom: 1px solid var(--border-color); padding-bottom: 30px; margin-bottom: 30px; }
    .list-header h1 { font-family: 'Bebas Neue'; font-size: 3.5em; color: var(--text-main); margin: 0 0 10px 0; letter-spacing: 1px; }
    .list-creator { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; }
    .list-creator img { width: 35px; height: 35px; border-radius: 50%; object-fit: cover; }
    .list-creator span { font-size: 12px; color: var(--text-muted); }
    .list-creator strong { color: var(--text-main); }
    .list-desc { font-size: 14px; color: var(--text-muted); line-height: 1.8; margin-bottom: 20px; }
    
    /* Aksiyon Çubuğu (Beğeni, Film Ekle) */
    .list-actions { display: flex; gap: 15px; align-items: center; }
    .btn-like { display: flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 4px; font-weight: bold; font-size: 12px; cursor: pointer; transition: 0.3s; text-decoration: none;}
    .btn-like.active { background: rgba(178, 7, 16, 0.1); color: #b20710; border: 1px solid #b20710; }
    .btn-like.passive { background: var(--bg-color); color: var(--text-muted); border: 1px solid var(--border-color); }
    .btn-like.passive:hover { color: #b20710; border-color: #b20710; }
    
    .btn-add-movie { background: #00e054; color: #000; padding: 10px 20px; border: none; font-weight: bold; font-size: 12px; border-radius: 4px; cursor: pointer; text-transform: uppercase; transition: 0.3s;}
    .btn-add-movie:hover { background: #fff; }

    /* Film Izgarası (Sıralı Numaralı) */
    .movies-grid { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 50px; }
    .movie-item { width: calc(20% - 16px); position: relative; border-radius: 4px; overflow: hidden; border: 1px solid var(--border-color); transition: 0.3s; background: var(--card-bg); }
    .movie-item:hover { border-color: #00e054; transform: translateY(-5px); }
    .movie-item img { width: 100%; height: 250px; object-fit: cover; display: block; }
    
    .rank-badge { position: absolute; top: -5px; left: -5px; background: #b20710; color: #fff; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; font-family: 'Bebas Neue'; font-size: 20px; border-radius: 50%; border: 3px solid var(--bg-color); z-index: 2;}
    .remove-btn { position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.8); color: #fff; width: 25px; height: 25px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; opacity: 0; transition: 0.3s; z-index: 2; text-decoration: none;}
    .movie-item:hover .remove-btn { opacity: 1; }
    .remove-btn:hover { background: #b20710; }

    /* Yorumlar Alanı */
    .comments-section { border-top: 1px solid var(--border-color); padding-top: 40px; margin-top: 20px; }
    .comments-section h3 { font-size: 18px; color: var(--text-main); margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px; font-weight: bold;}
    
    .comment-form textarea { width: 100%; background: var(--bg-color); border: 1px solid var(--border-color); color: var(--text-main); padding: 15px; border-radius: 4px; resize: vertical; outline: none; font-family: inherit; font-size: 13px; margin-bottom: 10px;}
    .comment-form textarea:focus { border-color: #00e054; }
    .btn-submit-comment { background: var(--nav-bg); color: var(--text-main); border: 1px solid var(--border-color); padding: 10px 25px; font-weight: bold; font-size: 11px; text-transform: uppercase; cursor: pointer; border-radius: 3px; float: right; transition: 0.3s;}
    .btn-submit-comment:hover { border-color: #00e054; color: #00e054; }

    .comment-list { margin-top: 60px; display: flex; flex-direction: column; gap: 20px; }
    .comment-box { display: flex; gap: 15px; background: var(--card-bg); padding: 20px; border-radius: 4px; border: 1px solid var(--border-color); }
    .comment-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
    .comment-content h5 { margin: 0 0 5px 0; font-size: 13px; color: var(--text-main); display: flex; align-items: center; gap: 10px;}
    .comment-content h5 small { color: var(--text-muted); font-size: 10px; font-weight: normal; }
    .comment-content p { margin: 0; font-size: 13px; color: var(--text-muted); line-height: 1.6; }

    /* Modal Formları (Profildeki ile aynı yapı) */
    .modal { display: none; position: fixed; z-index: 2000; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); }
    .modal-box { background: var(--card-bg); width: 450px; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); padding: 30px; border: 1px solid var(--border-color); border-radius: 4px; }
    .modal-box h3 { font-family: 'Bebas Neue'; font-size: 2em; color: var(--text-main); margin-top: 0; margin-bottom: 20px; }
    .modal-box input { width: 100%; padding: 12px; margin-bottom: 15px; background: var(--bg-color); border: 1px solid var(--border-color); color: var(--text-main); outline: none; font-size: 12px; border-radius: 3px;}
    .modal-box input:focus { border-color: #00e054; }
    
    #searchMovieResults { display: flex; flex-direction: column; gap: 5px; margin-top: 5px; max-height: 250px; overflow-y: auto; }
    .search-res-item { display: flex; align-items: center; gap: 15px; background: var(--bg-color); border: 1px solid var(--border-color); padding: 8px; cursor: pointer; transition: 0.2s; text-decoration: none; color: var(--text-main); border-radius: 3px;}
    .search-res-item:hover { border-color: #00e054; }
    .search-res-item img { width: 35px; height: 50px; object-fit: cover; border-radius: 2px;}
    .search-res-item div { display: flex; flex-direction: column; }
    .search-res-item span { font-size: 12px; font-weight: bold; }
    
    .msg-alert { border: 1px solid #00e054; color: #00e054; background: rgba(0, 224, 84, 0.05); padding: 10px; margin-bottom: 20px; font-weight: bold; text-align: center; border-radius: 4px; font-size: 11px;}
</style>

<div class="list-container">
    
    <?php if($mesaj): ?>
        <div id="basariMesaji" class="msg-alert"><i class="fas fa-info-circle"></i> <?php echo $mesaj; ?></div>
        <script>setTimeout(function() { var msgBox = document.getElementById('basariMesaji'); if(msgBox) msgBox.style.display = 'none'; }, 2000);</script>
    <?php endif; ?>

    <div class="list-header">
        <h1><?php echo htmlspecialchars($liste_bilgi['baslik']); ?></h1>
        
        <div class="list-creator">
            <img src="https://ui-avatars.com/api/?name=Kullanici&background=222&color=fff" alt="Avatar">
            <span>Listeyi oluşturan: <strong><?php echo $benim_listem_mi ? "Sen" : "Kullanıcı"; ?></strong></span>
        </div>
        
        <?php if(!empty($liste_bilgi['aciklama'])): ?>
            <div class="list-desc"><?php echo nl2br(htmlspecialchars($liste_bilgi['aciklama'])); ?></div>
        <?php endif; ?>
        
        <div class="list-actions">
            <a href="liste_detay.php?id=<?php echo $liste_id; ?>&action=like" class="btn-like <?php echo $ben_begendim_mi ? 'active' : 'passive'; ?>">
                <i class="fa<?php echo $ben_begendim_mi ? 's' : 'r'; ?> fa-heart"></i> 
                <?php echo $begeni_sayisi; ?> Beğeni
            </a>
            
            <?php if($benim_listem_mi): ?>
                <button class="btn-add-movie" onclick="openAddMovieModal()"><i class="fas fa-plus"></i> Listeye Film Ekle</button>
            <?php endif; ?>
        </div>
    </div>

    <div class="movies-grid">
        <?php if(!empty($liste_filmleri)): ?>
            <?php 
            $sira = 1;
            foreach($liste_filmleri as $film): 
            ?>
                <div class="movie-item">
                    <div class="rank-badge"><?php echo $sira++; ?></div>
                    
                    <?php if($benim_listem_mi): ?>
                        <a href="liste_detay.php?id=<?php echo $liste_id; ?>&remove_movie=<?php echo $film['id']; ?>" class="remove-btn" title="Listeden Çıkar"><i class="fas fa-times"></i></a>
                    <?php endif; ?>
                    
                    <a href="detay.php?id=<?php echo $film['id']; ?>">
                        <img src="<?php echo htmlspecialchars($film['img']); ?>" alt="<?php echo htmlspecialchars($film['title']); ?>">
                    </a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="width:100%; border:1px dashed var(--border-color); padding:50px; text-align:center; color:var(--text-muted); font-size:13px; border-radius:4px;">
                Bu listeye henüz film eklenmemiş.
            </div>
        <?php endif; ?>
    </div>

    <div class="comments-section">
        <h3><?php echo count($yorumlar); ?> YORUM</h3>
        
        <form method="POST" action="liste_detay.php?id=<?php echo $liste_id; ?>" class="comment-form">
            <textarea name="yorum_icerik" rows="3" placeholder="Bu liste hakkında ne düşünüyorsun? Yorumunu bırak..." required></textarea>
            <button type="submit" name="add_comment" class="btn-submit-comment">Gönder</button>
            <div style="clear:both;"></div>
        </form>

        <div class="comment-list">
            <?php if(!empty($yorumlar)): ?>
                <?php foreach($yorumlar as $yorum): ?>
                    <div class="comment-box">
                        <img src="https://ui-avatars.com/api/?name=Yorumcu&background=111&color=fff" class="comment-avatar">
                        <div class="comment-content">
                            <h5>Kullanıcı <small><i class="far fa-clock"></i> <?php echo date("d.m.Y H:i", strtotime($yorum['tarih'])); ?></small></h5>
                            <p><?php echo nl2br(htmlspecialchars($yorum['yorum'])); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if($benim_listem_mi): ?>
<div id="addMovieModal" class="modal">
    <div class="modal-box">
        <h3 style="color:var(--text-main); font-family:'Bebas Neue'; font-size:2em; margin-top:0;">LİSTEYE FİLM EKLE</h3>
        <input type="text" id="modalSearchInput" onkeyup="searchListMovie()" placeholder="Film adı yazın..." autocomplete="off">
        <div id="searchMovieResults"></div>
        <p onclick="document.getElementById('addMovieModal').style.display='none';" style="text-align:center; color:var(--text-muted); cursor:pointer; margin-top:20px; font-size:10px; text-transform:uppercase;">Kapat</p>
    </div>
</div>

<script>
    function openAddMovieModal() { 
        document.getElementById('addMovieModal').style.display = 'block'; 
        document.getElementById('modalSearchInput').focus();
        searchListMovie();
    }
    
    var allMovies = <?php echo json_encode($tum_filmler_js); ?>;
    var listeId = <?php echo $liste_id; ?>;

    function searchListMovie() {
        var input = document.getElementById("modalSearchInput").value.toLowerCase();
        var resultsBox = document.getElementById("searchMovieResults");
        resultsBox.innerHTML = ""; 
        var matches = 0;
        
        for (var i = 0; i < allMovies.length; i++) {
            var film = allMovies[i];
            if (film.title.toLowerCase().indexOf(input) > -1) {
                var card = `
                    <a href="liste_detay.php?id=${listeId}&add_movie=${film.id}" class="search-res-item">
                        <img src="${film.img}" alt="Poster">
                        <div><span>${film.title}</span></div>
                    </a>
                `;
                resultsBox.innerHTML += card;
                matches++;
            }
            if(matches >= 10) break;
        }
        if (matches === 0) { resultsBox.innerHTML = "<p style='color:var(--text-muted); font-size:11px; text-align:center;'>Film bulunamadı.</p>"; }
    }
</script>
<?php endif; ?>

<?php include 'footer.php'; ?>
</body>
</html>