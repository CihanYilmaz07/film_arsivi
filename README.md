# CHNStream — Kişisel Film ve Dizi Arşivi

> **Ders:** İnternet Programcılığı I (PHP)  
> **Proje türü:** Bireysel dönem projesi — Kayıt ve Takip Sistemi  
> **Konu:** Film / dizi arşiv sistemi  
> **Teknolojiler:** PHP 8, HTML5, CSS3, JavaScript (vanilla)  
> **Veri saklama:** PHP dizileri + `$_SESSION`

---

## 1. Projenin Adı ve Amacı

**CHNStream**, kullanıcının izlediği ve izlemek istediği filmleri/dizileri tek bir yerde
kaydettiği, puanladığı, filtrelendiği ve takip ettiği kişisel bir sinema arşividir.

Projenin amacı yalnızca görsel bir sayfa hazırlamak değil; PHP ile

- veri saklayan,
- kullanıcıdan form ile veri alan,
- veriyi doğrulayan,
- listeleyen, filtreleyen, güncelleyen ve silen

çalışır bir web uygulaması geliştirmektir.

---

## 2. Projede Bulunan Özellikler

### 2.1. Ödev kapsamındaki zorunlu özellikler

| # | Gereksinim | Durum | Nerede? |
|---|------------|:-----:|---------|
| 1 | Uygulama adı ve kısa açıklaması | ✅ | `index.php` hero alanı |
| 2 | Kayıtların listelendiği bölüm | ✅ | `index.php` → Film Arşivi |
| 3 | Sayfalar arası kolay gezinme | ✅ | Ortak üst menü + alt menü |
| 4 | Kayıtların PHP ile listelenmesi | ✅ | `foreach` döngüsü, `index.php` |
| 5 | Her kayıtta en az dört bilgi alanı | ✅ | Başlık, kategori, yönetmen, yıl, puan, IMDb puanı, açıklama, konu, durum |
| 6 | HTML formu ile yeni kayıt | ✅ | `ekle.php` |
| 7 | Form verisinin PHP ile işlenmesi | ✅ | `ekle.php` POST işleme bloğu |
| 8 | Veri gönderiminde POST kullanımı | ✅ | Ekleme, silme, güncelleme, durum değiştirme |
| 9 | Başarılı kayıttan sonra listede görünme | ✅ | Kayıt `$_SESSION['filmler']` dizisine eklenir |
| 10 | Boş zorunlu alan kontrolü | ✅ | `filmVerisiniDogrula()` |
| 11 | Hatalı girişte açıklayıcı mesaj | ✅ | Alan altı hata mesajları + uyarı kutusu |
| 12 | Verilerin PHP tarafında doğrulanması | ✅ | `functions.php` |
| 13 | Kayıt silme | ✅ | `sil.php` (yalnızca POST) |
| 14 | Silinen kaydın listeden kalkması | ✅ | `filmSil()` + yönlendirme |
| 15 | Kategori veya duruma göre filtreleme | ✅ | `index.php` GET filtreleri |
| 16 | Toplam kayıt sayısı | ✅ | İstatistik kartları |
| 17 | Duruma göre en az bir sayaç | ✅ | İzlenen / İzlenecek / İzleniyor |
| 18 | `htmlspecialchars()` ile güvenli çıktı | ✅ | `e()` yardımcı fonksiyonu |
| 19 | En az iki kullanıcı tanımlı fonksiyon | ✅ | `functions.php` içinde 25+ fonksiyon |
| 20 | En az bir `foreach` döngüsü | ✅ | Film listesi, kategori seçenekleri, durum seçenekleri |
| 21 | Koşul yapıları | ✅ | Doğrulama, filtreleme, durum kontrolleri |
| 22 | `$_SESSION` ile kalıcılık | ✅ | `filmVerisiniBaslat()` |
| 23 | README dosyası | ✅ | Bu dosya |

### 2.2. Ödev kapsamı dışında eklenen geliştirmeler

- Sinematik, tamamen mobil uyumlu koyu tema ve cam (glassmorphism) paneller
- Canlı form önizlemesi: form doldurulurken film kartı anında güncellenir
- Gelişmiş arama: başlık, kategori, yönetmen, yıl ve açıklama içinde arama
- Kategori, durum ve sıralama filtreleri (GET)
- Hızlı durum filtreleri ve sayaçlı çipler
- Kayıt güncelleme (`duzenle.php`)
- Film detay sayfası (`detay.php`) ve benzer film önerileri
- Tek tıkla durum değiştirme (`duruma göre` döngüsel geçiş)
- Izgara / liste görünümü seçimi (tarayıcıda hatırlanır)
- Silme öncesi erişilebilir onay penceresi
- CSRF token koruması (ekleme, güncelleme, silme, durum değiştirme)
- Alan bazlı sunucu taraflı doğrulama ve hata mesajları
- Afiş URL'si desteği; afiş yoksa otomatik gradyan kapak
- Animasyonlu sayaçlar, bildirimler (toast), klavye kısayolları
- Erişilebilirlik: `aria` etiketleri, odak halkaları, "içeriğe geç" bağlantısı
- `prefers-reduced-motion` desteği
- **Koyu / açık tema:** üst menüdeki güneş–ay düğmesiyle anında geçiş; tercih
  `localStorage` içinde saklanır ve sayfa boyanmadan önce uygulanır (tema titremesi yok)
- **50 filmlik zengin başlangıç arşivi:** her film için açıklama, konu ve IMDb puanı
- **IMDb puanı:** kartlarda, detay sayfasında, öne çıkan filmde ve sıralama seçeneklerinde
- **Yıldızlı puanlama:** detay sayfasında 1–10 arası tıklanabilir yıldız formu (`puanla.php`)
- **İzleme geçmişine göre öneri motoru:** izlenen/izleniyor filmlerin tür ağırlıkları,
  IMDb ve kişisel puanlar birlikte değerlendirilerek “Senin İçin Öneriler” üretilir
- **Zevk etiketleri:** öneri motorunun öne çıkardığı türler ana sayfada çip olarak gösterilir
- **Topluluk yorumları:** her filmde yorum listesi, ortalama topluluk puanı ve
  ad + puan + yorum içeren doğrulanmış yorum formu (`yorum.php`)
- **Filmin konusu:** detay sayfasında ayrı “Filmin Konusu” bölümü
- **Otomatik uyum:** yeni eklenen her film konu, IMDb, puanlama, yorum ve öneri
  sistemine ek kod gerekmeden otomatik dahil olur
- **Paylaşılabilir tema bağlantısı:** `?tema=light` veya `?tema=dark` ile tema seçilebilir
- **Giriş ekranı ve oturum koruması:** `giris.php` üzerinden demo hesapla giriş;
  arşiv, detay, ekleme, düzenleme, puanlama, yorum ve silme sayfaları `girisGerekli()`
  ile korunur. Çıkış `cikis.php` üzerinden yalnızca POST + CSRF ile yapılır.
- **Şifre güvenliği:** demo şifresi düz metin yerine `password_hash()` çıktısıyla
  saklanır ve `password_verify()` ile doğrulanır; başarılı girişte
  `session_regenerate_id()` ile oturum kimliği yenilenir.
- **Rastgele film keşfi:** üst menüdeki ve ana sayfadaki **Rastgele Film**
  kısayolu (`rastgele.php`) arşivden rastgele bir filmin detayını açar.
- **Sıcak açık tema:** beyaz yerine parşömen/bej tonları; kartlar, paneller,
  formlar, menü ve parlama (glow) efektleri açık temaya özel olarak uyarlanır.

---

## 3. Klasör Yapısı

```text
film_arsivi/
│
├── index.php                  → Ana sayfa, listeleme, arama, filtreleme, istatistikler, öneriler
├── giris.php                  → Bağımsız giriş ekranı (POST + CSRF, demo hesap)
├── cikis.php                  → Oturum kapatma (yalnızca POST + CSRF)
├── ekle.php                   → Yeni film ekleme formu ve POST işleme
├── duzenle.php                → Var olan kaydı güncelleme
├── detay.php                  → Film detayı, puanlama, konu, yorumlar, benzer/kişisel öneriler
├── rastgele.php               → Arşivden rastgele film seçip detayına yönlendirir
├── sil.php                    → Kayıt silme işlemi (POST + CSRF)
├── durum.php                  → İzlenme durumunu değiştirme (POST + CSRF)
├── puanla.php                 → Yıldızlı film puanlama işlemi (POST + CSRF)
├── yorum.php                  → Yorum gönderme ve doğrulama işlemi (POST + CSRF)
├── functions.php              → Veri, 50 film, doğrulama, öneri motoru, oturum, güvenlik
│
├── partials/
│   ├── header.php             → Ortak <head>, üst menü, kullanıcı rozeti, bildirim
│   ├── theme_init.php         → Sayfa boyanmadan önce tema tercihini uygular
│   ├── footer.php             → Ortak alt bilgi, silme modalı, JS bağlantısı
│   ├── movie_card.php         → Tek film kartı (IMDb + yorum sayısı ile)
│   ├── film_form.php          → Ekleme / düzenleme formu (IMDb + konu alanları)
│   └── film_preview.php       → Canlı önizleme + ipuçları
│
├── assets/
│   ├── style.css              → Arayüz, sıcak açık tema, giriş ekranı, mobil uyum
│   └── app.js                 → Tema, menü, önizleme, yıldız, sayaç, modal, şifre göster
│
├── ekran-goruntuleri/         → Teslim için ekran görüntüleri
│   ├── 01-ana-sayfa.png
│   ├── 02-film-ekle.png
│   ├── 03-filtreleme.png
│   ├── 04-film-detay.png
│   ├── 05-acik-tema.png
│   ├── 06-yorumlar-puanlama.png
│   └── 07-giris-ekrani.png
│
└── README.md                  → Bu dosya
```

---

## 4. XAMPP Üzerinde Çalıştırma

1. XAMPP Control Panel'i açın.
2. **Apache** servisini başlatın.
3. Proje klasörünü şu konuma koyun:
   ```text
   C:\xampp\htdocs\film_arsivi
   ```
4. Tarayıcıda şu adresi açın:
   ```text
   http://localhost/film_arsivi/
   ```
5. Karşınıza **giriş ekranı** gelir. Demo hesap bilgileriyle giriş yapın:

   | Alan | Değer |
   |------|-------|
   | Kullanıcı adı | `admin` |
   | Şifre | `sinema123` |

   Giriş ekranındaki **Bilgileri doldur** düğmesi bu değerleri otomatik yazar.
6. Giriş sonrası ana sayfada 50 örnek film kaydı, yorumlar ve öneriler otomatik
   olarak oluşturulur.

> **Not:** Proje PHP 8.2 ile geliştirilmiştir. MySQL bu sürümde zorunlu değildir;
> veriler `$_SESSION` içinde tutulur. Apache kapalıysa sayfalar açılmaz.
> Oturum kapatıldığında film ve yorum verileri silinmez; yalnızca giriş bilgisi temizlenir.

### Alternatif: PHP yerleşik sunucusu

```bash
cd C:\xampp\htdocs\film_arsivi
C:\xampp\php\php.exe -S localhost:8080
```

Ardından `http://localhost:8080/` adresini açın.

---

## 5. Kullanım

### Giriş yapma ve oturum

- Uygulama açıldığında `giris.php` ekranı gelir; korumalı sayfalar girişsiz
  açılmak istendiğinde otomatik olarak buraya yönlendirilir.
- Demo hesap: kullanıcı adı **`admin`**, şifre **`sinema123`**.
- **Bilgileri doldur** düğmesi demo bilgilerini forma yazar; göz simgesi şifreyi
  gösterir/gizler.
- Giriş başarılı olduğunda oturum kimliği `session_regenerate_id()` ile yenilenir
  ve kullanıcı gelmek istediği sayfaya yönlendirilir.
- Üst menüdeki kullanıcı rozeti aktif kullanıcıyı gösterir; yanındaki çıkış
  düğmesi (`cikis.php`) oturumu POST + CSRF ile kapatır.
- Çıkışta film ve yorum verileri korunur; yalnızca `$_SESSION['kullanici']`
  temizlenir. Böylece tekrar giriş yapıldığında arşiv olduğu gibi durur.

### Yeni film ekleme

1. Üst menüden **Film Ekle**'ye tıklayın.
2. Film adı, kategori, yönetmen, yıl, kişisel puan, **IMDb puanı**, durum, açıklama ve
   **filmin konusu** alanlarını doldurun.
3. İsterseniz bir afiş URL'si ekleyin; boş bırakırsanız otomatik kapak oluşur.
4. **Filmi Arşive Kaydet** butonuna basın.
5. Başarılı kayıt sonrası ana sayfaya yönlendirilirsiniz ve kayıt listede görünür.
6. Yeni kayıt; detay sayfası, IMDb rozeti, puanlama, yorum formu ve öneri motoruna
   otomatik olarak dahil olur.

### Filtreleme ve arama

- Arama kutusuna film adı, yönetmen veya açıklama yazın.
- Kategori ve durum seçimlerini değiştirdiğinizde filtre otomatik uygulanır.
- Sıralama kutusu ile en yeni, en eski, A–Z, puana göre veya yıla göre sıralayın.
- Hızlı filtre çipleriyle tek tıkla duruma göre süzün.
- `/` veya `Ctrl + K` kısayolu arama kutusuna odaklanır.

### Kayıt güncelleme / silme

- Kart üzerindeki kalem simgesi veya detay sayfasındaki **Düzenle** ile güncelleyin.
- Çöp kutusu simgesiyle silme onay penceresini açın.
- Silme ve güncelleme işlemleri yalnızca **POST** ile çalışır.

### Durum değiştirme

Kart üzerindeki döndürme simgesi durumu şu sırayla değiştirir:

```text
İzlenecek → İzleniyor → İzlendi → İzlenecek
```

### Koyu / açık tema

- Üst menüdeki güneş (açık tema) / ay (koyu tema) düğmesine basın.
- Tercih `localStorage` içinde saklanır; sonraki ziyaretlerde otomatik uygulanır.
- Tema, sayfa yüklenirken daha ilk boyamadan önce uygulandığı için ekranda titreme olmaz.
- `index.php?tema=light` veya `index.php?tema=dark` bağlantılarıyla tema paylaşılabilir.

### Film puanlama

1. Bir filmin detay sayfasını açın.
2. **Filmi Puanla** bölümünde 1–10 arası bir yıldıza tıklayın.
3. **Puanı Kaydet** butonuna basın.
4. Puan, `puanla.php` üzerinden POST + CSRF doğrulamasıyla kaydedilir; kart, detay
   sayfası ve öneri motoru anında güncellenir.

### Yorumlar ve topluluk puanı

- Detay sayfasındaki **İzleyici Yorumları** bölümünde diğer yorumları okuyabilirsiniz.
- **Yorumunu Paylaş** formunda görünen ad, isteğe bağlı puan ve yorum metni girilir.
- Yorum en az 5, en fazla 600 karakter olmalıdır; ad 2–40 karakter arasındadır.
- Yorumlar `$_SESSION['yorumlar']` içinde film ID'sine göre saklanır.
- Puan bırakan yorumların ortalaması “topluluk puanı” olarak gösterilir.
- Yeni eklenen filmlerde yorum listesi boş başlar; ilk yorumu kullanıcı yazar.

### Öneriler

- **Senin İçin Öneriler** bölümü, izlediğiniz/izlemek istediğiniz filmlerin tür ağırlıklarına,
  IMDb puanlarına ve kişisel puanlarınıza göre otomatik hesaplanır.
- İzlenen ve izleniyor durumundaki filmler öneri listesine tekrar eklenmez.
- Bir filmin detay sayfasında hem “Bu Filme Benzer” hem de “Sana Özel Öneriler” listelenir.
- Yeni film eklediğinizde veya puan verdiğinizde öneriler otomatik yenilenir.

---

## 6. Veri Yapısı

Her film `$_SESSION['filmler']` dizisinde şu yapıda tutulur:

```php
[
    'id'       => 1,
    'baslik'   => 'Inception',
    'kategori' => 'Bilim Kurgu / Gerilim',
    'yonetmen' => 'Christopher Nolan',
    'yil'      => 2010,
    'puan'     => 9.0,
    'imdb'     => 8.8,
    'aciklama' => 'Rüyaların içine girerek fikir çalan bir ekibin hikâyesi.',
    'konu'     => 'Dom Cobb, insanların rüyalarına girerek zihinlerindeki sırları çalan ...',
    'durum'    => 'İzlendi',
    'poster'   => '',
    'eklenme'  => '2026-10-01 09:00:00',
    'ozel'     => false,
]
```

- `$_SESSION['filmler']` 50 başlangıç filmini ve kullanıcının eklediği kayıtları tutar.
- `$_SESSION['yorumlar']` film ID'sine göre yorum dizilerini tutar; her yorum
  `ad`, `yorum`, `puan`, `tarih` ve `demo` alanlarını içerir.
- `$_SESSION['son_id']` yeni kayıtlar için artan ID üretir.
- `$_SESSION['kullanici']` giriş yapan kullanıcının adını, rolünü ve giriş
  zamanını tutar; `girisYapildi()` bu dizinin varlığına bakar.
- `$_SESSION['giris_hedef']` girişsiz erişilen sayfanın adresini saklar ve
  başarılı girişten sonra kullanıcı oraya döndürülür.
- `$_SESSION['csrf_token']` form güvenliği için kullanılır.
- `$_SESSION['flash']` işlem sonrası tek seferlik bildirim mesajını tutar.
- `VERI_SURUMU` sabiti, yeni alanlar eklendiğinde eski oturum verilerinin
  kaybolmadan yeni şemayla birleştirilmesini sağlar.

---

## 7. Öne Çıkan PHP Fonksiyonları

`functions.php` içindeki kullanıcı tanımlı fonksiyonlardan bazıları:

| Fonksiyon | Görevi |
|-----------|--------|
| `e($deger)` | `htmlspecialchars()` ile güvenli HTML çıktısı üretir. |
| `girisYapildi()` | Oturumda geçerli bir kullanıcı olup olmadığını döndürür. |
| `aktifKullanici()` | Giriş yapan kullanıcının bilgilerini döndürür. |
| `girisGerekli()` | Korumalı sayfaları korur; giriş yoksa `giris.php`'ye yönlendirir. |
| `girisDene($kullanici, $sifre)` | Demo hesabı `password_verify()` ile doğrular, oturumu yeniler. |
| `cikisYap()` | Kullanıcı oturumunu kapatır, film/yorum verilerini korur. |
| `rastgeleFilm($filmler)` | Arşivden rastgele bir film seçer. |
| `varsayilanFilmler()` | İlk açılışta 50 örnek kayıt (konu ve IMDb puanıyla) döndürür. |
| `filmVerisiniBaslat()` | `$_SESSION['filmler']` dizisini hazırlar. |
| `filmleriGetir(...)` | Arama, kategori, durum ve sıralamaya göre filtreler. |
| `filmSayilari($filmler)` | Toplam, izlenen, izlenecek, izleniyor ve ortalama puanı hesaplar. |
| `kategorileriGetir($filmler)` | Benzersiz kategorileri alfabetik döndürür. |
| `filmEkle($veri)` | Yeni kaydı oturuma ekler, ID döndürür. |
| `filmGuncelle($id, $veri)` | Var olan kaydı günceller. |
| `filmSil($id)` | Kaydı diziden siler. |
| `filmDurumDegistir($id)` | Durumu bir sonraki aşamaya taşır. |
| `filmVerisiniDogrula($girdi)` | Tüm alanları sunucu tarafında doğrular. |
| `csrfToken()` / `csrfDogrula()` | Form güvenlik anahtarı üretir ve doğrular. |
| `flashAyarla()` / `flashGetir()` | Tek seferlik bildirim mesajı yönetir. |
| `benzerFilmler(...)` | Detay sayfası için benzer filmleri seçer. |
| `posterSinifi()` / `filmBasHarfleri()` | Afişsiz kayıtlar için kapak üretir. |
| `filmImdbPuan(array $film)` | IMDb puanını güvenli biçimde döndürür; alan boşsa kişisel puanı kullanır. |
| `izlenenTurAgirliklari($filmler)` | İzlenen/izleniyor filmlerin türlerine ağırlık hesaplar. |
| `ilgiAlanlari($filmler)` | Öne çıkan ilgi alanı türlerini döndürür. |
| `filmOnerileri($filmler, $limit, $haricId)` | İzleme geçmişine göre kişisel öneri listesi üretir. |
| `filmYorumlari($filmId)` | Bir filmin yorumlarını döndürür. |
| `yorumEkle($filmId, $veri)` | Yorumu doğrular ve oturuma ekler. |
| `yorumSayisi($filmId)` | Filmin yorum sayısını döndürür. |
| `yorumOrtalamasi($yorumlar)` | Puan içeren yorumların ortalamasını hesaplar. |
| `isimBasHarfleri($isim)` | Yorum avatarı için baş harfleri üretir. |
| `metinUzunlugu($metin)` | Çok baytlı metin uzunluğunu güvenli biçimde ölçer. |

---

## 8. Veri Doğrulama ve Güvenlik

- **Zorunlu alanlar:** Film adı, kategori, yönetmen, yıl, puan, IMDb puanı, durum ve açıklama.
- **Uzunluk kontrolleri:** Başlık 2–120, kategori ≤ 60, yönetmen ≤ 100,
  açıklama 10–1000, konu ≤ 2000 karakter.
- **Yıl kontrolü:** 1888 ile içinde bulunulan yıl + 2 arasında.
- **Puan kontrolü:** Kişisel puan ve IMDb puanı 0–10 arasında sayı.
- **Afiş URL kontrolü:** Yalnızca `http://` veya `https://` şemalı geçerli URL.
- **Durum kontrolü:** Yalnızca `İzlendi`, `İzlenecek`, `İzleniyor` değerleri.
- **Yorum doğrulaması:** Ad 2–40, yorum 5–600 karakter; puan boşsa veya 1–10 aralığında olmalı.
- **Puanlama doğrulaması:** `puanla.php` yalnızca 1–10 arası sayı kabul eder.
- **Yorum XSS koruması:** Yorum metni ve görünen ad `e()` ile kaçırılarak gösterilir.
- **XSS koruması:** Tüm kullanıcı çıktıları `e()` → `htmlspecialchars()` ile gösterilir.
- **CSRF koruması:** Tüm POST formlarında rastgele token doğrulanır.
- **POST zorunluluğu:** Silme, durum değiştirme ve çıkış GET ile çalışmaz.
- **Oturum koruması:** Arşiv, detay, ekleme, düzenleme, puanlama, yorum ve silme
  sayfaları `girisGerekli()` ile korunur; girişsiz istekler `giris.php`'ye yönlenir.
- **Şifre saklama:** Demo şifresi `password_hash()` ile üretilmiş `bcrypt` çıktısı
  olarak tutulur, `password_verify()` ile doğrulanır; düz metin şifre yoktur.
- **Oturum yenileme:** Başarılı giriş ve çıkışta `session_regenerate_id(true)`
  çağrılarak oturum sabitleme (session fixation) riski azaltılır.
- **Oturum çerezi:** `HttpOnly` ve `SameSite=Lax` olarak ayarlanır.

> Not: İlk sürümde veritabanı kullanılmadığı için SQL enjeksiyonu riski yoktur.
> İlerleyen sürümde MySQL/PDO eklenirken prepared statement kullanılmalıdır.

---

## 9. Test Senaryoları

| # | Senaryo | Beklenen Sonuç |
|---|---------|----------------|
| 1 | Ana sayfayı aç | 50 örnek kayıt, sayaçlar, IMDb ortalaması ve öneriler görünür. |
| 2 | Boş form gönder | Alan bazlı hata mesajları görünür, kayıt eklenmez. |
| 3 | Geçerli form gönder | Kayıt eklenir, ana sayfada en üstte görünür. |
| 4 | Yıl alanına 1800 gir | "Yıl 1888 ile ... arasında olmalıdır" hatası. |
| 5 | Puanı 11 yap | Puan aralığı hatası. |
| 6 | Açıklamayı 5 karakter gir | En az 10 karakter hatası. |
| 7 | Kategori filtresi seç | Yalnızca o kategorideki kayıtlar listelenir. |
| 8 | Durum filtresi seç | Yalnızca seçilen durumdaki kayıtlar listelenir. |
| 9 | Arama kutusuna "Nolan" yaz | Christopher Nolan filmleri listelenir. |
| 10 | Silme işlemini onayla | Kayıt listeden kalkar, bildirim görünür. |
| 11 | Silme isteğini GET ile aç | İşlem yapılmaz, hata bildirimi gösterilir. |
| 12 | Kaydı düzenle | Güncel bilgiler detay ve listede görünür. |
| 13 | Durum simgesine bas | Durum sırayla değişir. |
| 14 | Sayfayı yenile | Kayıtlar `$_SESSION` sayesinde korunur. |
| 15 | Başlığa `<script>` yaz | Metin olarak gösterilir, kod çalışmaz. |
| 16 | Tema düğmesine bas | Koyu/açık tema anında değişir ve sayfa yenilenince korunur. |
| 17 | `?tema=light` bağlantısını aç | Sayfa açık temada yüklenir. |
| 18 | Detay sayfasında 8. yıldıza tıkla ve kaydet | Puan 8 olarak kaydedilir, kart ve öneriler güncellenir. |
| 19 | Geçerli yorum gönder | Yorum listede görünür, topluluk puanı güncellenir. |
| 20 | 3 karakterlik yorum gönder | "En az 5 karakter" hatası görünür, yorum eklenmez. |
| 21 | IMDb alanına 11 gir | "IMDb puanı 0 ile 10 arasında" hatası görünür. |
| 22 | Yeni film ekle ve detayını aç | Konu, IMDb, puanlama, boş yorum listesi ve öneriler otomatik görünür. |
| 23 | İzlenen bir filmi puanla | "Senin İçin Öneriler" sıralaması yeni puana göre değişir. |
| 24 | Giriş yapmadan `index.php` aç | `giris.php`'ye yönlendirilir, bilgi mesajı görünür. |
| 25 | Doğru demo bilgileriyle giriş yap | Ana sayfaya yönlendirilir, üst menüde kullanıcı rozeti görünür. |
| 26 | Yanlış şifreyle giriş dene | "Kullanıcı adı veya şifre hatalı" uyarısı görünür, oturum açılmaz. |
| 27 | Giriş ekranında **Bilgileri doldur** | Kullanıcı adı ve şifre alanları `admin` / `sinema123` ile dolar. |
| 28 | Çıkış düğmesine bas | Oturum kapanır, `giris.php`'ye dönülür; filmler korunur. |
| 29 | Çıkış işlemini GET ile aç | İşlem yapılmaz, hata bildirimi gösterilir. |
| 30 | Üst menüdeki **Rastgele Film** | Arşivden rastgele bir filmin detay sayfası açılır. |

---

## 10. Ekran Görüntüleri

Teslim klasöründeki `ekran-goruntuleri/` dizininde aşağıdaki görüntüler yer alır:

1. `01-ana-sayfa.png` — Koyu tema ana sayfa: hero, istatistikler, IMDb rozetleri,
   “Senin İçin Öneriler” ve film kartları
2. `02-film-ekle.png` — Yeni kayıt formu: IMDb puanı, konu alanı ve canlı önizleme
3. `03-filtreleme.png` — Kategori + durum filtresi ve IMDb puanına göre sıralama
4. `04-film-detay.png` — Film detayı: IMDb, yıldızlı puanlama, konu ve topluluk yorumları
5. `05-acik-tema.png` — Sıcak parşömen/bej tonlarındaki açık tema ana sayfa
6. `06-yorumlar-puanlama.png` — Açık temada puanlama ve yorum bölümleri
7. `07-giris-ekrani.png` — Bağımsız giriş ekranı: demo hesap kartı, şifre göster ve tema düğmesi

> Ekran görüntüleri, yerel PHP sunucusunda (`127.0.0.1:8080`) çalışan uygulamadan
> Microsoft Edge headless ile 1440 piksel genişlikte alınmıştır. Koyu tema için
> `?tema=dark`, açık tema için `?tema=light` bağlantısı kullanılmıştır. Korumalı
> sayfalar demo hesapla giriş yapılmış oturumda görüntülenmiştir.

---

## 11. Bilinen Eksikler ve Sonraki Geliştirmeler

Bu sürüm ödev kapsamındaki MVP'dir. Bilinçli olarak kapsam dışı bırakılanlar:

- MySQL veritabanı entegrasyonu (sonraki aşamada PDO ile)
- Gerçek kullanıcı kaydı ve çoklu kullanıcı yönetimi; şu an tek demo hesap var
- Sayfalama (pagination) — şu an tüm kayıtlar listelenir
- Afiş dosyası yükleme (şu an URL ile)
- Gelişmiş raporlama / grafikler
- Yorumların veritabanında kalıcı olması (şu an oturum tabanlıdır; tarayıcı oturumu
  kapandığında kullanıcı yorumları sıfırlanır, başlangıç yorumları yeniden yüklenir)
- Öneri motorunun makine öğrenmesi yerine kural tabanlı (tür ağırlığı + puan) çalışması
- Tema tercihinin kullanıcı hesabına değil tarayıcıya bağlı olması

Planlanan geliştirmeler: MySQL + PDO, gerçek kullanıcı tablosu ve parola sıfırlama,
sayfalama, dışa aktarma (CSV/JSON), TMDB API ile otomatik film bilgisi ve afiş çekme.

---

## 12. Yapay Zekâ Kullanım Açıklaması

Ödev duyurusunda yapay zekâ kullanımı serbest bırakıldığı için bu bölümde
kullanım şeffaf biçimde açıklanmıştır.

### Kullanılan yapay zekâ araçları

- **OpenCode** (kodlama ajanı) — dosya oluşturma, düzenleme ve test
- **Claude / Büyük Dil Modeli** — kod üretimi ve açıklama desteği

### Kullanım amaçları

- PHP veri katmanı ve CRUD fonksiyonlarının oluşturulması
- Form doğrulama ve CSRF korumasının eklenmesi
- Giriş ekranı, oturum koruması ve şifre doğrulamanın eklenmesi
- Arayüz tasarımının (CSS) ve mobil uyumun geliştirilmesi
- Sıcak açık tema ve tema uyumlu efektlerin hazırlanması
- JavaScript etkileşimlerinin (canlı önizleme, modal, filtre) yazılması
- Hata ayıklama ve kod düzeni önerileri
- README ve test senaryolarının hazırlanması

### Kullanılan örnek istem (prompt)

> "Bu kod bloklarını yani CSS, ekle.php, index.php gibi dosyaları değiştir ve
> bana çok gelişmiş, profesyonel bir web sitesi oluştur. Kodları Visual Studio
> Code'da yazacaksın. Sana gönderdiğim PDF'te yazan şeyleri kullanmayı ve
> onların haricinde web sayfasını geliştirmeyi unutma."

### Öğrencinin kendi katkıları

- Proje konusunun (film/dizi arşivi) ve **CHNStream** markasının belirlenmesi
- Başlangıçtaki `index.php`, `ekle.php`, `functions.php` ve `style.css` dosyalarının hazırlanması
- Örnek film verilerinin ve kategori yapısının belirlenmesi
- Uygulamanın XAMPP üzerinde çalıştırılması ve test edilmesi
- Hata senaryolarının (boş form, geçersiz yıl, XSS denemesi) denenmesi
- Tasarım ve içerik tercihlerinin yönlendirilmesi

> **Sorumluluk notu:** Teslim edilen kodun tamamı öğrenci tarafından
> açıklanabilmelidir. Öğrenci; `foreach`, `if`, `$_SESSION`, `$_POST`, `$_GET`,
> `htmlspecialchars()`, CSRF ve doğrulama mantığını sözlü olarak açıklamaya hazırdır.

---

## 13. Teslim Kontrol Listesi

- [x] Kaynak dosyalar hazır (`index.php`, `ekle.php`, `sil.php`, `functions.php` …)
- [x] En az 5 örnek kayıt var (50 kayıt)
- [x] PHP ile listeleme çalışıyor (`foreach`)
- [x] Yeni kayıt ekleme (POST) çalışıyor
- [x] Boş / hatalı veri kontrolü var (IMDb ve konu dahil)
- [x] Kayıt silme (POST) çalışıyor
- [x] Kategori ve durum filtreleme çalışıyor
- [x] Toplam ve durum sayaçları çalışıyor
- [x] `htmlspecialchars()` ile güvenli çıktı var
- [x] En az iki kullanıcı tanımlı fonksiyon var (25+)
- [x] `$_SESSION` ile kalıcılık var
- [x] Mobil uyumlu arayüz var
- [x] Koyu / açık tema geçişi ve tema kalıcılığı çalışıyor
- [x] Sıcak parşömen/bej açık tema ve tema uyumlu parlama efektleri var
- [x] Giriş ekranı ve oturum koruması çalışıyor (`admin` / `sinema123`)
- [x] Şifre `password_hash()` + `password_verify()` ile doğrulanıyor
- [x] Çıkış (POST + CSRF) çalışıyor ve veriler korunuyor
- [x] Rastgele film keşif kısayolu çalışıyor
- [x] Yıldızlı puanlama (POST + CSRF) çalışıyor
- [x] Yorum ekleme ve topluluk puanı çalışıyor
- [x] İzleme geçmişine göre öneri motoru çalışıyor
- [x] Her filmde konu ve IMDb puanı gösteriliyor
- [x] Yeni eklenen filmler tüm özelliklere otomatik dahil oluyor
- [x] README hazır
- [x] Ekran görüntüleri hazır (`ekran-goruntuleri/`, 7 adet)
- [ ] Proje `OgrenciNo_Ad_Soyad_PHP_Proje.zip` olarak Teams'e yüklenecek

---
**Öğrenci Adı:** Cihan Yılmaz
**Öğrenci No:** 254309003
**Son teslim:** 8 Kasım 2026, 23.59 — Microsoft Teams
