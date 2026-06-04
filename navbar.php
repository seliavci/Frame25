<nav>
    <div class="nav-wrapper">
        <ul>
            <?php
            // Yönerge Madde 12: Menüyü veritabanından çekiyoruz
            $menu_sorgu = $baglanti->query("SELECT * FROM menu ORDER BY sira ASC");
            while($m = $menu_sorgu->fetch_assoc()):
                // Mevcut sayfadaysak "active" sınıfını ekleyelim
                $active_class = (basename($_SERVER['PHP_SELF']) == $m['url']) ? 'active' : '';
            ?>
                <li><a href="<?php echo $m['url']; ?>" class="<?php echo $active_class; ?>"><?php echo htmlspecialchars($m['baslik']); ?></a></li>
            <?php endwhile; ?>

            <?php if(isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['Super Admin', 'Editor'])): ?>
                <li><a href="admin.php" style="color:#b20710;">YÖNETİM</a></li>
            <?php endif; ?>
        </ul>

        <div class="search-area">
            <div class="lang-switcher">
                <a href="?lang=tr" class="lang-btn"><span class="fi fi-tr"></span></a>
                <a href="?lang=en" class="lang-btn"><span class="fi fi-gb"></span></a>
            </div>

            <button class="theme-btn" id="theme-toggle" title="Aydınlık/Karanlık Mod">
                <i class="fas fa-moon"></i>
            </button>

            <?php if(basename($_SERVER['PHP_SELF']) == 'index.php'): ?>
                <input type="text" id="searchInput" onkeyup="searchMovies()" placeholder="Film ara...">
            <?php endif; ?>
        </div>
    </div>
</nav>