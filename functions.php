<?php
declare(strict_types=1);

/**
 * CHNStream - Kişisel Film ve Dizi Arşivi
 * Ortak veri katmanı, doğrulama, yorum, öneri ve güvenlik fonksiyonları.
 *
 * Proje kapsamı: İnternet Programcılığı I - PHP Kayıt ve Takip Sistemi
 * Veriler PHP dizileri ve $_SESSION içinde tutulur.
 */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/* Temel güvenlik başlıkları. Tüm sayfalar functions.php ile başladığı için
   çıktıdan önce burada gönderilir. */
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header('X-Permitted-Cross-Domain-Policies: none');
}

const UYGULAMA_ADI    = 'CHNStream';
const UYGULAMA_SURUMU = '2.0.0';
const DURUMLAR        = ['İzlendi', 'İzlenecek', 'İzleniyor'];
const VARSAYILAN_PUAN = 8.0;
const VARSAYILAN_IMDB = 7.5;
const VERI_SURUMU     = 5;
const SAYFA_BASINA_KAYIT = 12;
const POSTER_MAKS_BOYUT = 2 * 1024 * 1024; /* 2 MB */
const POSTER_KLASORU    = __DIR__ . '/uploads';
const POSTER_URL_ONEKI  = 'uploads/';

/* Demo giriş hesabı. Şifre düz metin olarak tutulmaz; password_hash() çıktısı kullanılır. */
const DEMO_KULLANICI   = 'admin';
const DEMO_SIFRE_HASH  = '$2y$10$j7uBOC93/BdrYZ/K/0KtROFsvXAnjU4vv7JqKBKaZ9EW2RCA5tntu';
const DEMO_GORUNEN_AD  = 'Demo Kullanıcı';

/* --------------------------------------------------------------------------
 * 1. GÜVENLİ ÇIKTI VE METİN YARDIMCILARI
 * ----------------------------------------------------------------------- */

/**
 * Kullanıcı girdisini HTML sayfasında güvenli biçimde göstermek için kullanılır.
 * XSS (kod çalıştırma) saldırılarına karşı temel korumayı sağlar.
 */
function e(?string $deger): string
{
    return htmlspecialchars((string) $deger, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function kucukHarf(string $metin): string
{
    return function_exists('mb_strtolower')
        ? mb_strtolower($metin, 'UTF-8')
        : strtolower($metin);
}

function metinUzunlugu(string $metin): int
{
    return function_exists('mb_strlen') ? mb_strlen($metin, 'UTF-8') : strlen($metin);
}

/**
 * Başlıktan iki harflik bir kapak monogramı üretir. Afişi olmayan filmlerde
 * şık bir yedek kapak göstermek için kullanılır.
 */
function filmBasHarfleri(string $baslik): string
{
    $parcalar = preg_split('/\s+/u', trim($baslik)) ?: [];
    $harfler  = '';

    foreach (array_slice($parcalar, 0, 2) as $parca) {
        if ($parca !== '') {
            $harfler .= function_exists('mb_substr')
                ? mb_substr($parca, 0, 1, 'UTF-8')
                : substr($parca, 0, 1);
        }
    }

    if ($harfler === '') {
        return '?';
    }

    return function_exists('mb_strtoupper')
        ? mb_strtoupper($harfler, 'UTF-8')
        : strtoupper($harfler);
}

function posterSinifi(string $kategori): string
{
    $ozet = $kategori !== '' ? $kategori : 'Genel';
    $sayi = (int) (hexdec(substr(md5($ozet), 0, 8)) % 6) + 1;

    return 'poster-grad-' . $sayi;
}

function puanFormat(float|int|string $puan): string
{
    return number_format((float) $puan, 1, ',', '.');
}

function tarihFormat(?string $tarih): string
{
    if ($tarih === null || trim($tarih) === '') {
        return '—';
    }

    $zaman = strtotime($tarih);

    return $zaman ? date('d.m.Y', $zaman) : '—';
}

/**
 * CSS / JS gibi statik dosyalara sürüm ekleyerek tarayıcı önbelleğini kırar.
 * Böylece tema ve arayüz güncellemeleri eski önbelleğe takılmaz.
 */
function varlikSurumu(string $goreliYol): string
{
    $tamYol = __DIR__ . '/' . ltrim($goreliYol, '/');
    $zaman  = @filemtime($tamYol);

    return $zaman !== false ? (string) $zaman : UYGULAMA_SURUMU;
}

/**
 * "Dram, Gerilim, Suç" biçimindeki etiket metnini temiz bir diziye çevirir.
 */
function etiketleriAyir(array|string|null $etiketler): array
{
    if (is_array($etiketler)) {
        $parcalar = $etiketler;
    } else {
        $parcalar = preg_split('/[,;]+/u', (string) $etiketler) ?: [];
    }

    $sonuc = [];

    foreach ($parcalar as $parca) {
        $parca = trim((string) $parca);

        if ($parca !== '' && metinUzunlugu($parca) <= 30) {
            $sonuc[kucukHarf($parca)] = $parca;
        }
    }

    return array_values($sonuc);
}

/**
 * Süreyi "2 sa 18 dk" biçiminde gösterir.
 */
function sureFormat(int|string|null $dakika): string
{
    $dakika = (int) $dakika;

    if ($dakika <= 0) {
        return '—';
    }

    $saat = intdiv($dakika, 60);
    $kalan = $dakika % 60;

    if ($saat === 0) {
        return $kalan . ' dk';
    }

    return $kalan > 0 ? $saat . ' sa ' . $kalan . ' dk' : $saat . ' sa';
}

/* --------------------------------------------------------------------------
 * 1b. FAVORİLER
 * ----------------------------------------------------------------------- */

function favoriMi(int $filmId): bool
{
    $favoriler = $_SESSION['favoriler'] ?? [];

    return is_array($favoriler) && in_array($filmId, array_map('intval', $favoriler), true);
}

function favoriSayisi(): int
{
    $favoriler = $_SESSION['favoriler'] ?? [];

    return is_array($favoriler) ? count(array_unique(array_map('intval', $favoriler))) : 0;
}

/**
 * Favori durumunu değiştirir ve yeni durumu döndürür.
 */
function favoriDegistir(int $filmId): bool
{
    if (filmBul($_SESSION['filmler'], $filmId) === null) {
        return false;
    }

    if (!isset($_SESSION['favoriler']) || !is_array($_SESSION['favoriler'])) {
        $_SESSION['favoriler'] = [];
    }

    $_SESSION['favoriler'] = array_values(array_unique(array_map('intval', $_SESSION['favoriler'])));

    $konum = array_search($filmId, $_SESSION['favoriler'], true);

    if ($konum === false) {
        $_SESSION['favoriler'][] = $filmId;

        return true;
    }

    array_splice($_SESSION['favoriler'], $konum, 1);

    return false;
}

/**
 * Favori filmleri arşiv sırasını koruyarak döndürür.
 */
function favoriFilmler(array $filmler): array
{
    $favoriler = $_SESSION['favoriler'] ?? [];

    if (!is_array($favoriler) || $favoriler === []) {
        return [];
    }

    $favoriler = array_map('intval', $favoriler);

    return array_values(array_filter(
        $filmler,
        static fn (array $film): bool => in_array((int) ($film['id'] ?? 0), $favoriler, true)
    ));
}

function durumSinifi(string $durum): string
{
    return match ($durum) {
        'İzlendi'   => 'watched',
        'İzleniyor' => 'watching',
        default     => 'watchlist',
    };
}

function durumIkonu(string $durum): string
{
    return match ($durum) {
        'İzlendi'   => 'fa-circle-check',
        'İzleniyor' => 'fa-circle-play',
        default     => 'fa-bookmark',
    };
}

/**
 * "Bilim Kurgu / Gerilim" gibi kategori metnini tek tek türlere ayırır.
 * Öneri motoru ve benzer film eşleştirmesi bu listeyi kullanır.
 */
function filmTurleri(string $kategori): array
{
    $parcalar = preg_split('/[\/,;]+/u', $kategori) ?: [];
    $turler   = [];

    foreach ($parcalar as $parca) {
        $parca = trim($parca);

        if ($parca !== '') {
            $turler[kucukHarf($parca)] = $parca;
        }
    }

    return array_values($turler);
}

/* --------------------------------------------------------------------------
 * 2. OTURUM VERİSİ VE ÖRNEK KAYITLAR
 * ----------------------------------------------------------------------- */

/**
 * İlk açılışta 50 örnek film/dizi oluşturur. Böylece ana sayfa boş gelmez,
 * listeleme döngüsü, öneri motoru ve yorum bölümü hemen test edilebilir.
 */
function varsayilanFilmler(): array
{
    /*
     * [başlık, kategori, yönetmen, yıl, kişisel puan, IMDb puanı, durum,
     *  kısa açıklama, konu]
     */
    $kayitlar = [
        ["Inception", "Bilim Kurgu / Gerilim", "Christopher Nolan", 2010, 9.0, 8.8, "İzlendi",
            "Rüyaların içine girerek fikir çalan bir ekibin, gerçeklik algısını alt üst eden hikâyesi.",
            "Dom Cobb, insanların rüyalarına girerek zihinlerindeki sırları çalan yetenekli bir hırsızdır. Son görevinde bu kez bir fikri çalmak değil, hedefin bilinçaltına bir fikir yerleştirmek zorundadır. Ekip, katman katman kurulan rüyaların içinde zamanla yarışırken Cobb kendi geçmişiyle de yüzleşir."],
        ["Interstellar", "Bilim Kurgu / Dram", "Christopher Nolan", 2014, 9.2, 8.7, "İzlendi",
            "İnsanlığın geleceği için uzayın derinliklerine yapılan duygusal ve görsel açıdan etkileyici bir yolculuk.",
            "Dünya yaşanmaz hale gelirken eski bir NASA pilotu, insanlığa yeni bir yuva bulmak için yıldızlararası bir yolculuğa çıkar. Kızıyla arasındaki bağ, uzayın bükülen zamanı ve fedakârlık temasıyla birlikte hikâyenin duygusal merkezini oluşturur."],
        ["The Godfather", "Suç / Dram", "Francis Ford Coppola", 1972, 9.5, 9.2, "İzlenecek",
            "Bir mafya ailesinin iktidar mücadelesini, sadakati ve aile bağlarını anlatan sinema klasiği.",
            "Corleone ailesinin patriği Vito, suç dünyasındaki gücünü çocuklarına devretmeye hazırlanırken aile içindeki dengeler değişir. Savaştan dönen en küçük oğul Michael, başlangıçta aile işlerinden uzak durmak isterken kendini iktidar mücadelesinin tam ortasında bulur."],
        ["The Matrix", "Aksiyon / Bilim Kurgu", "Lana ve Lilly Wachowski", 1999, 9.0, 8.7, "İzlendi",
            "Gerçekliğin bir simülasyon olduğunu keşfeden Neo'nun, sistemin zincirlerini kırma hikâyesi.",
            "Programcı Neo, yaşadığı dünyanın aslında makineler tarafından kurulmuş bir simülasyon olduğunu öğrenir. Morpheus'un yönlendirmesiyle gerçek dünyaya uyanan Neo, insanlığı özgürleştirebilecek seçilmiş kişi olup olmadığını keşfetmeye başlar."],
        ["İncir Reçeli", "Dram / Romantik", "Aytaç Ağırlar", 2011, 7.4, 6.6, "İzlendi",
            "İki genç insanın hayata tutunma çabasını ve birbirine dokunan hikâyesini anlatan Türk filmi.",
            "Hayatın zorluklarıyla baş etmeye çalışan iki genç, beklenmedik bir anda kesişen yollarında birbirlerine tutunur. Film, aşkın yanında yalnızlık, umut ve hayatta kalma mücadelesini sade bir dille anlatır."],
        ["Skyscraper", "Aksiyon / Gerilim", "Rawson Marshall Thurber", 2018, 6.2, 5.8, "İzlenecek",
            "Yangın çıkan dev bir gökdelende ailesini kurtarmaya çalışan eski bir FBI ajanının hikâyesi.",
            "Eski FBI ajanı Will Sawyer, dünyanın en yüksek gökdelenlerinden birinde güvenlik uzmanı olarak çalışır. Bina sabotaja uğrayıp alevler içinde kalınca, üst katlarda mahsur kalan ailesini kurtarmak için imkânsız bir tırmanışa başlar."],
        ["Prison Break", "Suç / Dram", "Paul Scheuring", 2005, 8.8, 8.3, "İzleniyor",
            "Ağabeyini kurtarmak için hapishaneye giren bir mühendisin zekâ dolu kaçış planı.",
            "Michael Scofield, işlemediği bir suçtan hüküm giyen ağabeyini kurtarmak için kendi planını devreye sokar. Hapishanenin mimari planlarını bedenine dövme olarak taşıyan Michael, mahkûmlardan gardiyanlara kadar herkesi hesaba katan bir kaçış planı kurar."],
        ["Dune: Part Two", "Bilim Kurgu / Macera", "Denis Villeneuve", 2024, 8.7, 8.5, "İzlenecek",
            "Paul Atreides'in kaderini kabullenirken Arrakis'in geleceğini belirleyen destansı mücadelesi.",
            "Paul Atreides, ailesinin yıkımının ardından Fremen halkıyla birlikte Arrakis'in çöllerinde yaşamaya başlar. İntikam arzusu ile kehanet arasında sıkışan Paul, evrenin geleceğini değiştirecek bir savaşın merkezine doğru ilerler."],
        ["Oppenheimer", "Biyografi / Dram", "Christopher Nolan", 2023, 8.9, 8.3, "İzlendi",
            "Atom bombasının babası olarak anılan J. Robert Oppenheimer'ın bilim, güç ve vicdanla imtihanı.",
            "II. Dünya Savaşı sırasında bilim insanları, atom bombasını geliştirmek için gizli bir projede bir araya gelir. Projenin başındaki Oppenheimer, bilimsel başarının gölgesinde büyüyen ahlaki sorumlulukla ve siyasi baskılarla yüzleşir."],
        ["The Dark Knight", "Aksiyon / Suç", "Christopher Nolan", 2008, 9.4, 9.0, "İzlendi",
            "Batman'in kaosun mimarı Joker ile girdiği zekâ savaşını anlatan karanlık süper kahraman filmi.",
            "Gotham'ı suçtan temizlemeye çalışan Batman, kuralsız ve öngörülemez bir düşmanla karşılaşır. Joker'in oyunları şehri kaosa sürüklerken Batman, kahraman olmanın sınırlarını ve kendi ilkelerini sorgulamak zorunda kalır."],
        ["Pulp Fiction", "Suç / Dram", "Quentin Tarantino", 1994, 9.1, 8.9, "İzlenecek",
            "Birbirine düğümlenen suç hikâyelerini kara mizah ve unutulmaz diyaloglarla anlatan film.",
            "Birkaç farklı suç hikâyesi, doğrusal olmayan bir zaman akışıyla birbirine bağlanır. Kiralık katiller, bir boksör ve gangsterlerin yolları beklenmedik olaylar sırasında kesişirken her bölüm kendi absürt ritmini kurar."],
        ["Fight Club", "Dram / Gerilim", "David Fincher", 1999, 8.9, 8.8, "İzlendi",
            "Tüketim kültürüne yabancılaşan bir adamın kurduğu yeraltı kulübünün kontrolden çıkan hikâyesi.",
            "Sıradan hayatından bunalan bir ofis çalışanı, karizmatik Tyler Durden ile tanışır. Birlikte kurdukları dövüş kulübü kısa sürede büyür ve kimlik, tüketim ve erkeklik üzerine karanlık bir hesaplaşmaya dönüşür."],
        ["Forrest Gump", "Dram / Romantik", "Robert Zemeckis", 1994, 8.8, 8.8, "İzlendi",
            "Saf bir kalbin gözünden Amerika'nın yakın tarihine bakan sıcak ve duygusal bir hikâye.",
            "Forrest Gump, düşük zekâlı olarak görülmesine rağmen hayatın ona sunduğu her fırsatı cesaretle karşılar. Vietnam'dan ping-ponga, büyük tarihsel olaylardan çocukluk aşkı Jenny'ye uzanan yolculuğu, iyiliğin gücünü anlatır."],
        ["The Shawshank Redemption", "Dram", "Frank Darabont", 1994, 9.6, 9.3, "İzlendi",
            "Hapishanede umudunu kaybetmeyen bir adamın dostluk ve özgürlük hikâyesi.",
            "Haksız yere müebbet hapis cezası alan bankacı Andy Dufresne, Shawshank hapishanesinde zorlu bir hayata başlar. Yıllar içinde kurduğu dostluklar ve aklını kullanarak geliştirdiği plan, umudun en karanlık yerde bile yaşayabileceğini gösterir."],
        ["Gladiator", "Aksiyon / Dram", "Ridley Scott", 2000, 8.6, 8.5, "İzlenecek",
            "İmparatorluğa ihanet edilen bir generalin gladyatör olarak intikam arayışı.",
            "Roma'nın sevilen generali Maximus, imparatorun ölümünün ardından ihanete uğrar ve ailesini kaybeder. Köle olarak satılan Maximus, arenada gladyatör olarak yükselirken hem intikamını hem de adalet duygusunu geri kazanmaya çalışır."],
        ["Titanic", "Dram / Romantik", "James Cameron", 1997, 8.0, 7.9, "İzlendi",
            "Batan dev gemide başlayan imkânsız bir aşkın destansı hikâyesi.",
            "Zengin bir ailenin kızı Rose ile gemideki yerini arayan genç ressam Jack, Titanic'te tanışır. Sınıf farklılıklarına rağmen yakınlaşan ikili, geminin buzdağına çarpmasıyla birlikte hayatta kalma mücadelesi verir."],
        ["Avatar", "Bilim Kurgu / Macera", "James Cameron", 2009, 8.1, 7.9, "İzlenecek",
            "Pandora gezegeninde insan ve Na'vi kültürünün çatışmasını anlatan görsel bir macera.",
            "Pandora'nın değerli kaynaklarına ulaşmak isteyen insanlar, Na'vi halkının yaşam alanını tehdit eder. Na'vi bedenine aktarılan eski bir asker, gezegenin doğasıyla bağ kurdukça hangi tarafın haklı olduğunu sorgulamaya başlar."],
        ["Avengers: Endgame", "Aksiyon / Macera", "Anthony ve Joe Russo", 2019, 8.4, 8.4, "İzlendi",
            "Sonsuzluk taşlarıyla kaybedilenleri geri getirmeye çalışan kahramanların finali.",
            "Thanos'un zaferinin ardından evrenin yarısı yok olmuştur. Hayatta kalan Avengers, zaman yolculuğu ihtimali doğduğunda son bir kez bir araya gelerek kaybettiklerini geri getirmek ve evrenin kaderini değiştirmek için mücadele eder."],
        ["Joker", "Suç / Dram", "Todd Phillips", 2019, 8.7, 8.4, "İzlendi",
            "Toplumdan dışlanan bir adamın kaosa sürüklenişini anlatan karanlık karakter hikâyesi.",
            "Gotham'da komedyenlik hayali kuran Arthur Fleck, yalnızlık ve toplumsal baskıyla giderek daha fazla yalıtılır. Aldığı darbeler sonrasında içindeki öfke açığa çıkar ve şehrin karanlık bir simgesi haline gelir."],
        ["Parasite", "Dram / Gerilim", "Bong Joon-ho", 2019, 9.0, 8.5, "İzlenecek",
            "Yoksul bir ailenin zengin bir eve sızmasını anlatan gerilim dolu toplumsal hiciv.",
            "Maddi sıkıntı çeken Kim ailesi, zengin Park ailesinin evinde iş bulmak için küçük yalanlar ve planlar kurar. İki sınıfın aynı çatı altında kesişmesi, kısa sürede kontrol edilemeyen bir gerilime dönüşür."],
        ["The Silence of the Lambs", "Suç / Gerilim", "Jonathan Demme", 1991, 8.5, 8.6, "İzlenecek",
            "Genç bir FBI ajanının seri katili yakalamak için zeki bir suçluyla kurduğu tehlikeli ilişki.",
            "Ajan Clarice Starling, bir seri katilin izini sürmek için hapisteki psikiyatrist Hannibal Lecter'dan yardım ister. Lecter'ın zekâsı ve manipülasyonu, Clarice'in hem davayı hem de kendi korkularını çözmesini gerektirir."],
        ["Se7en", "Suç / Gerilim", "David Fincher", 1995, 8.6, 8.6, "İzlendi",
            "Yedi ölümcül günahı temel alan seri katilin peşindeki iki dedektifin karanlık hikâyesi.",
            "Emekliliğine yaklaşan dedektif Somerset ile genç meslektaşı Mills, sıra dışı cinayetleri çözmek için birlikte çalışır. Katil, işlediği suçları yedi ölümcül günahla ilişkilendirirken dedektifler ahlaki sınırların zorlandığı bir finale doğru ilerler."],
        ["The Prestige", "Dram / Gerilim", "Christopher Nolan", 2006, 8.7, 8.5, "İzlendi",
            "İki sihirbazın hırs ve takıntıyla gölgelenen rekabetinin şaşırtıcı hikâyesi.",
            "Aynı dönemde sahnede yükselen iki sihirbaz, birbirlerinin sırlarını çalmaya ve gösterilerini gölgelemeye başlar. Rekabet büyüdükçe dostlukları yok olur ve her biri başarı uğruna en değerli şeylerinden vazgeçer."],
        ["Memento", "Gerilim / Gizem", "Christopher Nolan", 2000, 8.4, 8.4, "İzlenecek",
            "Kısa süreli hafızasını kaybeden bir adamın intikam arayışında gerçeği yeniden kurma çabası.",
            "Karısının katilini bulmaya çalışan Leonard, yeni anılar oluşturamadığı için hayatını notlar ve dövmelerle yönetir. Hikâye geriye doğru akarken izleyici de onunla birlikte hangi bilginin gerçek olduğunu sorgular."],
        ["Shutter Island", "Gerilim / Gizem", "Martin Scorsese", 2010, 8.2, 8.2, "İzlendi",
            "Akıl hastanesinde kaybolan bir hastayı arayan dedektifin gerçeklikle yüzleşmesi.",
            "Dedektif Teddy Daniels, kapalı bir akıl hastanesinde yaşanan kaybolma olayını araştırmak için adaya gelir. Soruşturma ilerledikçe hastanenin sırları ve Teddy'nin kendi geçmişi iç içe geçer."],
        ["The Departed", "Suç / Gerilim", "Martin Scorsese", 2006, 8.6, 8.5, "İzlenecek",
            "Polis ve mafya arasında köstebek avını anlatan gerilim dolu suç hikâyesi.",
            "Boston polisi, mafya ailesine sızmak için genç bir ajanı görevlendirir. Aynı anda mafya da polis teşkilatına kendi adamını yerleştirmiştir; iki taraf da diğerinin köstebeğini bulmaya çalışırken güven kavramı çöker."],
        ["Goodfellas", "Suç / Dram", "Martin Scorsese", 1990, 8.7, 8.7, "İzlenecek",
            "Mafya dünyasına adım atan bir gencin yükselişini ve düşüşünü anlatan gerçek hikâye.",
            "Henry Hill, çocukluğundan itibaren mafya hayatının cazibesine kapılır ve zamanla suç dünyasında yükselir. Para, sadakat ve şiddetle örülü bu hayat, ihanet ve parçalanan ilişkilerle birlikte çözülmeye başlar."],
        ["The Wolf of Wall Street", "Biyografi / Suç", "Martin Scorsese", 2013, 8.4, 8.2, "İzlendi",
            "Borsa dünyasında hızla yükselen bir dolandırıcının abartılı hayat hikâyesi.",
            "Jordan Belfort, küçük bir borsa şirketinden kısa sürede büyük bir imparatorluk kurar. Para, lüks ve aşırılıkla dolu hayatı, FBI soruşturması ve kendi hatalarıyla birlikte kontrolden çıkar."],
        ["Django Unchained", "Western / Dram", "Quentin Tarantino", 2012, 8.5, 8.5, "İzlenecek",
            "Özgürlüğüne kavuşan bir kölenin karısını kurtarmak için çıktığı intikam yolculuğu.",
            "Django, bir ödül avcısının yardımıyla özgürlüğüne kavuşur ve karısını bulmak için yola çıkar. Güney'in acımasız plantasyon dünyasında adaleti kendi eline alan Django, güçlü bir intikam hikâyesinin merkezine yerleşir."],
        ["Inglourious Basterds", "Savaş / Dram", "Quentin Tarantino", 2009, 8.4, 8.4, "İzlenecek",
            "II. Dünya Savaşı'nda Nazi hedeflerine suikast düzenleyen ekibin gerilimli hikâyesi.",
            "Nazi işgali altındaki Fransa'da bir grup Amerikalı asker, Nazi yetkililerini hedef alan bir görev yürütür. Bir sinema salonunda kesişen planlar, tarihin akışını değiştirebilecek gerilimli bir finale doğru ilerler."],
        ["The Grand Budapest Hotel", "Komedi / Dram", "Wes Anderson", 2014, 8.0, 8.1, "İzlendi",
            "Renkli görselliği ve kendine özgü mizahıyla bir otelin ve dönemin hikâyesi.",
            "Ünlü kapı görevlisi Gustave H. ile genç lobi görevlisi Zero, otelin müşterilerinden birinin ölümüyle başlayan bir maceraya sürüklenir. Avrupa'nın değişen tarihi, otelin renkli dünyası üzerinden mizahi bir dille anlatılır."],
        ["Whiplash", "Dram / Müzik", "Damien Chazelle", 2014, 8.6, 8.5, "İzlendi",
            "Mükemmeliyet peşindeki genç bir davulcunun acımasız hocasıyla hesaplaşması.",
            "Caz davulcusu Andrew, hayallerindeki konservatuvara kabul edilir ve zorlu bir hocanın sınıfına girer. Hocanın sert yöntemleri ile Andrew'un büyük olma arzusu, yıkıcı bir rekabete ve kendi sınırlarını aşma denemesine dönüşür."],
        ["La La Land", "Müzikal / Romantik", "Damien Chazelle", 2016, 8.1, 8.0, "İzlenecek",
            "Los Angeles'ta hayallerinin peşinden giden iki sanatçının aşk ve kariyer hikâyesi.",
            "Caz piyanisti Sebastian ile oyuncu olmak isteyen Mia, hayallerini gerçekleştirmeye çalışırken birbirlerine âşık olur. Başarı ve aşk arasındaki denge, ikisinin de kariyerinde önemli kararlar vermesini gerektirir."],
        ["The Social Network", "Biyografi / Dram", "David Fincher", 2010, 8.2, 7.8, "İzlendi",
            "Facebook'un kuruluşunu ve kurucular arasındaki gerilimi anlatan modern hikâye.",
            "Harvard öğrencisi Mark Zuckerberg, kampüste hızla yayılan bir sosyal ağ kurar. Platform büyüdükçe arkadaşlıklar, ortaklıklar ve fikir sahipliği konusundaki anlaşmazlıklar mahkemeye taşınır."],
        ["The Truman Show", "Dram / Bilim Kurgu", "Peter Weir", 1998, 8.3, 8.2, "İzlendi",
            "Hayatının bir televizyon programı olduğunu keşfeden bir adamın özgürlük arayışı.",
            "Truman Burch, küçük bir kasabada sıradan bir hayat yaşadığını düşünür. Ancak günlük rutinindeki tuhaflıklar, tüm hayatının kameralarla izlenen bir gösteri olduğunu fark etmesine yol açar."],
        ["Eternal Sunshine of the Spotless Mind", "Bilim Kurgu / Romantik", "Michel Gondry", 2004, 8.5, 8.3, "İzlenecek",
            "Acı veren anıları silme sürecinde aşkın izini süren sıra dışı bir hikâye.",
            "Joel ve Clementine'in ilişkisi sona erdiğinde Clementine, Joel'e dair anılarını sildirir. Aynı işlemi yaptıran Joel, anıları silinirken ilişkisinin değerini yeniden keşfeder ve onları kaybetmemek için mücadele eder."],
        ["Her", "Bilim Kurgu / Romantik", "Spike Jonze", 2013, 8.2, 8.0, "İzlenecek",
            "Yapay zekâ ile insan arasındaki duygusal bağın yalnızlık ve sevgi üzerinden anlatımı.",
            "Yalnız bir yazar, yeni bir işletim sistemiyle tanışır ve zamanla ona âşık olur. İnsan ile yapay zekâ arasındaki ilişki, sevginin doğası ve dijital çağda yalnızlık üzerine duygusal bir sorgulamaya dönüşür."],
        ["Blade Runner 2049", "Bilim Kurgu / Gerilim", "Denis Villeneuve", 2017, 8.4, 8.0, "İzlendi",
            "Geçmişin sırlarını araştıran bir replikanın kimlik arayışını anlatan görsel şölen.",
            "Los Angeles'ın distopik geleceğinde replika avcısı K, uzun süredir saklanan bir sırrı ortaya çıkarır. Bu keşif, onu kendi kimliği ve insanlıkla replikalar arasındaki sınır hakkında büyük bir sorgulamaya götürür."],
        ["Arrival", "Bilim Kurgu / Dram", "Denis Villeneuve", 2016, 8.5, 7.9, "İzlendi",
            "Dünyaya gelen gizemli uzaylılarla iletişim kurmaya çalışan bir dilbilimcinin hikâyesi.",
            "Dünyanın farklı noktalarına inen gizemli araçlarla iletişim kurmak için dilbilimci Louise Banks görevlendirilir. Zaman ve dil arasındaki ilişkiyi çözmeye çalışırken geçmişi ve geleceği birbirine bağlayan bir gerçekle yüzleşir."],
        ["Mad Max: Fury Road", "Aksiyon / Macera", "George Miller", 2015, 8.2, 8.1, "İzlenecek",
            "Çöl dünyasında özgürlük için kaçan bir grubun nefes kesen aksiyon hikâyesi.",
            "Kıyamet sonrası çölde yaşayan Max, özgürlük peşindeki Furiosa ile yollarını birleştirir. Zalim bir liderin ordusundan kaçarken yakıt, su ve hayatta kalma mücadelesi hiç durmayan bir kovalamacaya dönüşür."],
        ["The Lord of the Rings: The Fellowship of the Ring", "Fantastik / Macera", "Peter Jackson", 2001, 9.0, 8.9, "İzlendi",
            "Orta Dünya'yı kurtarmak için yola çıkan yüzük kardeşliğinin destansı başlangıcı.",
            "Genç hobbit Frodo, yüzüğün gerçek gücünü öğrendiğinde onu yok etmek için uzun bir yolculuğa çıkar. Farklı halklardan oluşan bir kardeşlik, Orta Dünya'yı karanlığa sürükleyebilecek yüzüğü taşımak için bir araya gelir."],
        ["The Lord of the Rings: The Two Towers", "Fantastik / Macera", "Peter Jackson", 2002, 8.8, 8.8, "İzlenecek",
            "Dağılan kardeşliğin Orta Dünya'daki savaşın ortasında yeniden bir araya gelme hikâyesi.",
            "Yüzük taşıyıcısı Frodo ve Sam, yolculuklarına devam ederken Gollum'un rehberliğine mecbur kalır. Diğer yanda Rohan ve Isengard arasındaki savaş büyürken kardeşliğin üyeleri umutsuz bir direnişin parçası olur."],
        ["The Lord of the Rings: The Return of the King", "Fantastik / Macera", "Peter Jackson", 2003, 9.1, 9.0, "İzlenecek",
            "Yüzüğün kaderinin belirlendiği, Orta Dünya'nın son büyük savaşı.",
            "Frodo ve Sam, yüzüğü yok etmek için Mordor'a yaklaşırken büyük ordular son savaşa hazırlanır. Fedakârlık, dostluk ve umut, karanlığın karşısında verilen mücadelenin merkezinde yer alır."],
        ["Harry Potter and the Sorcerer's Stone", "Fantastik / Aile", "Chris Columbus", 2001, 8.0, 7.6, "İzlendi",
            "Hogwarts'a başlayan genç bir büyücünün büyülü dünyayla tanışma hikâyesi.",
            "Harry Potter, on birinci yaş gününde büyücü olduğunu ve Hogwarts'a davet edildiğini öğrenir. Yeni arkadaşlar edinirken okulun koridorlarında saklanan bir sır ve ailesiyle ilgili geçmişin izlerini keşfeder."],
        ["Spirited Away", "Animasyon / Fantastik", "Hayao Miyazaki", 2001, 8.9, 8.6, "İzlendi",
            "Ruhlar dünyasına sürüklenen küçük bir kızın cesaret ve büyüme hikâyesi.",
            "Chihiro, ailesiyle birlikte geldiği ıssız bir yerde ruhlar dünyasına girer ve ailesi kaybolur. Adını ve özgürlüğünü geri kazanmak için ruhlar hamamında çalışmaya başlayan Chihiro, cesaretini ve kendi gücünü keşfeder."],
        ["Your Name", "Animasyon / Romantik", "Makoto Shinkai", 2016, 8.4, 8.4, "İzlenecek",
            "Bedenleri yer değiştiren iki gencin zaman ve mesafe boyunca birbirini arayışı.",
            "Taşrada yaşayan Mitsuha ile Tokyo'da yaşayan Taki, bir sabah bedenlerini değiştirdiklerini fark eder. Birbirlerinin hayatına alışırken aralarındaki bağ, zamanı ve mekânı aşan duygusal bir arayışa dönüşür."],
        ["Spider-Man: Into the Spider-Verse", "Animasyon / Aksiyon", "Bob Persichetti", 2018, 8.6, 8.4, "İzlendi",
            "Farklı evrenlerden gelen örümcek kahramanların bir araya geldiği yenilikçi animasyon.",
            "Genç Miles Morales, örümcek güçleri kazandıktan sonra farklı evrenlerden gelen Spider-Man'lerle tanışır. Evrenler arası bir tehdidi durdurmak için kendi kahramanlığını keşfetmesi ve ekibin parçası olmayı öğrenmesi gerekir."],
        ["The Batman", "Suç / Aksiyon", "Matt Reeves", 2022, 8.0, 7.8, "İzlenecek",
            "Gotham'ın karanlık sokaklarında bir seri katilin izini süren dedektif Batman.",
            "Batman, Gotham'da işlenen gizemli cinayetleri araştırırken şehrin yozlaşmış güç yapısını keşfeder. Selina Kyle ve polisle kurduğu ilişkiler, onu kendi ailesinin geçmişiyle yüzleşmeye götürür."],
        ["Babam ve Oğlum", "Dram / Aile", "Çağan Irmak", 2005, 9.0, 8.1, "İzlendi",
            "Bir babanın ve oğlunun yıllar sonra yeniden kurduğu bağın duygusal hikâyesi.",
            "Sadık, eşinin ölümünden sonra oğluyla birlikte memleketine döner. Ailesiyle arasındaki kırgınlıklar ve geçmişin acıları, baba-oğul ilişkisi üzerinden sevgi, kayıp ve affetme temalarıyla anlatılır."],
        ["Kış Uykusu", "Dram", "Nuri Bilge Ceylan", 2014, 8.7, 8.1, "İzlenecek",
            "Kapadokya'da bir otel işletmecisinin ailesi ve çevresiyle yaşadığı ahlaki hesaplaşma.",
            "Eski bir oyuncu olan Aydın, Kapadokya'daki otelinde karısı ve kız kardeşiyle birlikte yaşar. Yerel halkla kurduğu ilişkiler ve aile içindeki gerilimler, sınıf, gurur ve iletişimsizlik üzerine uzun soluklu bir hesaplaşmaya dönüşür."],
    ];

    $filmler = [];

    foreach ($kayitlar as $sira => $kayit) {
        $filmler[] = [
            'id'       => $sira + 1,
            'baslik'   => (string) $kayit[0],
            'kategori' => (string) $kayit[1],
            'yonetmen' => (string) $kayit[2],
            'yil'      => (int) $kayit[3],
            'puan'     => (float) $kayit[4],
            'imdb'     => (float) $kayit[5],
            'durum'    => (string) $kayit[6],
            'aciklama' => (string) $kayit[7],
            'konu'     => (string) $kayit[8],
            'poster'   => '',
            'eklenme'  => date('Y-m-d H:i:s', strtotime('2026-10-01 09:00:00') + ($sira * 18000)),
            'ozel'     => false,
        ];
    }

    return $filmler;
}

/**
 * Eski oturumlardaki kayıtları yeni 50 filmlik arşivle birleştirir.
 * Kullanıcının durum, puan ve afiş gibi değişiklikleri korunur.
 */
function filmleriBirlestir(array $varsayilan, array $mevcut): array
{
    $mevcutBasliklar = [];

    foreach ($mevcut as $film) {
        $anahtar = kucukHarf(trim((string) ($film['baslik'] ?? '')));

        if ($anahtar !== '') {
            $mevcutBasliklar[$anahtar] = $film;
        }
    }

    $sonuc = [];

    foreach ($varsayilan as $film) {
        $anahtar = kucukHarf(trim((string) $film['baslik']));

        if (isset($mevcutBasliklar[$anahtar])) {
            $eski = $mevcutBasliklar[$anahtar];

            foreach (['durum', 'puan', 'poster', 'aciklama', 'konu', 'imdb', 'eklenme'] as $alan) {
                if (array_key_exists($alan, $eski) && $eski[$alan] !== '' && $eski[$alan] !== null) {
                    $film[$alan] = $eski[$alan];
                }
            }

            unset($mevcutBasliklar[$anahtar]);
        }

        $sonuc[] = $film;
    }

    foreach ($mevcutBasliklar as $eski) {
        $eski['ozel'] = true;
        $sonuc[] = $eski;
    }

    foreach ($sonuc as $sira => &$film) {
        $film['id'] = $sira + 1;
    }
    unset($film);

    return $sonuc;
}

/**
 * $_SESSION içindeki film dizisini hazırlar. Sayfa yenilendiğinde kayıtların
 * korunmasını sağlar; eski sürümleri yeni alanlarla uyumlu hale getirir.
 */
function filmVerisiniBaslat(): void
{
    $ilkKurulum = !isset($_SESSION['filmler']) || !is_array($_SESSION['filmler']);

    if ($ilkKurulum) {
        $_SESSION['filmler'] = varsayilanFilmler();
    } elseif ((int) ($_SESSION['veri_surumu'] ?? 1) < VERI_SURUMU) {
        $_SESSION['filmler'] = filmleriBirlestir(varsayilanFilmler(), $_SESSION['filmler']);
    }

    $_SESSION['veri_surumu'] = VERI_SURUMU;

    foreach ($_SESSION['filmler'] as &$film) {
        if (!isset($film['imdb']) || !is_numeric($film['imdb'])) {
            $film['imdb'] = (float) ($film['puan'] ?? 0);
        }

        if (!isset($film['konu']) || trim((string) $film['konu']) === '') {
            $film['konu'] = (string) ($film['aciklama'] ?? '');
        }

        if (!isset($film['ozel'])) {
            $film['ozel'] = false;
        }

        if (!isset($film['sure']) || !is_numeric($film['sure'])) {
            $film['sure'] = 0;
        }

        if (!isset($film['ulke'])) {
            $film['ulke'] = '';
        }

        if (!isset($film['etiketler'])) {
            $film['etiketler'] = '';
        }
    }
    unset($film);

    $idler = array_map(
        static fn (array $film): int => (int) ($film['id'] ?? 0),
        $_SESSION['filmler']
    );

    $_SESSION['son_id'] = $idler === [] ? 0 : max($idler);

    if (!isset($_SESSION['favoriler']) || !is_array($_SESSION['favoriler'])) {
        $_SESSION['favoriler'] = [];
    }

    $_SESSION['favoriler'] = array_values(array_unique(array_filter(
        array_map('intval', $_SESSION['favoriler']),
        static fn (int $id): bool => in_array($id, $idler, true)
    )));

    yorumlariBaslat();
}

/* --------------------------------------------------------------------------
 * 3. YORUM VERİSİ
 * ----------------------------------------------------------------------- */

function yeniYorumId(): int
{
    $_SESSION['yorum_son_id'] = ((int) ($_SESSION['yorum_son_id'] ?? 0)) + 1;

    return $_SESSION['yorum_son_id'];
}

/**
 * Örnek topluluk yorumları üretir. Yeni eklenen kullanıcı filmlerinde
 * yorumlar boş başlar; böylece bölüm her filmde otomatik olarak çalışır.
 */
function varsayilanFilmYorumlari(array $film): array
{
    $isimler = [
        'Elif K.', 'Mert A.', 'Zeynep D.', 'Can Y.',
        'Selin T.', 'Burak Ö.', 'Deniz A.', 'Ece M.',
    ];

    $sablonlar = [
        ['puan' => 9, 'metin' => '{baslik} için {kategori} türünü sevenlere rahatça önerebilirim. Atmosferi ve finali beklentimin üzerindeydi.'],
        ['puan' => 8, 'metin' => '{yonetmen} imzası her karede hissediliyor. {baslik} biraz ağır ilerliyor ama sonunda karşılığını veriyor.'],
        ['puan' => 9, 'metin' => 'İlk yarısı sakin, ikinci yarısı çok güçlü. {baslik} özellikle ikinci izleyişte daha fazla detay veriyor.'],
        ['puan' => 10, 'metin' => 'Oyunculuklar dengeli, müzikler etkileyici. {baslik} arşivde kalıcı yer edinen filmlerden biri oldu.'],
        ['puan' => 7, 'metin' => 'Beklentim yüksekti; {baslik} iyi bir yapım ama benim için kült seviyesine çıkmadı.'],
        ['puan' => 8, 'metin' => '{kategori} türünde daha iyisini gördüm. Yine de {baslik} akşamı kurtaran bir seçenek.'],
        ['puan' => 9, 'metin' => 'Finali uzun süre aklımda kaldı. {baslik} hikâye anlatımı açısından gerçekten güçlü.'],
        ['puan' => 8, 'metin' => 'Tekrar izlemek için sabırsızlanıyorum. {baslik} detaylarıyla ikinci kez daha iyi geldi.'],
    ];

    $filmId = (int) ($film['id'] ?? 0);
    $adet   = 2 + ($filmId % 2);
    $temel  = strtotime((string) ($film['eklenme'] ?? '2026-10-01 09:00:00')) ?: time();
    $yorumlar = [];

    for ($i = 0; $i < $adet; $i++) {
        $sablon = $sablonlar[($filmId + $i) % count($sablonlar)];

        $metin = str_replace(
            ['{baslik}', '{kategori}', '{yonetmen}'],
            [
                (string) ($film['baslik'] ?? 'Film'),
                (string) ($film['kategori'] ?? 'Sinema'),
                (string) ($film['yonetmen'] ?? 'Yönetmen'),
            ],
            $sablon['metin']
        );

        $puan = max(6, min(10, (int) $sablon['puan'] + (($filmId + $i) % 3) - 1));

        $yorumlar[] = [
            'id'    => yeniYorumId(),
            'ad'    => $isimler[($filmId + ($i * 2)) % count($isimler)],
            'yorum' => $metin,
            'puan'  => $puan,
            'tarih' => date('Y-m-d H:i:s', $temel + (($i + 1) * 172800)),
            'demo'  => true,
        ];
    }

    return $yorumlar;
}

function yorumlariBaslat(): void
{
    if (!isset($_SESSION['yorumlar']) || !is_array($_SESSION['yorumlar'])) {
        $_SESSION['yorumlar'] = [];
    }

    if (!isset($_SESSION['yorum_son_id'])) {
        $_SESSION['yorum_son_id'] = 0;
    }

    $gecerliIdler = [];

    foreach ($_SESSION['filmler'] as $film) {
        $filmId = (int) ($film['id'] ?? 0);
        $gecerliIdler[] = $filmId;

        if (!array_key_exists($filmId, $_SESSION['yorumlar']) || !is_array($_SESSION['yorumlar'][$filmId])) {
            $_SESSION['yorumlar'][$filmId] = empty($film['ozel'])
                ? varsayilanFilmYorumlari($film)
                : [];
        }
    }

    foreach (array_keys($_SESSION['yorumlar']) as $filmId) {
        if (!in_array((int) $filmId, $gecerliIdler, true)) {
            unset($_SESSION['yorumlar'][$filmId]);
        }
    }

    $enBuyuk = 0;

    foreach ($_SESSION['yorumlar'] as $yorumlar) {
        foreach ($yorumlar as $yorum) {
            $enBuyuk = max($enBuyuk, (int) ($yorum['id'] ?? 0));
        }
    }

    $_SESSION['yorum_son_id'] = max((int) $_SESSION['yorum_son_id'], $enBuyuk);
}

function filmYorumlari(int $filmId): array
{
    $yorumlar = $_SESSION['yorumlar'][$filmId] ?? [];

    if (!is_array($yorumlar)) {
        return [];
    }

    usort($yorumlar, static function (array $a, array $b): int {
        $tarih = strcmp((string) ($b['tarih'] ?? ''), (string) ($a['tarih'] ?? ''));

        return $tarih !== 0 ? $tarih : ((int) ($b['id'] ?? 0) <=> (int) ($a['id'] ?? 0));
    });

    return $yorumlar;
}

function yorumSayisi(int $filmId): int
{
    return count($_SESSION['yorumlar'][$filmId] ?? []);
}

/**
 * Bir filmdeki yorumların ortalama puanını hesaplar. Puan bırakmayan
 * yorumlar ortalamaya dahil edilmez.
 */
function yorumOrtalamasi(array $yorumlar): float
{
    $toplam = 0.0;
    $adet   = 0;

    foreach ($yorumlar as $yorum) {
        if (isset($yorum['puan']) && is_numeric($yorum['puan'])) {
            $toplam += (float) $yorum['puan'];
            $adet++;
        }
    }

    return $adet > 0 ? round($toplam / $adet, 1) : 0.0;
}

/**
 * Film kaydındaki IMDb puanını güvenli biçimde döndürür.
 * Yeni eklenen kayıtlarda alan boşsa kişisel puan kullanılır.
 */
function filmImdbPuan(array $film): float
{
    $imdb = $film['imdb'] ?? null;

    if (!is_numeric($imdb)) {
        $imdb = $film['puan'] ?? 0;
    }

    return round((float) $imdb, 1);
}

/**
 * Bir metnin ilk harflerinden avatar oluşturmak için kısa yardımcı.
 */
function isimBasHarfleri(string $isim): string
{
    $parcalar = preg_split('/\s+/u', trim($isim)) ?: [];
    $harfler  = '';

    foreach (array_slice($parcalar, 0, 2) as $parca) {
        if ($parca !== '') {
            $harfler .= function_exists('mb_substr')
                ? mb_substr($parca, 0, 1, 'UTF-8')
                : substr($parca, 0, 1);
        }
    }

    $harfler = $harfler !== '' ? $harfler : '?';

    return function_exists('mb_strtoupper')
        ? mb_strtoupper($harfler, 'UTF-8')
        : strtoupper($harfler);
}

/**
 * Yorum formunu doğrular ve yorumu oturuma ekler.
 */
function yorumEkle(int $filmId, array $girdi): array
{
    $ad    = trim((string) ($girdi['ad'] ?? ''));
    $yorum = trim((string) ($girdi['yorum'] ?? ''));
    $puan  = str_replace(',', '.', trim((string) ($girdi['puan'] ?? '')));

    $hatalar = [];

    if ($ad === '') {
        $hatalar['ad'] = 'Görünen ad zorunludur.';
    } elseif (metinUzunlugu($ad) < 2) {
        $hatalar['ad'] = 'Ad en az 2 karakter olmalıdır.';
    } elseif (metinUzunlugu($ad) > 40) {
        $hatalar['ad'] = 'Ad en fazla 40 karakter olabilir.';
    }

    if ($yorum === '') {
        $hatalar['yorum'] = 'Yorum alanı zorunludur.';
    } elseif (metinUzunlugu($yorum) < 5) {
        $hatalar['yorum'] = 'Yorum en az 5 karakter olmalıdır.';
    } elseif (metinUzunlugu($yorum) > 600) {
        $hatalar['yorum'] = 'Yorum en fazla 600 karakter olabilir.';
    }

    if ($puan !== '') {
        if (!is_numeric($puan) || (float) $puan < 1 || (float) $puan > 10) {
            $hatalar['puan'] = 'Yorum puanı 1 ile 10 arasında olmalıdır.';
        }
    }

    if (filmBul($_SESSION['filmler'], $filmId) === null) {
        $hatalar['genel'] = 'Yorum yapılacak film bulunamadı.';
    }

    if ($hatalar !== []) {
        return ['ok' => false, 'hatalar' => $hatalar];
    }

    if (!isset($_SESSION['yorumlar'][$filmId]) || !is_array($_SESSION['yorumlar'][$filmId])) {
        $_SESSION['yorumlar'][$filmId] = [];
    }

    $_SESSION['yorumlar'][$filmId][] = [
        'id'    => yeniYorumId(),
        'ad'    => $ad,
        'yorum' => $yorum,
        'puan'  => $puan !== '' ? (float) $puan : null,
        'tarih' => date('Y-m-d H:i:s'),
        'demo'  => false,
    ];

    return ['ok' => true, 'hatalar' => []];
}

/* --------------------------------------------------------------------------
 * 4. KAYIT İŞLEMLERİ (CRUD)
 * ----------------------------------------------------------------------- */

function filmBul(array $filmler, int $id): ?array
{
    foreach ($filmler as $film) {
        if ((int) ($film['id'] ?? 0) === $id) {
            return $film;
        }
    }

    return null;
}

function filmIndeksi(array $filmler, int $id): ?int
{
    foreach ($filmler as $index => $film) {
        if ((int) ($film['id'] ?? 0) === $id) {
            return $index;
        }
    }

    return null;
}

/**
 * Arşivden rastgele bir film seçer. "Rastgele Film" kısayolu için kullanılır.
 */
function rastgeleFilm(array $filmler): ?array
{
    if ($filmler === []) {
        return null;
    }

    return $filmler[array_rand($filmler)];
}

/**
 * Yeni kaydı oturuma ekler ve yeni kaydın ID'sini döndürür.
 */
function filmEkle(array $veri): int
{
    $id = ((int) ($_SESSION['son_id'] ?? 0)) + 1;
    $_SESSION['son_id'] = $id;

    $aciklama = (string) $veri['aciklama'];
    $konu     = trim((string) ($veri['konu'] ?? ''));

    $_SESSION['filmler'][] = [
        'id'        => $id,
        'baslik'    => (string) $veri['baslik'],
        'kategori'  => (string) $veri['kategori'],
        'yonetmen'  => (string) $veri['yonetmen'],
        'yil'       => (int) $veri['yil'],
        'puan'      => (float) $veri['puan'],
        'imdb'      => (float) ($veri['imdb'] ?? VARSAYILAN_IMDB),
        'aciklama'  => $aciklama,
        'konu'      => $konu !== '' ? $konu : $aciklama,
        'durum'     => (string) $veri['durum'],
        'poster'    => (string) ($veri['poster'] ?? ''),
        'sure'      => (int) ($veri['sure'] ?? 0),
        'ulke'      => (string) ($veri['ulke'] ?? ''),
        'etiketler' => (string) ($veri['etiketler'] ?? ''),
        'eklenme'   => date('Y-m-d H:i:s'),
        'ozel'      => true,
    ];

    $_SESSION['yorumlar'][$id] = [];

    return $id;
}

/**
 * Var olan kaydı günceller. Kayıt bulunamazsa false döner.
 */
function filmGuncelle(int $id, array $veri): bool
{
    $index = filmIndeksi($_SESSION['filmler'], $id);

    if ($index === null) {
        return false;
    }

    $eski = $_SESSION['filmler'][$index];
    $aciklama = (string) $veri['aciklama'];
    $konu     = trim((string) ($veri['konu'] ?? ''));

    $_SESSION['filmler'][$index] = [
        'id'        => $id,
        'baslik'    => (string) $veri['baslik'],
        'kategori'  => (string) $veri['kategori'],
        'yonetmen'  => (string) $veri['yonetmen'],
        'yil'       => (int) $veri['yil'],
        'puan'      => (float) $veri['puan'],
        'imdb'      => (float) ($veri['imdb'] ?? ($eski['imdb'] ?? VARSAYILAN_IMDB)),
        'aciklama'  => $aciklama,
        'konu'      => $konu !== '' ? $konu : $aciklama,
        'durum'     => (string) $veri['durum'],
        'poster'    => (string) ($veri['poster'] ?? ''),
        'sure'      => (int) ($veri['sure'] ?? ($eski['sure'] ?? 0)),
        'ulke'      => (string) ($veri['ulke'] ?? ($eski['ulke'] ?? '')),
        'etiketler' => (string) ($veri['etiketler'] ?? ($eski['etiketler'] ?? '')),
        'eklenme'   => (string) ($eski['eklenme'] ?? date('Y-m-d H:i:s')),
        'ozel'      => (bool) ($eski['ozel'] ?? false),
    ];

    return true;
}

/**
 * Kaydı oturum dizisinden ve yorumlarından siler.
 */
function filmSil(int $id): bool
{
    $index = filmIndeksi($_SESSION['filmler'], $id);

    if ($index === null) {
        return false;
    }

    $poster = (string) ($_SESSION['filmler'][$index]['poster'] ?? '');

    array_splice($_SESSION['filmler'], $index, 1);

    if (isset($_SESSION['yorumlar'][$id])) {
        unset($_SESSION['yorumlar'][$id]);
    }

    if (isset($_SESSION['favoriler']) && is_array($_SESSION['favoriler'])) {
        $_SESSION['favoriler'] = array_values(array_filter(
            $_SESSION['favoriler'],
            static fn ($favoriId): bool => (int) $favoriId !== $id
        ));
    }

    posterDosyasiniSil($poster);

    return true;
}

/**
 * Durumu sırayla değiştirir: İzlenecek -> İzleniyor -> İzlendi -> İzlenecek
 */
function filmDurumDegistir(int $id): ?string
{
    $index = filmIndeksi($_SESSION['filmler'], $id);

    if ($index === null) {
        return null;
    }

    $sira   = ['İzlenecek', 'İzleniyor', 'İzlendi'];
    $mevcut = (string) ($_SESSION['filmler'][$index]['durum'] ?? 'İzlenecek');
    $konum  = array_search($mevcut, $sira, true);
    $yeni   = $sira[(($konum === false ? 0 : $konum) + 1) % count($sira)];

    $_SESSION['filmler'][$index]['durum'] = $yeni;

    return $yeni;
}

/**
 * Detay sayfasındaki yıldızlı puanlama formunu kaydeder.
 */
function filmPuanla(int $id, float $puan): bool
{
    if ($puan < 1 || $puan > 10) {
        return false;
    }

    $index = filmIndeksi($_SESSION['filmler'], $id);

    if ($index === null) {
        return false;
    }

    $_SESSION['filmler'][$index]['puan'] = round($puan, 1);

    return true;
}

/* --------------------------------------------------------------------------
 * 5. LİSTELEME, ARAMA, FİLTRELEME VE İSTATİSTİK
 * ----------------------------------------------------------------------- */

/**
 * Kayıtları arama metni, kategori, durum ve sıralama ölçütüne göre süzer.
 */
function filmleriGetir(
    array $filmler,
    string $arama = '',
    string $kategori = '',
    string $durum = '',
    string $sirala = 'yeni',
    array $ekFiltreler = []
): array {
    $aranan       = kucukHarf(trim($arama));
    $yilMin       = (int) ($ekFiltreler['yil_min'] ?? 0);
    $yilMax       = (int) ($ekFiltreler['yil_max'] ?? 0);
    $minPuan      = (float) ($ekFiltreler['min_puan'] ?? 0);
    $minImdb      = (float) ($ekFiltreler['min_imdb'] ?? 0);
    $sadeceFavori = !empty($ekFiltreler['sadece_favoriler']);
    $sadeceYorum  = !empty($ekFiltreler['sadece_yorumlu']);
    $favoriler    = is_array($ekFiltreler['favoriler'] ?? null)
        ? array_map('intval', $ekFiltreler['favoriler'])
        : [];

    $filtreli = array_values(array_filter(
        $filmler,
        static function (array $film) use (
            $aranan,
            $kategori,
            $durum,
            $yilMin,
            $yilMax,
            $minPuan,
            $minImdb,
            $sadeceFavori,
            $sadeceYorum,
            $favoriler
        ): bool {
            if ($kategori !== '' && (string) ($film['kategori'] ?? '') !== $kategori) {
                return false;
            }

            if ($durum !== '' && (string) ($film['durum'] ?? '') !== $durum) {
                return false;
            }

            $yil = (int) ($film['yil'] ?? 0);

            if ($yilMin > 0 && $yil < $yilMin) {
                return false;
            }

            if ($yilMax > 0 && $yil > $yilMax) {
                return false;
            }

            if ($minPuan > 0 && (float) ($film['puan'] ?? 0) < $minPuan) {
                return false;
            }

            if ($minImdb > 0 && filmImdbPuan($film) < $minImdb) {
                return false;
            }

            if ($sadeceFavori && !in_array((int) ($film['id'] ?? 0), $favoriler, true)) {
                return false;
            }

            if ($sadeceYorum && yorumSayisi((int) ($film['id'] ?? 0)) === 0) {
                return false;
            }

            if ($aranan !== '') {
                $hedef = kucukHarf(implode(' ', [
                    (string) ($film['baslik'] ?? ''),
                    (string) ($film['kategori'] ?? ''),
                    (string) ($film['yonetmen'] ?? ''),
                    (string) ($film['aciklama'] ?? ''),
                    (string) ($film['konu'] ?? ''),
                    (string) ($film['yil'] ?? ''),
                    (string) ($film['imdb'] ?? ''),
                    (string) ($film['ulke'] ?? ''),
                    (string) ($film['etiketler'] ?? ''),
                ]));

                if (strpos($hedef, $aranan) === false) {
                    return false;
                }
            }

            return true;
        }
    ));

    usort($filtreli, static function (array $a, array $b) use ($sirala): int {
        return match ($sirala) {
            'eski'   => ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0)),
            'baslik' => strcmp(kucukHarf((string) ($a['baslik'] ?? '')), kucukHarf((string) ($b['baslik'] ?? ''))),
            'puan'   => ((float) ($b['puan'] ?? 0)) <=> ((float) ($a['puan'] ?? 0)),
            'imdb'   => ((float) ($b['imdb'] ?? 0)) <=> ((float) ($a['imdb'] ?? 0)),
            'yil'    => ((int) ($b['yil'] ?? 0)) <=> ((int) ($a['yil'] ?? 0)),
            'sure'   => ((int) ($b['sure'] ?? 0)) <=> ((int) ($a['sure'] ?? 0)),
            'yorum'  => yorumSayisi((int) ($b['id'] ?? 0)) <=> yorumSayisi((int) ($a['id'] ?? 0)),
            default  => ((int) ($b['id'] ?? 0)) <=> ((int) ($a['id'] ?? 0)),
        };
    });

    return $filtreli;
}

/**
 * Özet sayaçları hesaplar: toplam, izlenen, izlenecek, izleniyor, ortalama puan,
 * ortalama IMDb puanı ve toplam yorum sayısı.
 */
function filmSayilari(array $filmler): array
{
    $sayilar = [
        'toplam'        => count($filmler),
        'izlendi'       => 0,
        'izlenecek'     => 0,
        'izleniyor'     => 0,
        'ortalama'      => 0.0,
        'imdb_ortalama' => 0.0,
        'kategori'      => count(kategorileriGetir($filmler)),
        'yorum'         => 0,
    ];

    $puanToplam = 0.0;
    $imdbToplam = 0.0;

    foreach ($filmler as $film) {
        $durum = (string) ($film['durum'] ?? '');

        if ($durum === 'İzlendi') {
            $sayilar['izlendi']++;
        } elseif ($durum === 'İzlenecek') {
            $sayilar['izlenecek']++;
        } elseif ($durum === 'İzleniyor') {
            $sayilar['izleniyor']++;
        }

        $puanToplam += (float) ($film['puan'] ?? 0);
        $imdbToplam += (float) ($film['imdb'] ?? 0);
        $sayilar['yorum'] += yorumSayisi((int) ($film['id'] ?? 0));
    }

    if ($sayilar['toplam'] > 0) {
        $sayilar['ortalama']      = round($puanToplam / $sayilar['toplam'], 1);
        $sayilar['imdb_ortalama'] = round($imdbToplam / $sayilar['toplam'], 1);
    }

    return $sayilar;
}

/**
 * Kayıtlardaki benzersiz kategorileri alfabetik olarak döndürür.
 */
function kategorileriGetir(array $filmler): array
{
    $kategoriler = [];

    foreach ($filmler as $film) {
        $kategori = trim((string) ($film['kategori'] ?? ''));

        if ($kategori !== '') {
            $kategoriler[$kategori] = true;
        }
    }

    $liste = array_keys($kategoriler);

    usort($liste, static fn (string $a, string $b): int => strcmp(kucukHarf($a), kucukHarf($b)));

    return $liste;
}

/**
 * İzlenen ve izlenmekte olan filmlerin türlerini, verilen puanlara göre
 * ağırlıklandırır. Öneri motorunun temelini bu ağırlıklar oluşturur.
 */
function izlenenTurAgirliklari(array $filmler): array
{
    $agirliklar = [];

    foreach ($filmler as $film) {
        $durum = (string) ($film['durum'] ?? '');

        if ($durum !== 'İzlendi' && $durum !== 'İzleniyor') {
            continue;
        }

        $carpan = $durum === 'İzlendi' ? 1.0 : 0.55;
        $puan   = (float) ($film['puan'] ?? 0);
        $etki   = max(0.5, ($puan - 4) / 6) * $carpan;

        foreach (filmTurleri((string) ($film['kategori'] ?? '')) as $tur) {
            $agirliklar[$tur] = ($agirliklar[$tur] ?? 0) + $etki;
        }
    }

    arsort($agirliklar);

    return $agirliklar;
}

/**
 * Kullanıcının en çok izlediği türleri döndürür.
 */
function ilgiAlanlari(array $filmler, int $limit = 4): array
{
    $agirliklar = izlenenTurAgirliklari($filmler);

    return array_slice(array_keys($agirliklar), 0, max(1, $limit));
}

/**
 * İzleme geçmişine göre yeni film önerileri üretir.
 * Yeni eklenen filmler de aynı fonksiyondan otomatik olarak geçer.
 */
function filmOnerileri(array $filmler, int $limit = 4, ?int $haricId = null): array
{
    $agirliklar = izlenenTurAgirliklari($filmler);
    $adaylar    = [];

    foreach ($filmler as $film) {
        $id = (int) ($film['id'] ?? 0);

        if ($haricId !== null && $id === $haricId) {
            continue;
        }

        $durum = (string) ($film['durum'] ?? '');

        if ($durum === 'İzlendi' || $durum === 'İzleniyor') {
            continue;
        }

        $skor = 0.0;

        foreach (filmTurleri((string) ($film['kategori'] ?? '')) as $tur) {
            if (isset($agirliklar[$tur])) {
                $skor += $agirliklar[$tur] * 6.0;
            }
        }

        $skor += ((float) ($film['imdb'] ?? 0)) * 0.7;
        $skor += ((float) ($film['puan'] ?? 0)) * 0.5;

        if ($durum === 'İzlenecek') {
            $skor += 0.4;
        }

        $film['_skor'] = round($skor, 3);
        $adaylar[] = $film;
    }

    if ($adaylar === []) {
        return [];
    }

    usort($adaylar, static function (array $a, array $b): int {
        $skor = ((float) ($b['_skor'] ?? 0)) <=> ((float) ($a['_skor'] ?? 0));

        return $skor !== 0 ? $skor : ((float) ($b['imdb'] ?? 0) <=> ((float) ($a['imdb'] ?? 0)));
    });

    return array_slice($adaylar, 0, max(1, $limit));
}

/**
 * Detay sayfası için aynı türleri paylaşan, yüksek puanlı kayıtları seçer.
 */
function benzerFilmler(array $filmler, int $id, int $limit = 3): array
{
    $hedef = filmBul($filmler, $id);

    if ($hedef === null) {
        return [];
    }

    $hedefTurler = array_map('kucukHarf', filmTurleri((string) ($hedef['kategori'] ?? '')));
    $adaylar     = [];

    foreach ($filmler as $film) {
        if ((int) ($film['id'] ?? 0) === $id) {
            continue;
        }

        $turler = array_map('kucukHarf', filmTurleri((string) ($film['kategori'] ?? '')));
        $ortak  = count(array_intersect($hedefTurler, $turler));

        $film['_benzerlik'] = ($ortak * 10)
            + ((float) ($film['imdb'] ?? 0) * 0.5)
            + ((float) ($film['puan'] ?? 0) * 0.3);

        $adaylar[] = $film;
    }

    usort($adaylar, static function (array $a, array $b): int {
        $benzerlik = ((float) ($b['_benzerlik'] ?? 0)) <=> ((float) ($a['_benzerlik'] ?? 0));

        return $benzerlik !== 0 ? $benzerlik : ((float) ($b['imdb'] ?? 0) <=> ((float) ($a['imdb'] ?? 0)));
    });

    return array_slice($adaylar, 0, max(1, $limit));
}

/* --------------------------------------------------------------------------
 * 5b. AFİŞ YÜKLEME
 * ----------------------------------------------------------------------- */

/**
 * Yalnızca bu uygulamanın uploads klasöründeki afişleri siler.
 * Dış URL'lere dokunmaz.
 */
function posterDosyasiniSil(?string $yol): void
{
    $yol = trim((string) $yol);

    if ($yol === '' || !str_starts_with($yol, POSTER_URL_ONEKI)) {
        return;
    }

    $dosya = basename($yol);
    $tamYol = POSTER_KLASORU . DIRECTORY_SEPARATOR . $dosya;

    if (is_file($tamYol)) {
        @unlink($tamYol);
    }
}

/**
 * Formdan gelen afiş dosyasını doğrular ve uploads klasörüne kaydeder.
 *
 * @return array{yol: string, hata: string}
 */
function posterYukle(array $dosya): array
{
    $hataKodu = (int) ($dosya['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($hataKodu === UPLOAD_ERR_NO_FILE) {
        return ['yol' => '', 'hata' => ''];
    }

    if ($hataKodu !== UPLOAD_ERR_OK) {
        $mesaj = match ($hataKodu) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Afiş dosyası sunucu sınırından büyük.',
            UPLOAD_ERR_PARTIAL => 'Afiş dosyası yarım yüklendi, lütfen tekrar deneyin.',
            default => 'Afiş yüklenemedi. Lütfen tekrar deneyin.',
        };

        return ['yol' => '', 'hata' => $mesaj];
    }

    if ((int) ($dosya['size'] ?? 0) > POSTER_MAKS_BOYUT) {
        return ['yol' => '', 'hata' => 'Afiş dosyası en fazla 2 MB olabilir.'];
    }

    $gecici = (string) ($dosya['tmp_name'] ?? '');

    if ($gecici === '' || !is_uploaded_file($gecici)) {
        return ['yol' => '', 'hata' => 'Afiş dosyası doğrulanamadı.'];
    }

    $bilgi = @getimagesize($gecici);

    if ($bilgi === false) {
        return ['yol' => '', 'hata' => 'Seçilen dosya geçerli bir görsel değil.'];
    }

    $izinli = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    $mime = (string) ($bilgi['mime'] ?? '');

    if (!isset($izinli[$mime])) {
        return ['yol' => '', 'hata' => 'Afiş JPG, PNG, WEBP veya GIF olmalıdır.'];
    }

    $genislik = (int) ($bilgi[0] ?? 0);
    $yukseklik = (int) ($bilgi[1] ?? 0);

    if ($genislik < 80 || $yukseklik < 80 || $genislik > 8000 || $yukseklik > 8000) {
        return ['yol' => '', 'hata' => 'Afiş boyutları 80×80 ile 8000×8000 arasında olmalıdır.'];
    }

    if (!is_dir(POSTER_KLASORU) && !@mkdir(POSTER_KLASORU, 0775, true) && !is_dir(POSTER_KLASORU)) {
        return ['yol' => '', 'hata' => 'Afiş klasörü oluşturulamadı.'];
    }

    $dosyaAdi = 'afis-' . date('Ymd') . '-' . bin2hex(random_bytes(8)) . '.' . $izinli[$mime];
    $hedef    = POSTER_KLASORU . DIRECTORY_SEPARATOR . $dosyaAdi;

    if (!move_uploaded_file($gecici, $hedef)) {
        return ['yol' => '', 'hata' => 'Afiş kaydedilemedi. Klasör izinlerini kontrol edin.'];
    }

    @chmod($hedef, 0644);

    return ['yol' => POSTER_URL_ONEKI . $dosyaAdi, 'hata' => ''];
}

/* --------------------------------------------------------------------------
 * 5c. İSTATİSTİK VE GRAFİK VERİLERİ
 * ----------------------------------------------------------------------- */

/**
 * Türlerin kayıt sayısına göre dağılımı.
 *
 * @return array<string, int>
 */
function turDagilimi(array $filmler, int $limit = 8): array
{
    $sayac = [];

    foreach ($filmler as $film) {
        foreach (filmTurleri((string) ($film['kategori'] ?? '')) as $tur) {
            $sayac[$tur] = ($sayac[$tur] ?? 0) + 1;
        }
    }

    arsort($sayac);

    return array_slice($sayac, 0, max(1, $limit), true);
}

/**
 * Filmlerin on yıllara göre dağılımı.
 *
 * @return array<string, int>
 */
function onYilDagilimi(array $filmler): array
{
    $sayac = [];

    foreach ($filmler as $film) {
        $yil = (int) ($film['yil'] ?? 0);

        if ($yil < 1900) {
            continue;
        }

        $onYil = intdiv($yil, 10) * 10;
        $anahtar = $onYil . "'lar";
        $sayac[$anahtar] = ($sayac[$anahtar] ?? 0) + 1;
    }

    ksort($sayac, SORT_NUMERIC);

    return $sayac;
}

/**
 * Kişisel puanların 2'şerlik dilimlere göre dağılımı.
 *
 * @return array<string, int>
 */
function puanDagilimi(array $filmler): array
{
    $dilimler = [
        '1–2' => 0,
        '3–4' => 0,
        '5–6' => 0,
        '7–8' => 0,
        '9–10' => 0,
    ];

    foreach ($filmler as $film) {
        $puan = (float) ($film['puan'] ?? 0);

        if ($puan <= 2) {
            $dilimler['1–2']++;
        } elseif ($puan <= 4) {
            $dilimler['3–4']++;
        } elseif ($puan <= 6) {
            $dilimler['5–6']++;
        } elseif ($puan <= 8) {
            $dilimler['7–8']++;
        } else {
            $dilimler['9–10']++;
        }
    }

    return $dilimler;
}

/**
 * Belirtilen alana göre en yüksek puanlı filmleri döndürür.
 */
function enIyiFilmler(array $filmler, int $limit = 5, string $alan = 'imdb'): array
{
    usort($filmler, static function (array $a, array $b) use ($alan): int {
        $sol = $alan === 'puan' ? (float) ($a['puan'] ?? 0) : (float) ($a['imdb'] ?? 0);
        $sag = $alan === 'puan' ? (float) ($b['puan'] ?? 0) : (float) ($b['imdb'] ?? 0);

        return $sag <=> $sol;
    });

    return array_slice($filmler, 0, max(1, $limit));
}

/**
 * En çok yorum alan filmleri döndürür.
 */
function enCokYorumlananlar(array $filmler, int $limit = 5): array
{
    usort($filmler, static function (array $a, array $b): int {
        $sol = yorumSayisi((int) ($a['id'] ?? 0));
        $sag = yorumSayisi((int) ($b['id'] ?? 0));

        return $sag <=> $sol;
    });

    return array_slice($filmler, 0, max(1, $limit));
}

/**
 * Son eklenen filmleri döndürür.
 */
function sonEklenenler(array $filmler, int $limit = 5): array
{
    usort($filmler, static fn (array $a, array $b): int => strcmp(
        (string) ($b['eklenme'] ?? ''),
        (string) ($a['eklenme'] ?? '')
    ));

    return array_slice($filmler, 0, max(1, $limit));
}

/**
 * Grafik çubukları için yüzde hesaplar.
 */
function yuzdeHesapla(int|float $deger, int|float $toplam): float
{
    return $toplam > 0 ? round(($deger / $toplam) * 100, 1) : 0.0;
}

/* --------------------------------------------------------------------------
 * 5d. SAYFALAMA
 * ----------------------------------------------------------------------- */

/**
 * Adresteki sayfa numarasını güvenli biçimde okur.
 */
function sayfaNumarasi(): int
{
    return max(1, (int) ($_GET['sayfa'] ?? 1));
}

/**
 * Toplam kayıt, geçerli sayfa ve sayfa başına kayıt sayısına göre
 * dilim bilgilerini hesaplar.
 *
 * @return array{
 *     toplam: int, limit: int, sayfa: int, sayfa_sayisi: int,
 *     baslangic: int, bitis: int, onceki: int, sonraki: int
 * }
 */
function sayfalamaBilgisi(int $toplam, int $sayfa, int $limit = SAYFA_BASINA_KAYIT): array
{
    $limit      = max(1, $limit);
    $toplam     = max(0, $toplam);
    $sayfaSayisi = max(1, (int) ceil($toplam / $limit));
    $sayfa      = min(max(1, $sayfa), $sayfaSayisi);
    $baslangic  = ($sayfa - 1) * $limit;
    $bitis      = min($baslangic + $limit, $toplam);

    return [
        'toplam'       => $toplam,
        'limit'        => $limit,
        'sayfa'        => $sayfa,
        'sayfa_sayisi' => $sayfaSayisi,
        'baslangic'    => $baslangic,
        'bitis'        => $bitis,
        'onceki'       => max(1, $sayfa - 1),
        'sonraki'      => min($sayfaSayisi, $sayfa + 1),
    ];
}

/**
 * Sayfa bağlantıları için mevcut sorgu parametrelerini koruyarak URL üretir.
 */
function sorguUrl(string $dosya, array $parametreler = []): string
{
    $parametreler = array_filter(
        $parametreler,
        static fn ($deger): bool => $deger !== '' && $deger !== null && $deger !== false
    );

    return $dosya . ($parametreler !== [] ? '?' . http_build_query($parametreler) : '');
}

/* --------------------------------------------------------------------------
 * 6. VERİ DOĞRULAMA
 * ----------------------------------------------------------------------- */

/**
 * Formdan gelen veriyi temizler ve sunucu tarafında doğrular.
 * Geriye hem temizlenmiş veriyi hem de alan bazlı hata mesajlarını döndürür.
 */
function filmVerisiniDogrula(array $girdi): array
{
    $veri = [
        'baslik'    => trim((string) ($girdi['baslik'] ?? '')),
        'kategori'  => trim((string) ($girdi['kategori'] ?? '')),
        'yonetmen'  => trim((string) ($girdi['yonetmen'] ?? '')),
        'yil'       => trim((string) ($girdi['yil'] ?? '')),
        'puan'      => str_replace(',', '.', trim((string) ($girdi['puan'] ?? ''))),
        'imdb'      => str_replace(',', '.', trim((string) ($girdi['imdb'] ?? ''))),
        'aciklama'  => trim((string) ($girdi['aciklama'] ?? '')),
        'konu'      => trim((string) ($girdi['konu'] ?? '')),
        'durum'     => trim((string) ($girdi['durum'] ?? '')),
        'poster'    => trim((string) ($girdi['poster'] ?? '')),
        'sure'      => trim((string) ($girdi['sure'] ?? '')),
        'ulke'      => trim((string) ($girdi['ulke'] ?? '')),
        'etiketler' => trim((string) ($girdi['etiketler'] ?? '')),
    ];

    $hatalar = [];

    if ($veri['baslik'] === '') {
        $hatalar['baslik'] = 'Film veya dizi adı zorunludur.';
    } elseif (metinUzunlugu($veri['baslik']) < 2) {
        $hatalar['baslik'] = 'Film adı en az 2 karakter olmalıdır.';
    } elseif (metinUzunlugu($veri['baslik']) > 120) {
        $hatalar['baslik'] = 'Film adı en fazla 120 karakter olabilir.';
    }

    if ($veri['kategori'] === '') {
        $hatalar['kategori'] = 'Kategori alanı zorunludur.';
    } elseif (metinUzunlugu($veri['kategori']) > 60) {
        $hatalar['kategori'] = 'Kategori en fazla 60 karakter olabilir.';
    }

    if ($veri['yonetmen'] === '') {
        $hatalar['yonetmen'] = 'Yönetmen alanı zorunludur.';
    } elseif (metinUzunlugu($veri['yonetmen']) > 100) {
        $hatalar['yonetmen'] = 'Yönetmen adı en fazla 100 karakter olabilir.';
    }

    if ($veri['yil'] === '' || !preg_match('/^\d{4}$/', $veri['yil'])) {
        $hatalar['yil'] = 'Geçerli bir yayın yılı girin (örn. 2024).';
    } else {
        $yil = (int) $veri['yil'];

        if ($yil < 1888 || $yil > ((int) date('Y') + 2)) {
            $hatalar['yil'] = 'Yıl 1888 ile ' . ((int) date('Y') + 2) . ' arasında olmalıdır.';
        }
    }

    if ($veri['puan'] === '' || !is_numeric($veri['puan'])) {
        $hatalar['puan'] = 'Puan 1 ile 10 arasında bir sayı olmalıdır.';
    } else {
        $puan = (float) $veri['puan'];

        if ($puan < 1 || $puan > 10) {
            $hatalar['puan'] = 'Puan 1 ile 10 arasında olmalıdır.';
        }
    }

    if ($veri['imdb'] === '' || !is_numeric($veri['imdb'])) {
        $hatalar['imdb'] = 'IMDb puanı 0 ile 10 arasında bir sayı olmalıdır.';
    } else {
        $imdb = (float) $veri['imdb'];

        if ($imdb < 0 || $imdb > 10) {
            $hatalar['imdb'] = 'IMDb puanı 0 ile 10 arasında olmalıdır.';
        }
    }

    if ($veri['durum'] === '') {
        $hatalar['durum'] = 'İzlenme durumu seçilmelidir.';
    } elseif (!in_array($veri['durum'], DURUMLAR, true)) {
        $hatalar['durum'] = 'Geçersiz izlenme durumu seçildi.';
    }

    if ($veri['aciklama'] === '') {
        $hatalar['aciklama'] = 'Açıklama alanı zorunludur.';
    } elseif (metinUzunlugu($veri['aciklama']) < 10) {
        $hatalar['aciklama'] = 'Açıklama en az 10 karakter olmalıdır.';
    } elseif (metinUzunlugu($veri['aciklama']) > 1000) {
        $hatalar['aciklama'] = 'Açıklama en fazla 1000 karakter olabilir.';
    }

    if ($veri['konu'] !== '' && metinUzunlugu($veri['konu']) > 2000) {
        $hatalar['konu'] = 'Konu en fazla 2000 karakter olabilir.';
    }

    if ($veri['poster'] !== '') {
        if (str_starts_with($veri['poster'], POSTER_URL_ONEKI)) {
            if (!preg_match('/^afis-[A-Za-z0-9._-]+$/', basename($veri['poster']))) {
                $hatalar['poster'] = 'Geçersiz afiş yolu.';
            }
        } else {
            $sema = strtolower((string) parse_url($veri['poster'], PHP_URL_SCHEME));

            if (!filter_var($veri['poster'], FILTER_VALIDATE_URL) || !in_array($sema, ['http', 'https'], true)) {
                $hatalar['poster'] = 'Afiş adresi http:// veya https:// ile başlayan geçerli bir URL olmalıdır.';
            } elseif (metinUzunlugu($veri['poster']) > 500) {
                $hatalar['poster'] = 'Afiş adresi çok uzun.';
            }
        }
    }

    if ($veri['sure'] !== '') {
        if (!ctype_digit($veri['sure']) || (int) $veri['sure'] < 1 || (int) $veri['sure'] > 900) {
            $hatalar['sure'] = 'Süre 1 ile 900 dakika arasında olmalıdır.';
        }
    }

    if ($veri['ulke'] !== '' && metinUzunlugu($veri['ulke']) > 60) {
        $hatalar['ulke'] = 'Ülke en fazla 60 karakter olabilir.';
    }

    if ($veri['etiketler'] !== '') {
        $etiketler = etiketleriAyir($veri['etiketler']);

        if (count($etiketler) > 6) {
            $hatalar['etiketler'] = 'En fazla 6 etiket ekleyebilirsin.';
        } elseif (metinUzunlugu($veri['etiketler']) > 120) {
            $hatalar['etiketler'] = 'Etiketler en fazla 120 karakter olabilir.';
        } else {
            $veri['etiketler'] = implode(', ', $etiketler);
        }
    }

    if (!isset($hatalar['yil']) && $veri['yil'] !== '') {
        $veri['yil'] = (int) $veri['yil'];
    }

    if (!isset($hatalar['puan']) && $veri['puan'] !== '') {
        $veri['puan'] = (float) $veri['puan'];
    }

    if (!isset($hatalar['imdb']) && $veri['imdb'] !== '') {
        $veri['imdb'] = (float) $veri['imdb'];
    }

    if (!isset($hatalar['sure'])) {
        $veri['sure'] = $veri['sure'] !== '' ? (int) $veri['sure'] : 0;
    }

    return [
        'veri'    => $veri,
        'hatalar' => $hatalar,
    ];
}

/* --------------------------------------------------------------------------
 * 7. CSRF, FLASH MESAJ VE YÖNLENDİRME
 * ----------------------------------------------------------------------- */

/**
 * Formlarda kullanılacak tek kullanımlık oturum güvenlik anahtarı.
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrfDogrula(?string $token): bool
{
    return is_string($token)
        && $token !== ''
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * İşlem sonrası tek seferlik bildirim mesajı saklar.
 */
function flashAyarla(string $tip, string $mesaj): void
{
    $_SESSION['flash'] = [
        'tip'   => $tip,
        'mesaj' => $mesaj,
    ];
}

function flashGetir(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function yonlendir(string $adres): never
{
    header('Location: ' . $adres);
    exit;
}

/**
 * Formdan gelen dönüş adresini yalnızca uygulama içi sayfalara izin vererek temizler.
 * Açık yönlendirme (open redirect) saldırılarını engeller.
 */
function guvenliDonus(?string $adres, string $varsayilan = 'index.php'): string
{
    $adres = trim((string) $adres);

    if ($adres === '' || str_contains($adres, "\n") || str_contains($adres, "\r")) {
        return $varsayilan;
    }

    if (!preg_match('/^[A-Za-z0-9._-]+\.php(?:\?[A-Za-z0-9._%&=#+-]*)?$/', $adres)) {
        return $varsayilan;
    }

    return $adres;
}

/* --------------------------------------------------------------------------
 * 7b. GİRİŞ / OTURUM KİMLİĞİ
 * ----------------------------------------------------------------------- */

/**
 * Oturumda geçerli bir kullanıcı kimliği olup olmadığını döndürür.
 */
function girisYapildi(): bool
{
    return isset($_SESSION['kullanici'])
        && is_array($_SESSION['kullanici'])
        && !empty($_SESSION['kullanici']['kullanici_adi']);
}

/**
 * Giriş yapan kullanıcının bilgilerini döndürür.
 */
function aktifKullanici(): ?array
{
    return girisYapildi() ? $_SESSION['kullanici'] : null;
}

/**
 * Korumalı sayfaların başında çağrılır. Giriş yoksa kullanıcıyı
 * giriş ekranına yönlendirir ve gelmek istediği adresi hatırlar.
 */
function girisGerekli(): void
{
    if (girisYapildi()) {
        return;
    }

    $hedef = (string) ($_SERVER['REQUEST_URI'] ?? 'index.php');

    if (!str_starts_with($hedef, '/') || str_starts_with($hedef, '//')) {
        $hedef = 'index.php';
    }

    $_SESSION['giris_hedef'] = $hedef;
    flashAyarla('bilgi', 'Devam etmek için giriş yapmalısın.');

    yonlendir('giris.php');
}

/**
 * Demo hesap bilgilerini sunucu tarafında doğrular.
 * Başarılı girişte oturum kimliğini yeniler ve CSRF anahtarını döndürür.
 */
function girisDene(string $kullaniciAdi, string $sifre): bool
{
    $kullaniciAdi = trim($kullaniciAdi);

    if ($kullaniciAdi === '' || $sifre === '') {
        return false;
    }

    $kullaniciDogru = hash_equals(DEMO_KULLANICI, $kullaniciAdi);
    $sifreDogru     = password_verify($sifre, kullaniciSifreHash());

    if (!$kullaniciDogru || !$sifreDogru) {
        girisDenemesiKaydet();

        return false;
    }

    girisDenemesiSifirla();
    session_regenerate_id(true);

    $_SESSION['kullanici'] = [
        'kullanici_adi' => DEMO_KULLANICI,
        'ad'            => DEMO_GORUNEN_AD,
        'rol'           => 'Arşiv Yöneticisi',
        'giris_zamani'  => date('c'),
    ];

    /* Oturum yenilendiğinde eski CSRF anahtarını geçersiz kıl. */
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    unset($_SESSION['giris_hedef']);

    return true;
}

/**
 * Kullanıcı oturumunu kapatır. Film ve yorum verileri demo bütünlüğü için korunur.
 */
function cikisYap(): void
{
    unset($_SESSION['kullanici']);
    unset($_SESSION['giris_hedef']);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    session_regenerate_id(true);
}

/* --------------------------------------------------------------------------
 * 7c. GİRİŞ SINIRLAMA VE HESAP AYARLARI
 * ----------------------------------------------------------------------- */

const GIRIS_MAKS_DENEME = 5;
const GIRIS_KILIT_SURESI = 60;

/**
 * Kullanıcının güncel şifre özeti. Demo hesap için sabit hash,
 * şifre değiştirildiyse oturumdaki yeni hash kullanılır.
 */
function kullaniciSifreHash(): string
{
    $hash = (string) ($_SESSION['sifre_hash'] ?? '');

    return $hash !== '' ? $hash : DEMO_SIFRE_HASH;
}

/**
 * Başarısız giriş denemesini kaydeder. Beş denemeden sonra
 * kısa süreli bir bekleme (throttle) uygulanır.
 */
function girisDenemesiKaydet(): void
{
    $simdi = time();
    $denemeler = $_SESSION['giris_denemeleri'] ?? [];

    if (!is_array($denemeler)) {
        $denemeler = [];
    }

    /* Son 10 dakikadaki denemeleri tut. */
    $denemeler = array_values(array_filter(
        $denemeler,
        static fn ($zaman): bool => is_int($zaman) && ($simdi - $zaman) < 600
    ));

    $denemeler[] = $simdi;

    if (count($denemeler) >= GIRIS_MAKS_DENEME) {
        $_SESSION['giris_kilit'] = $simdi + GIRIS_KILIT_SURESI;
        $denemeler = [];
    }

    $_SESSION['giris_denemeleri'] = $denemeler;
}

/**
 * Başarılı girişten sonra deneme sayaçlarını temizler.
 */
function girisDenemesiSifirla(): void
{
    unset($_SESSION['giris_denemeleri'], $_SESSION['giris_kilit']);
}

/**
 * Kalan bekleme süresini saniye olarak döndürür. 0 ise giriş serbest.
 */
function girisBeklemeSuresi(): int
{
    $kilit = (int) ($_SESSION['giris_kilit'] ?? 0);

    if ($kilit <= time()) {
        unset($_SESSION['giris_kilit']);

        return 0;
    }

    return $kilit - time();
}

/**
 * Profil sayfasında kullanıcı adını günceller.
 */
function profilAdiGuncelle(string $ad): bool
{
    $ad = trim($ad);

    if (metinUzunlugu($ad) < 2 || metinUzunlugu($ad) > 40 || !girisYapildi()) {
        return false;
    }

    $_SESSION['kullanici']['ad'] = $ad;

    return true;
}

/**
 * Şifre değiştirme formunu doğrular ve oturumdaki hash'i günceller.
 *
 * @return array{ok: bool, hatalar: array<string, string>}
 */
function sifreDegistir(string $mevcut, string $yeni, string $tekrar): array
{
    $hatalar = [];

    if ($mevcut === '') {
        $hatalar['mevcut_sifre'] = 'Mevcut şifreni girmelisin.';
    } elseif (!password_verify($mevcut, kullaniciSifreHash())) {
        $hatalar['mevcut_sifre'] = 'Mevcut şifre hatalı.';
    }

    if (metinUzunlugu($yeni) < 8) {
        $hatalar['yeni_sifre'] = 'Yeni şifre en az 8 karakter olmalıdır.';
    } elseif (!preg_match('/[A-Za-zÇĞİÖŞÜçğıöşü]/u', $yeni) || !preg_match('/\d/', $yeni)) {
        $hatalar['yeni_sifre'] = 'Yeni şifre en az bir harf ve bir rakam içermelidir.';
    } elseif ($yeni === $mevcut) {
        $hatalar['yeni_sifre'] = 'Yeni şifre mevcut şifreyle aynı olamaz.';
    }

    if ($tekrar !== $yeni) {
        $hatalar['tekrar'] = 'Şifre tekrarı yeni şifreyle eşleşmiyor.';
    }

    if ($hatalar !== []) {
        return ['ok' => false, 'hatalar' => $hatalar];
    }

    $_SESSION['sifre_hash'] = password_hash($yeni, PASSWORD_DEFAULT);
    girisDenemesiSifirla();

    return ['ok' => true, 'hatalar' => []];
}

/* --------------------------------------------------------------------------
 * 8. OTURUMU HAZIRLA
 * ----------------------------------------------------------------------- */

filmVerisiniBaslat();
