<?php
// footer.php - Tüm sayfaların ortak kapanış şablonu
?>
<footer>
    &copy; 2026 FRAME 25  Selin Avcı Tarafından Yapılmıştır. Tüm Hakları Saklıdır. 
</footer>

<script>
    // --- TEMA DEĞİŞTİRİCİ SİSTEMİ (TÜM SAYFALAR İÇİN ORTAK) ---
    const toggleBtn = document.getElementById('theme-toggle');
    const currentThemeFromStorage = localStorage.getItem('theme') || 'dark';

    // Sayfa yüklendiğinde hafızadaki moda göre ikon belirle
    if (currentThemeFromStorage === 'light' && toggleBtn) {
        toggleBtn.innerHTML = '<i class="fas fa-sun"></i>';
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            let theme = document.documentElement.getAttribute('data-theme');
            if (theme === 'light') {
                document.documentElement.removeAttribute('data-theme'); 
                localStorage.setItem('theme', 'dark');
                toggleBtn.innerHTML = '<i class="fas fa-moon"></i>';
            } else {
                document.documentElement.setAttribute('data-theme', 'light'); 
                localStorage.setItem('theme', 'light');
                toggleBtn.innerHTML = '<i class="fas fa-sun"></i>';
            }
        });
    }
</script>