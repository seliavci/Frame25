<?php
include 'baglan.php';

// Dosya adını ve türünü ayarla
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=frame25_film_listesi.csv');

// Çıktıyı yazmak için dosya aç
$output = fopen('php://output', 'w');

// Excel'in Türkçe karakterleri tanıması için BOM ekle
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Sütun başlıklarını yaz
fputcsv($output, array('ID', 'Film Adı', 'Yönetmen', 'Fiyat', 'Kategori', 'Eklenme Tarihi'));

// Verileri çek ve dosyaya işle
$query = $baglanti->query("SELECT id, title, dir, price, category, release_date FROM filmler ORDER BY id DESC");
while ($row = $query->fetch_assoc()) {
    fputcsv($output, $row);
}

fclose($output);
exit();
?>