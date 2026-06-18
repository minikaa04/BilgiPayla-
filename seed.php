<?php
require_once 'config.php';
require_once 'helpers.php';

echo "<h2>BilgiPaylaş — Seed Data</h2>";

// Create fake users
$users = [
    ['email' => 'ayse@test.com', 'username' => 'aysekaya', 'display_name' => 'Ayşe Kaya', 'bio' => 'Yemek yapmayı ve yeni tarifler keşfetmeyi seviyorum. 🍳'],
    ['email' => 'mehmet@test.com', 'username' => 'mehmetdemir', 'display_name' => 'Mehmet Demir', 'bio' => 'Teknoloji meraklısı, yazılım geliştirici. 💻'],
    ['email' => 'zeynep@test.com', 'username' => 'zeynepyilmaz', 'display_name' => 'Zeynep Yılmaz', 'bio' => 'DIY projeleri ve ev dekorasyonu tutkunuyum. 🏠'],
    ['email' => 'ali@test.com', 'username' => 'aliozturk', 'display_name' => 'Ali Öztürk', 'bio' => 'Sağlıklı yaşam koçu, fitness antrenörü. 💪'],
    ['email' => 'fatma@test.com', 'username' => 'fatmacan', 'display_name' => 'Fatma Can', 'bio' => 'Alışveriş uzmanı, en iyi fırsatları paylaşırım. 🛍️'],
];

$password = password_hash('123456', PASSWORD_DEFAULT);
$userIds = [];

foreach ($users as $u) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$u['email']]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        $userIds[] = $existing['id'];
        echo "✓ Kullanıcı zaten var: {$u['display_name']}<br>";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (email, password, username, display_name, bio) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$u['email'], $password, $u['username'], $u['display_name'], $u['bio']]);
        $userIds[] = $pdo->lastInsertId();
        echo "✓ Kullanıcı oluşturuldu: {$u['display_name']}<br>";
    }
}

// Make some friendships
$friendships = [[0,1],[0,2],[1,3],[2,4],[3,4]];
foreach ($friendships as $f) {
    $stmt = $pdo->prepare("SELECT id FROM friends WHERE user_id = ? AND friend_id = ?");
    $stmt->execute([$userIds[$f[0]], $userIds[$f[1]]]);
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO friends (user_id, friend_id, status) VALUES (?, ?, 'accepted')");
        $stmt->execute([$userIds[$f[0]], $userIds[$f[1]]]);
        echo "✓ Arkadaşlık: {$users[$f[0]]['display_name']} ↔ {$users[$f[1]]['display_name']}<br>";
    }
}

// Create collections
$collections = [
    [
        'user' => 0, 'title' => 'En Kolay Ev Yemekleri',
        'description' => 'Herkesin kolayca yapabileceği lezzetli ev yemekleri tarifi koleksiyonu.',
        'category' => 'Yemek Tarifleri', 'hashtags' => 'yemek,tarif,kolay,ev yemekleri',
        'items' => [
            ['title' => 'Pratik Mercimek Çorbası', 'content' => "Malzemeler:\n- 1 su bardağı kırmızı mercimek\n- 1 adet soğan\n- 1 adet havuç\n- 1 yemek kaşığı tereyağı\n- Tuz, karabiber, pul biber\n\nYapılışı:\n1. Mercimeği yıkayıp tencereye alın\n2. Doğranmış soğan ve havucu ekleyin\n3. Üzerini geçecek kadar su koyup pişirin\n4. Blenderdan geçirin\n5. Tereyağında pul biberle sos yapıp üzerine gezdirin\n\n⏱️ Hazırlama: 30 dk"],
            ['title' => 'Fırında Sebzeli Tavuk', 'content' => "Malzemeler:\n- 1 bütün tavuk\n- Patates, biber, soğan, domates\n- Zeytinyağı, tuz, kekik, pul biber\n\nYapılışı:\n1. Tavuğu tepsiye yerleştirin\n2. Sebzeleri doğrayıp etrafına dizin\n3. Zeytinyağı ve baharatlarla marine edin\n4. 200°C fırında 1.5 saat pişirin\n\n💡 İpucu: Tavuğu önceden 1 gece marine ederseniz daha lezzetli olur."],
            ['title' => 'Anne Usulü Karnıyarık', 'content' => "Malzemeler:\n- 6 adet patlıcan\n- 300g kıyma\n- 2 domates, 2 biber\n- Soğan, sarımsak, tuz\n\nYapılışı:\n1. Patlıcanları kızartın\n2. Kıymayı soğan ile kavurun\n3. Domates ve biberi ekleyip pişirin\n4. Patlıcanları yarıp içini doldurun\n5. Fırında 20 dk pişirin"],
        ]
    ],
    [
        'user' => 1, 'title' => 'Teknoloji İpuçları ve Kısayollar',
        'description' => 'Günlük hayatınızı kolaylaştıracak teknoloji ipuçları, kısayollar ve püf noktaları.',
        'category' => 'Teknoloji', 'hashtags' => 'teknoloji,ipucu,bilgisayar,telefon',
        'items' => [
            ['title' => 'Windows Hız Kısayolları', 'content' => "En kullanışlı Windows kısayolları:\n\n🔹 Win + D → Masaüstünü göster\n🔹 Win + L → Ekranı kilitle\n🔹 Win + E → Dosya gezgini aç\n🔹 Win + V → Pano geçmişi\n🔹 Alt + Tab → Pencereler arası geçiş\n🔹 Ctrl + Shift + Esc → Görev yöneticisi\n🔹 Win + Shift + S → Ekran alıntısı\n\n💡 Bu kısayolları öğrenmek günde en az 15 dakika kazandırır!"],
            ['title' => 'Telefonunuzu Hızlandırın', 'content' => "📱 Telefonunuz yavaşladıysa:\n\n1. Kullanmadığınız uygulamaları silin\n2. Önbelleği temizleyin (Ayarlar → Depolama)\n3. Animasyonları kapatın\n4. Otomatik güncellemeyi Wi-Fi ile sınırlayın\n5. Lite versiyonları kullanın (Facebook Lite, vb.)\n\n⚠️ RAM temizleyici uygulamalardan kaçının, genelde daha çok yavaşlatırlar!"],
        ]
    ],
    [
        'user' => 2, 'title' => 'Ev Düzeni ve Temizlik Sırları',
        'description' => 'Evinizi daha düzenli ve temiz tutmanın pratik yolları.',
        'category' => 'Ev & Yaşam', 'hashtags' => 'ev,temizlik,düzen,lайфhack',
        'items' => [
            ['title' => 'Doğal Temizlik Karışımları', 'content' => "🧹 Kimyasal kullanmadan temizlik:\n\n1. Sirke + Karbonat → Mutfak yüzeyleri için mükemmel\n2. Limon + Tuz → Paslanmaz çelik parlatıcı\n3. Sirke + Su (1:1) → Cam temizleyici\n4. Karbonat → Halı leke çıkarıcı\n\n💚 Hem ekonomik hem de doğa dostu!"],
            ['title' => 'Marie Kondo Yöntemiyle Dolap Düzeni', 'content' => "👕 Kıyafetlerinizi düzenleyin:\n\n1. Hepsini çıkarın ve bir yere yığın\n2. Her birini elinize alıp 'Bu beni mutlu ediyor mu?' diye sorun\n3. Mutlu etmeyenleri ayırın\n4. Kalanları kategorize edin\n5. Dikey katlama tekniği kullanın\n\n✨ Daha az eşya = Daha az stres!"],
        ]
    ],
    [
        'user' => 3, 'title' => 'Sağlıklı Yaşam Rehberi',
        'description' => 'Sağlıklı beslenme, egzersiz ve yaşam tarzı ipuçları.',
        'category' => 'Sağlık', 'hashtags' => 'sağlık,fitness,beslenme,spor',
        'items' => [
            ['title' => 'Evde 15 Dakika Egzersiz', 'content' => "💪 Her gün 15 dakikada fit kalın:\n\n1. 20 x Squat\n2. 15 x Şınav\n3. 30 sn Plank\n4. 20 x Lunge (her bacak 10)\n5. 15 x Crunch\n6. 30 sn Mountain Climber\n\n🔄 Bu devreyi 3 kez tekrarlayın\n\n⏱️ Toplam süre: ~15 dakika\n📈 Haftada 5 gün yaparsanız 1 ayda fark görürsünüz!"],
            ['title' => 'Su İçme Hatırlatıcı', 'content' => "💧 Günlük su ihtiyacınızı karşılayın:\n\n- Sabah kalkar kalkmaz 1 bardak\n- Her yemekten 30 dk önce 1 bardak\n- Egzersiz öncesi ve sonrası\n- Gece yatmadan 1 saat önce son bardak\n\n📏 Günde en az 2-2.5 litre su için\n\n💡 İpucu: Telefonunuza alarm kurun veya su takip uygulaması kullanın"],
        ]
    ],
    [
        'user' => 4, 'title' => 'Akıllı Alışveriş Tüyoları',
        'description' => 'Para biriktirmenin ve akıllı alışveriş yapmanın yolları.',
        'category' => 'Alışveriş', 'hashtags' => 'alışveriş,tasarruf,indirim,akıllı',
        'items' => [
            ['title' => 'Online Alışverişte Tasarruf', 'content' => "🛍️ Daha ucuza almanın yolları:\n\n1. Fiyat karşılaştırma sitelerini kullanın\n2. Sepete ekleyip 1-2 gün bekleyin (indirim kodu gelebilir)\n3. Cashback uygulamalarını kullanın\n4. Sezon sonu indirimlerini takip edin\n5. Toplu alım yapın\n\n💰 Bu yöntemlerle yılda %20-30 tasarruf edebilirsiniz!"],
            ['title' => 'Market Alışverişi Stratejisi', 'content' => "🏪 Markette para biriktirme tüyoları:\n\n1. Liste yapın ve listeden şaşmayın\n2. Aç karnına markete gitmeyin\n3. Raf altı ürünleri kontrol edin (genelde daha ucuz)\n4. Markette kendi markalarını deneyin\n5. Mevsim sebze-meyve alın\n6. İndirimli ürünleri takip edip stok yapın\n\n📋 İpucu: Haftalık menü planlayarak alışveriş listenizi çıkarın"],
        ]
    ],
];

foreach ($collections as $col) {
    $userId = $userIds[$col['user']];
    
    $stmt = $pdo->prepare("SELECT id FROM collections WHERE title = ? AND user_id = ?");
    $stmt->execute([$col['title'], $userId]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        echo "✓ Koleksiyon zaten var: {$col['title']}<br>";
        continue;
    }
    
    $stmt = $pdo->prepare("INSERT INTO collections (user_id, title, description, category, hashtags) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $col['title'], $col['description'], $col['category'], $col['hashtags']]);
    $collId = $pdo->lastInsertId();
    echo "✓ Koleksiyon oluşturuldu: {$col['title']}<br>";
    
    foreach ($col['items'] as $item) {
        $stmt = $pdo->prepare("INSERT INTO collection_items (collection_id, user_id, title, content, status, likes) VALUES (?, ?, ?, ?, 'approved', ?)");
        $likes = rand(3, 25);
        $stmt->execute([$collId, $userId, $item['title'], $item['content'], $likes]);
        echo "  → İçerik eklendi: {$item['title']}<br>";
    }
}

// Add some cross-contributions
echo "<br><strong>Çapraz katkılar ekleniyor...</strong><br>";

// Get all collections
$stmt = $pdo->query("SELECT id, user_id, title FROM collections");
$allCollections = $stmt->fetchAll();

$crossContributions = [
    ['Pişirme Sırası İpucu', "Yemek yaparken suyun daha hızlı kaynaması için tencereye bir tutam tuz ekleyin ve kapağını kapatın. Bu sayede yaklaşık %15 daha hızlı kaynar.\n\n💡 Enerji tasarrufu da sağlar!"],
    ['Ekran Yorgunluğunu Azaltın', "20-20-20 Kuralı:\n\nHer 20 dakikada bir, 20 saniye boyunca 20 feet (6 metre) uzağa bakın.\n\n🖥️ Bu basit kural göz kuruluğunu ve yorgunluğunu büyük ölçüde azaltır."],
    ['Çamaşır Suyu Alternatifi', "Beyaz çamaşırlarınız için doğal bir alternatif:\n\n1 fincan sirke + 1 yemek kaşığı karbonat\n\nÇamaşır makinesinin yumuşatıcı gözüne ekleyin.\n\n🌿 Kimyasalsız bembeyaz çamaşırlar!"],
];

$contribIdx = 0;
foreach ($allCollections as $coll) {
    // Different user contributes
    $contribUserId = $userIds[($contribIdx + 2) % count($userIds)];
    if ($contribUserId != $coll['user_id'] && $contribIdx < count($crossContributions)) {
        $cc = $crossContributions[$contribIdx];
        $stmt = $pdo->prepare("SELECT id FROM collection_items WHERE title = ? AND collection_id = ?");
        $stmt->execute([$cc[0], $coll['id']]);
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO collection_items (collection_id, user_id, title, content, status, likes) VALUES (?, ?, ?, ?, 'approved', ?)");
            $stmt->execute([$coll['id'], $contribUserId, $cc[0], $cc[1], rand(5, 15)]);
            echo "✓ Çapraz katkı: '{$cc[0]}' → {$coll['title']}<br>";
        }
        $contribIdx++;
    }
}

// Add some messages
$msgPairs = [
    [0, 1, 'Merhaba Mehmet! Teknoloji koleksiyonun çok faydalı olmuş 👍'],
    [1, 0, 'Teşekkürler Ayşe! Senin yemek tariflerin de harika 😊'],
    [0, 1, 'Mercimek çorbası tarifini denedim, çok beğendik!'],
    [2, 3, 'Ali, evde egzersiz programını deniyorum, harika!'],
    [3, 2, 'Süper Zeynep! Düzenli yapman çok önemli 💪'],
];

foreach ($msgPairs as $msg) {
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
    $stmt->execute([$userIds[$msg[0]], $userIds[$msg[1]], $msg[2]]);
}
echo "<br>✓ Mesajlar eklendi<br>";

// Add followers
foreach ($allCollections as $coll) {
    for ($i = 0; $i < rand(2, 4); $i++) {
        $followerId = $userIds[array_rand($userIds)];
        if ($followerId != $coll['user_id']) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO collection_followers (collection_id, user_id) VALUES (?, ?)");
            $stmt->execute([$coll['id'], $followerId]);
        }
    }
}
echo "✓ Takipçiler eklendi<br>";

echo "<br><h3 style='color:green;'>✅ Seed data başarıyla oluşturuldu!</h3>";
echo "<p>Tüm kullanıcıların şifresi: <strong>123456</strong></p>";
echo "<p><a href='" . BASE_URL . "/login.php'>Giriş sayfasına git →</a></p>";
