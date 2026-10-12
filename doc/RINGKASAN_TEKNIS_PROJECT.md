# Ringkasan Teknis Project — Sistem Distribusi Yery Collection

> Dokumen ini disusun agar AI/asisten lain dapat memahami keseluruhan project tanpa membaca seluruh source code. Seluruh isi di bawah diverifikasi langsung terhadap source code aktual per Juli 2026 (migration, model, controller, request, service, seeder, route, view). Bagian yang tidak dapat diverifikasi dari kode (mis. metode SDLC) ditandai secara eksplisit.

---

# 1. Gambaran Umum Project

- **Nama project**: Sistem Distribusi Yery Collection — judul skripsi: "Implementasi Sistem Distribusi untuk Memprediksi Produk Terlaris dengan Algoritma C4.5 Berbasis Website pada Toko Yery Collection".
- **Tujuan sistem**: mendigitalisasi pencatatan distribusi barang keluar toko grosir celana anak, dan memprediksi produk terlaris menggunakan algoritma C4.5 berbasis data historis distribusi, sebagai dasar objektif keputusan restock.
- **Permasalahan yang diselesaikan**: pencatatan manual di buku (mudah hilang/rusak), rekapitulasi lambat, dan penentuan produk laris hanya berdasarkan ingatan/perkiraan pemilik toko tanpa dasar data.
- **Metode SDLC**: **tidak dapat diverifikasi dari source code** — ini keputusan metodologi penelitian, bukan artefak kode. Berdasarkan pernyataan pengguna, metode yang dipakai adalah **Waterfall**. Catatan pendukung (bukan bukti langsung): seluruh proses pengembangan sistem ini (bersama AI assistant) memang dijalankan secara berurutan dan bergerbang — analisis kebutuhan → ERD → UML → implementasi bertahap (4.1 migration/model/seeder → 4.2 route/controller/request → 4.3 view → 4.4 algoritma), masing-masing menunggu persetujuan eksplisit sebelum lanjut ke tahap berikutnya, tanpa pernah mundur mengubah tahap sebelumnya — pola ini konsisten dengan karakteristik Waterfall (sekuensial, tidak iteratif).
- **Algoritma yang digunakan**: C4.5 (decision tree), diimplementasikan manual dalam PHP (bukan library ML pihak ketiga), menggunakan Gain Ratio (bukan Information Gain biasa seperti ID3) sebagai kriteria pemilihan atribut split — inilah ciri pembeda C4.5 dari ID3.
- **Studi kasus penelitian**: Toko Yery Collection, usaha grosir celana anak, dengan data uji coba 15 produk dan 81 transaksi distribusi (periode Januari–Juni 2026, dibangkitkan lewat seeder, lihat Bagian 3 & 12).

---

# 2. Arsitektur Project

## Struktur Folder Penting
```
app/
├── Http/
│   ├── Controllers/          # 6 controller domain + Controller.php (base kosong) + Auth/ (8 controller bawaan Breeze)
│   └── Requests/             # 6 Form Request domain + Auth/LoginRequest.php
├── Models/                   # User, Product, Distribution (hanya 3 model, sesuai 3 tabel utama)
├── Services/                 # C45Classifier.php (satu-satunya service class)
└── View/Components/          # AppLayout.php, GuestLayout.php (Blade component class, bawaan Breeze)

database/
├── migrations/               # users, products, distributions + tabel infrastruktur Laravel (cache, jobs, sessions, password_reset_tokens)
├── seeders/                  # DatabaseSeeder, ProductSeeder, DistributionSeeder
└── factories/                # hanya UserFactory — TIDAK ADA ProductFactory/DistributionFactory (seeder menulis data langsung via array)

resources/views/
├── dashboard.blade.php, products/, distributions/, predictions/, reports/, profile/, auth/, layouts/, components/, vendor/pagination/

routes/
├── web.php                   # seluruh route domain (di dalam middleware auth) + redirect '/'
└── auth.php                  # seluruh route bawaan Breeze (login, register TIDAK ADA, forgot/reset password, verifikasi email, logout)
```

## Pola Arsitektur
MVC standar Laravel + satu **Service class** tambahan (`C45Classifier`) untuk mengisolasi logika algoritma dari Controller. **Tidak ada** Repository pattern, tidak ada Policy/Gate class, tidak ada API Resource/API layer (murni web/Blade, tidak ada endpoint JSON/API), tidak ada package admin panel (Filament dkk sengaja tidak dipakai).

## Alur Request Laravel (khusus project ini)
`routes/web.php` atau `routes/auth.php` → middleware `auth`/`guest` → Controller method → (kalau menulis data) Form Request untuk validasi → Model (Eloquent) untuk baca/tulis DB → `redirect()` (untuk POST/PUT/DELETE) atau `view()` (untuk GET) → Blade view dengan layout `layouts.app` (halaman terautentikasi) atau `layouts.guest` (halaman login/auth).

## Pembagian Controller / Model / Request / Service
- **Controller**: menerima request, memanggil Form Request (validasi otomatis lewat type-hint), memanggil Model langsung (tidak ada layer Repository), pada satu tempat (`PredictionController`) memanggil Service. Semua logika bisnis (termasuk transaksi DB) ditulis langsung di method Controller — **tidak ada** layer "business logic"/use-case class terpisah.
- **Model**: Eloquent murni, hanya berisi `$fillable`, `casts()`, dan method relasi. **Tidak ada** business logic (scope query custom, accessor/mutator, event listener) di dalam Model manapun.
- **Form Request**: satu-satunya tempat validasi input. Tidak ada validasi ditulis inline di Controller.
- **Service**: hanya `C45Classifier`, murni logika algoritma (tidak menyentuh DB sama sekali — menerima array PHP polos sebagai input, mengembalikan array PHP polos sebagai output).

---

# 3. Database

Sistem punya **3 tabel bisnis inti** (`users`, `products`, `distributions`) + tabel infrastruktur bawaan Laravel yang tidak disentuh aplikasi (`cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `sessions`, `password_reset_tokens`). **Tidak ada** tabel `categories`, `classification_runs`, `prediction_results`, atau `distribution_details` — ini keputusan desain eksplisit (lihat Bagian 16).

## Tabel `users`
- **Fungsi**: akun autentikasi Admin (satu-satunya actor sistem).
- **Kolom**: `id`, `name` (VARCHAR 255), `email` (VARCHAR 255, unique), `email_verified_at` (TIMESTAMP nullable, bawaan Breeze — **tidak dipakai fungsional**, tidak ada middleware `verified` yang mengecek kolom ini), `password` (VARCHAR 255, hashed), `remember_token` (VARCHAR 100 nullable), `created_at`, `updated_at`.
- **Primary Key**: `id` (BIGINT UNSIGNED auto-increment).
- **Foreign Key**: tidak ada.
- **Relasi**: **tidak ada relasi ke tabel lain** (lihat Bagian 16 untuk alasan).
- **Soft Delete**: tidak ada (`deleted_at` tidak ada di tabel ini) — `ProfileController::destroy()` melakukan **hard delete** (`$user->delete()` tanpa `SoftDeletes` trait di model `User`).
- **Index**: `email` unique index (otomatis dari `->unique()`).
- **Constraint**: `email` unique.

## Tabel `products`
- **Fungsi**: master data produk + tempat menyimpan hasil klasifikasi C4.5 terakhir.
- **Kolom**: `id`, `code` (VARCHAR, unique — **catatan**: migration menulis `$table->string('code')` tanpa parameter panjang, sehingga kolom ini sebenarnya **VARCHAR(255)**, bukan VARCHAR(50) seperti sempat tertulis di draf dokumentasi BAB III sebelumnya), `name` (VARCHAR 255 by default, alasan sama), `category` (VARCHAR 255, **string bebas, bukan foreign key** ke tabel kategori — memang tidak ada tabel `categories`), `size` (VARCHAR 255), `price` (DECIMAL(12,2)), `stock` (INTEGER UNSIGNED, default 0), `unit` (VARCHAR 255, default `'pcs'`), `predicted_label` (ENUM('Laris','Tidak Laris'), nullable — hasil klasifikasi C4.5, **hanya diisi lewat query builder `update()` di `PredictionController`, tidak pernah lewat form/mass assignment**), `last_classified_at` (TIMESTAMP nullable), `created_at`, `updated_at`, `deleted_at` (soft delete).
- **Primary Key**: `id`.
- **Foreign Key**: tidak ada (produk tidak merujuk tabel lain).
- **Relasi**: `hasMany(Distribution::class)` — satu produk punya banyak baris distribusi.
- **Soft Delete**: ada (`SoftDeletes` trait di Model `Product`). Alasan: agar riwayat distribusi produk yang dihapus tetap bisa ditampilkan (lihat Bagian 16).
- **Index**: `code` unique index.
- **Constraint**: `code` unique (di-scope hanya terhadap baris yang belum di-soft-delete, lewat `Rule::unique(...)->where(fn($q) => $q->whereNull('deleted_at'))` di Form Request — bukan di level DB).

## Tabel `distributions`
- **Fungsi**: riwayat setiap transaksi barang keluar per produk; menjadi sumber data latih C4.5.
- **Kolom**: `id`, `product_id` (BIGINT UNSIGNED, FK), `distribution_date` (DATE), `quantity` (INTEGER UNSIGNED), `notes` (TEXT nullable), `created_at`, `updated_at`, `deleted_at`.
- **Primary Key**: `id`.
- **Foreign Key**: `product_id` → `products.id`, dengan `->restrictOnDelete()` (constraint DB-level ini secara praktik tidak pernah terpicu karena penghapusan produk selalu soft delete, bukan hard delete — baris fisik produk tidak pernah benar-benar dihapus).
- **Relasi**: `belongsTo(Product::class)->withTrashed()` — sengaja pakai `withTrashed()` supaya distribusi lama tetap bisa menampilkan nama produknya meski produknya sudah di-soft-delete (tanpa ini, relasi akan `null` dan menyebabkan crash di halaman Distribusi/Laporan).
- **Soft Delete**: ada.
- **Index**: index otomatis pada `product_id` (dari `foreignId()->constrained()`).
- **Constraint**: `product_id` harus merujuk produk yang ada dan **belum di-soft-delete** (divalidasi di Form Request lewat `Rule::exists('products','id')->where(fn($q)=>$q->whereNull('deleted_at'))`, bukan constraint DB murni — DB-level FK tetap mengizinkan referensi ke produk yang di-soft-delete karena baris fisiknya masih ada).

---

# 4. Model

## `User` (`app/Models/User.php`)
- Extends `Illuminate\Foundation\Auth\User` (Authenticatable).
- **Fillable**: `name`, `email`, `password`.
- **Hidden**: `password`, `remember_token`.
- **Casts**: `email_verified_at` → datetime, `password` → hashed.
- **Trait**: `HasFactory`, `Notifiable`. Import `MustVerifyEmail` **di-comment-out** (baris 5) — class **tidak** implement interface `MustVerifyEmail`, artinya verifikasi email tidak benar-benar aktif secara kontrak meski route-nya ada.
- **Relasi**: tidak ada.
- **Fungsi**: representasi akun Admin untuk autentikasi saja.

## `Product` (`app/Models/Product.php`)
- Extends `Model`.
- **Fillable**: `code`, `name`, `category`, `size`, `price`, `stock`, `unit` — **sengaja tidak termasuk** `predicted_label`/`last_classified_at` (dua kolom ini hanya diisi sistem lewat query builder, bukan form).
- **Casts**: `price` → decimal:2, `stock` → integer, `last_classified_at` → datetime.
- **Trait**: `HasFactory`, `SoftDeletes`.
- **Relasi**: `distributions(): HasMany`.
- **Fungsi**: master data produk + penyimpan hasil klasifikasi terakhir.

## `Distribution` (`app/Models/Distribution.php`)
- Extends `Model`.
- **Fillable**: `product_id`, `distribution_date`, `quantity`, `notes`.
- **Casts**: `distribution_date` → date, `quantity` → integer.
- **Trait**: `HasFactory`, `SoftDeletes`.
- **Relasi**: `product(): BelongsTo` dengan `->withTrashed()`.
- **Fungsi**: catatan satu transaksi barang keluar.

---

# 5. Controller

## `DashboardController` (`app/Http/Controllers/DashboardController.php`)
- `index()`: menampilkan `dashboard.blade.php`. Menggunakan Model `Product` dan `Distribution` langsung (tanpa Service, tanpa Form Request — pure GET, tidak ada input dari user). Alur: hitung total produk, total distribusi, distribusi hari ini, agregasi 6 bulan terakhir untuk grafik, top 5 produk (by `withSum('distributions','quantity')`), 5 hasil prediksi terbaru, tren bulanan, rata-rata stok, rekomendasi restock, dan ringkasan analisis terakhir.
- `calculateMonthlyDistributionTrend()` (private): bandingkan jumlah transaksi bulan ini vs bulan lalu, hasil persentase.
- `buildLastAnalysisSummary()` (private): ambil produk yang sudah pernah diklasifikasi (`predicted_label` tidak null), kembalikan tanggal analisis terakhir, total produk, dan akurasi.
- `calculateAccuracy()` (private): **menghitung ulang akurasi model dari data saat ini** (bukan dari nilai tersimpan) memakai formula label yang sama seperti `PredictionController` (total kuantitas vs rata-rata) — ini logika yang **terduplikasi secara independen** dari `PredictionController::process()`, bukan dipanggil dari satu tempat bersama.

## `ProductController` (`app/Http/Controllers/ProductController.php`)
- `index(Request)`: list produk + pencarian (`code`/`name`/`category`/`size` like), pagination 10.
- `create()`: tampilkan form tambah.
- `store(StoreProductRequest)`: `Product::create($request->validated())`.
- `edit(Product)`: tampilkan form ubah (route model binding).
- `update(UpdateProductRequest, Product)`: `$product->update($request->validated())`.
- `destroy(Product)`: `$product->delete()` (soft delete).
- Tidak memakai Service. Model yang dipakai: `Product` saja.

## `DistributionController` (`app/Http/Controllers/DistributionController.php`)
- `index(Request)`: list distribusi + pencarian (nama/kategori produk via `whereHas`, atau tanggal via `parseSearchDate`), eager-load `product`, pagination 10.
- `parseSearchDate(string)` (private): parsing string pencarian jadi tanggal valid kalau polanya menyerupai tanggal.
- `create()`: tampilkan form tambah, daftar produk untuk dropdown.
- `store(StoreDistributionRequest)`: **dalam `DB::transaction`** — `Product::findOrFail`, `Distribution::create()`, lalu `$product->decrement('stock', $request->quantity)`.
- `edit(Distribution)`: eager-load `product` sebelum tampil form.
- `update(UpdateDistributionRequest, Distribution)`: **dalam `DB::transaction`** — kembalikan stok lama (`increment` sejumlah `$oldQuantity`), lalu kurangi sesuai jumlah baru (`decrement`).
- `destroy(Distribution)`: **dalam `DB::transaction`** — `increment` stok sejumlah quantity yang dihapus, lalu `$distribution->delete()`.
- Model yang dipakai: `Distribution` dan `Product` (untuk penyesuaian stok). Tidak memakai Service.

## `PredictionController` (`app/Http/Controllers/PredictionController.php`)
- `index()`: ambil semua produk (urut by `last_classified_at` desc), hitung `averageStock`, ambil `tree`/`accuracy`/`totalTraining`/threshold dari **cache aplikasi** (`Cache::get('c45_analysis')`, tersimpan di tabel `cache`), tampilkan ke view.
- `process()`: method inti penelitian. Ambil produk + `withCount('distributions')` + `withSum('distributions','quantity')`; kalau kosong → redirect dengan pesan error; hitung persentil ke-33/66 dari jumlah transaksi; hitung rata-rata total kuantitas; susun dataset (`category`, `size`, `frequency_level`, `label`); panggil `C45Classifier::buildTree()`; loop tiap baris dataset → `classify()` → `Product::whereKey()->update(['predicted_label'=>..., 'last_classified_at'=>now()])` (query builder, bukan mass assignment lewat `$fillable`); hitung akurasi; simpan ringkasan analisis (tree, akurasi, P33/P66, rata-rata kuantitas, level frekuensi, waktu analisis) dengan `Cache::forever('c45_analysis', ...)`; redirect dengan pesan sukses berisi angka akurasi. Cache ini dihapus otomatis (trait `InvalidatesPredictionCache`) setiap kali data Produk/Distribusi ditambah, diubah, atau dihapus.
- `frequencyLevel()` (private): diskritisasi jumlah transaksi jadi Rendah/Sedang/Tinggi berdasarkan persentil.
- `percentile()` (private): hitung persentil dengan interpolasi linear.
- Model: `Product`. Service: `C45Classifier` (satu-satunya controller yang memakai Service ini).

## `ReportController` (`app/Http/Controllers/ReportController.php`)
- `index(Request)`: ambil `from`/`to` dari query string (default: awal bulan ini s.d. hari ini; nilai yang bukan tanggal valid juga kembali ke default), `Distribution::with('product')->whereBetween('distribution_date',[from,to])->orderBy('distribution_date')->get()`, tampilkan ke view. Model: `Distribution` saja (tidak query `Product` langsung). Pencetakan laporan pakai fitur print bawaan browser (`window.print()` di sisi Blade/JS), **tidak ada** generate PDF di server.

## `ProfileController` (`app/Http/Controllers/ProfileController.php`) — **di luar domain riset, bawaan Breeze**
- `edit(Request)`: tampilkan form profil user yang sedang login.
- `update(ProfileUpdateRequest)`: `fill()` + `save()`, reset `email_verified_at` kalau email berubah.
- **Tidak ada** fitur hapus akun (method `destroy` dan route `DELETE /profile` bawaan Breeze sengaja dihapus): sistem hanya punya satu akun Admin tanpa halaman registrasi, jadi menghapusnya akan mengunci aplikasi.

## Controller `Auth/*` (8 file) — **bawaan Laravel Breeze, tidak dikustomisasi, di luar domain riset**
`AuthenticatedSessionController` (login/logout — dipakai & relevan riset), `ConfirmablePasswordController`, `EmailVerificationNotificationController`, `EmailVerificationPromptController`, `NewPasswordController`, `PasswordController`, `PasswordResetLinkController`, `VerifyEmailController` — seluruhnya kode default Breeze tanpa modifikasi. **Tidak ada** `RegisteredUserController`/route registrasi (sengaja dihapus — sistem single-admin, tidak ada pendaftaran akun baru).

---

# 6. Form Request

## `LoginRequest` (`app/Http/Requests/Auth/LoginRequest.php`)
- **Rules**: `email` (required, string, email), `password` (required, string).
- **Validasi tambahan**: `authenticate()` — cek rate limit (`ensureIsNotRateLimited()`, maksimal 5 kali gagal per kombinasi email+IP, pakai `RateLimiter` Laravel) sebelum `Auth::attempt()`; kalau gagal, `RateLimiter::hit()`; kalau sukses, `RateLimiter::clear()`.
- **Alasan**: keamanan brute-force login.

## `StoreProductRequest` / `UpdateProductRequest`
- **Rules**: `code` (required, string, max:50, unique — di-scope `whereNull('deleted_at')`, dan `UpdateProductRequest` tambah `->ignore($this->route('product'))`), `name` (required, max:150), `category` (required, max:100), `size` (required, max:20), `price` (required, numeric, min:0), `stock` (required, integer, min:0), `unit` (required, max:20).
- **Validasi tambahan**: tidak ada (`withValidator` tidak dipakai di kedua request ini).
- **Alasan**: memastikan kode produk tidak duplikat (kecuali dengan produk yang sudah di-soft-delete, supaya kode bisa dipakai ulang), dan seluruh field wajib terisi.

## `StoreDistributionRequest`
- **Rules**: `product_id` (required, exists di `products.id` yang belum di-soft-delete), `distribution_date` (required, date), `quantity` (required, integer, min:1), `notes` (nullable, string).
- **Validasi tambahan**: `withValidator()` — cek `$this->quantity > $product->stock`, tambah error kalau jumlah melebihi stok tersedia.
- **Alasan**: mencegah distribusi melebihi stok fisik yang ada.

## `UpdateDistributionRequest`
- **Rules**: `distribution_date` (required, date), `quantity` (required, integer, min:1), `notes` (nullable, string). **Tidak ada field `product_id`** — produk sengaja tidak bisa diganti saat edit (disebutkan eksplisit di komentar kode) supaya perhitungan penyesuaian stok tidak ambigu.
- **Validasi tambahan**: `withValidator()` — hitung `$availableStock = $distribution->product->stock + $distribution->quantity` (stok saat ini + jumlah lama yang belum "dikembalikan"), lalu cek `$this->quantity > $availableStock`.
- **Alasan**: sama seperti Store, tapi memperhitungkan bahwa jumlah lama masih "menyandera" sebagian stok.

## `ProfileUpdateRequest` — di luar domain riset
- **Rules**: `name` (required, max:255), `email` (required, email, max:255, unique di luar user sendiri).
- **Alasan**: bawaan Breeze, tidak dimodifikasi.

---

# 7. Service

Hanya ada **satu** Service class di project ini: `App\Services\C45Classifier` (`app/Services/C45Classifier.php`). Tidak ada Service lain (tidak ada `ReportService`, `StockService`, dsb — logika non-algoritma tetap di Controller).

## Seluruh Method `C45Classifier`

**Public:**
- `buildTree(array $dataset, array $attributes): array` — entry point, memanggil `buildNode()` secara rekursif, simpan & kembalikan hasil sebagai `$this->tree`.
- `classify(array $row, ?array $node = null): string` — telusuri pohon (default pakai `$this->tree` kalau `$node` null) secara rekursif berdasarkan nilai atribut baris data; kalau nilai atribut tidak dikenal di percabangan, fallback ke `$node['majority']`.
- `calculateEntropy(array $dataset): float` — `Entropy(S) = -Σ pᵢ·log2(pᵢ)`; return `0.0` kalau dataset kosong (guard eksplisit).
- `calculateGain(array $dataset, string $attribute): float` — `Gain(S,A) = Entropy(S) - Σ(|Sv|/|S|)·Entropy(Sv)`.
- `calculateSplitInformation(array $dataset, string $attribute): float` — `SplitInfo(S,A) = -Σ(|Sv|/|S|)·log2(|Sv|/|S|)`.
- `calculateGainRatio(array $dataset, string $attribute): float` — `Gain/SplitInfo`; return `0.0` kalau `SplitInfo == 0` (mencegah div-by-zero/NaN — ini justru ciri khas C4.5 dibanding ID3 murni).

**Protected (helper internal):**
- `buildNode(array $dataset, array $attributes): array` — logika rekursif inti pembentukan pohon (lihat di bawah).
- `filterByAttribute()`, `uniqueValues()`, `majorityLabel()` — utility murni tanpa efek samping.

## Alur Algoritma (bagaimana tree dibentuk)
1. Hitung label mayoritas dataset saat ini (`majorityLabel`).
2. **Base case 1**: kalau seluruh baris punya label sama → jadi leaf (`type: leaf`) dengan label tersebut.
3. **Base case 2**: kalau atribut sudah habis (`empty($attributes)`) → jadi leaf dengan label mayoritas.
4. Hitung Gain Ratio untuk **setiap** atribut yang tersisa, urutkan (`arsort`), ambil atribut dengan Gain Ratio tertinggi.
5. **Base case 3**: kalau Gain Ratio terbaik `<= 0` → jadi leaf dengan label mayoritas (mencegah split yang tidak informatif).
6. Kalau lolos semua base case: bagi dataset jadi beberapa cabang berdasarkan nilai unik atribut terbaik, hapus atribut itu dari daftar atribut tersisa (`array_diff`), lalu **rekursif** panggil `buildNode()` untuk tiap cabang (subset kosong langsung jadi leaf dengan label mayoritas parent, tanpa rekursi lebih lanjut — ini mencegah `calculateGain`/`calculateSplitInformation` pernah dipanggil dengan dataset kosong).
7. Node non-leaf menyimpan: `type: 'node'`, `attribute` (atribut yang dipakai split), `entropy`, `gain_ratio`, `majority` (untuk fallback klasifikasi), `branches` (map nilai atribut → sub-node).

## Bagaimana Klasifikasi Dilakukan
`classify($row)` menelusuri `$this->tree` mulai dari root: kalau node adalah leaf, kembalikan `label`-nya langsung; kalau bukan, ambil nilai atribut node dari `$row`, ikuti cabang yang cocok (`$node['branches'][$value]`) secara rekursif; kalau nilai tidak ditemukan di cabang manapun (atribut baru yang tidak ada di data latih), kembalikan `$node['majority']` sebagai fallback.

## Bagaimana Gain Ratio Dihitung
Persis rumus C4.5 standar: `GainRatio(S,A) = Gain(S,A) / SplitInfo(S,A)`, dengan guard `SplitInfo == 0 → return 0.0` supaya atribut dengan hanya satu nilai unik (SplitInfo=0, seharusnya tak terhingga) tidak menyebabkan crash atau bias tak wajar — inilah bedanya dengan ID3 yang murni pakai Information Gain tanpa normalisasi ini (Gain Ratio menghindari bias terhadap atribut yang punya banyak nilai unik).

## Bagaimana Hasil Dikembalikan
`buildTree()` mengembalikan struktur pohon sebagai **array asosiatif PHP bersarang** (bukan objek/class Node), yang lalu (di `PredictionController`) disimpan ke cache aplikasi (`Cache::forever('c45_analysis', ...)`, tabel `cache`) supaya tetap tampil di halaman Prediksi walau Admin logout/login ulang. Struktur pohon **tidak** disimpan ke tabel bisnis; yang disimpan permanen di tabel bisnis hanyalah hasil akhir klasifikasi per produk (`predicted_label`, `last_classified_at` di tabel `products`). Cache pohon dihapus otomatis begitu data Produk/Distribusi berubah, karena pohon lama tidak lagi mewakili data terbaru.

---

# 8. Routing

## Grup `guest` (hanya bisa diakses kalau **belum** login) — `routes/auth.php`
`GET/POST login`, `GET/POST forgot-password`, `GET reset-password/{token}`, `POST reset-password`.

## Grup `auth` (wajib login) — `routes/web.php`
`GET /dashboard`, `Route::resource('products')` minus `show` (index/create/store/edit/update/destroy), `Route::resource('distributions')` minus `show`, `GET/POST /predictions` (`index`/`process`), `GET /reports`, `GET/PATCH/DELETE /profile`.

## Grup `auth` (wajib login) — `routes/auth.php`
`GET verify-email` (+ `verified` middleware **tidak** dipakai di mana pun — route ini secara praktik tidak pernah dituju dari UI), `GET verify-email/{id}/{hash}` (+ `signed, throttle:6,1`), `POST email/verification-notification` (+ `throttle:6,1`), `GET/POST confirm-password`, `PUT password`, `POST logout`.

## Tanpa middleware
`GET /` — langsung `redirect()->route('login')`, tidak render apa-apa.

**Catatan**: tidak ada route API (`routes/api.php` tidak dipakai), tidak ada route registrasi, tidak ada middleware role/permission kustom (`auth` adalah satu-satunya gerbang akses, konsisten dengan single-admin).

---

# 9. Dashboard

Seluruh data dihitung **live** di `DashboardController::index()` setiap kali halaman dibuka (tidak ada caching):

| Elemen | Sumber Data |
|---|---|
| Kartu Total Produk | `Product::count()` |
| Kartu Total Distribusi + tren % | `Distribution::count()`, dibandingkan bulan ini vs bulan lalu (`calculateMonthlyDistributionTrend()`) |
| Kartu Distribusi Hari Ini | `Distribution::whereDate('distribution_date', today())->count()` |
| Grafik 6 Bulan Terakhir | `Distribution::selectRaw(...SUM(quantity)...)->groupBy(month)`, 6 bulan ke belakang dari bulan berjalan |
| Top 5 Produk Terlaris | `Product::withSum('distributions','quantity')->orderByDesc(...)->take(5)` |
| Hasil Prediksi Terbaru (5) | `Product::whereNotNull('predicted_label')->orderByDesc('last_classified_at')->take(5)` |
| Rata-rata Stok | `Product::avg('stock')`, dibulatkan |
| Rekomendasi Restock | `Product::where('predicted_label','Laris')->where('stock','<', averageStock)->orderBy('stock')->take(5)` |
| Ringkasan Analisis Terakhir (tanggal, jumlah produk, akurasi) | `buildLastAnalysisSummary()` — akurasi **dihitung ulang saat itu juga** dari data terkini, bukan dibaca dari nilai tersimpan, memakai formula label yang sama seperti proses klasifikasi (total kuantitas vs rata-rata) |

**Tidak ada** data yang berasal dari cache/queue/API eksternal — seluruhnya query MySQL langsung.

---

# 10. Modul Produk

- **Tambah**: form → `StoreProductRequest` (validasi field + keunikan kode terhadap produk aktif) → `Product::create()`.
- **Edit**: form terisi data lama → `UpdateProductRequest` (sama, keunikan kode mengecualikan dirinya sendiri) → `$product->update()`.
- **Hapus**: konfirmasi via `confirm()` JS browser (bukan modal khusus) → `$product->delete()` → **soft delete** (`deleted_at` diisi, baris tidak hilang secara fisik, agar riwayat distribusinya tetap valid).
- **Cari**: `LIKE` pada `code`/`name`/`category`/`size`, hasil dipaginasi (10/halaman), query string dipertahankan (`withQueryString()`).
- **Soft Delete**: produk yang dihapus otomatis tersaring dari semua query normal (termasuk dropdown pilih produk di form Tambah Distribusi), tapi tetap muncul di riwayat Distribusi/Laporan lama berkat `withTrashed()` di relasi `Distribution::product()`.
- **Validasi**: lihat Bagian 6. `predicted_label`/`last_classified_at` **tidak bisa** diisi lewat form ini (di luar `$fillable`).

---

# 11. Modul Distribusi

- **Tambah**: pilih produk (dropdown, hanya produk aktif) + tanggal + jumlah + catatan → `StoreDistributionRequest` (produk harus ada & aktif, jumlah ≤ stok tersedia) → dalam `DB::transaction`: `Distribution::create()` lalu `$product->decrement('stock', quantity)`.
- **Edit**: produk **tidak bisa diganti**, hanya tanggal/jumlah/catatan → `UpdateDistributionRequest` (jumlah baru ≤ stok tersedia + jumlah lama) → dalam `DB::transaction`: `$product->increment('stock', oldQuantity)` lalu `$product->decrement('stock', newQuantity)`.
- **Hapus**: konfirmasi JS browser (pesan eksplisit menyebut stok akan dikembalikan) → dalam `DB::transaction`: `$product->increment('stock', quantity)` lalu `$distribution->delete()` (soft delete).
- **Cari**: `LIKE` pada nama/kategori produk (`whereHas('product', ...)`) **atau** tanggal (`parseSearchDate()` mem-parsing string jadi tanggal valid kalau polanya menyerupai tanggal, pakai regex guard sebelum `Carbon::parse()` supaya kata kunci non-tanggal tidak salah tafsir).
- **Penyesuaian stok**: sepenuhnya otomatis di Controller (bukan trigger DB, bukan event/observer Model) — lihat detail arah penyesuaian per aksi di atas.
- **Transaction database**: ketiga aksi tulis (`store`/`update`/`destroy`) dibungkus `DB::transaction()` supaya perubahan data distribusi dan penyesuaian stok produk selalu atomik (kalau salah satu gagal, keduanya di-rollback).
- **Validasi**: lihat Bagian 6.

---

# 12. Modul Prediksi

Proses bisnis lengkap di `PredictionController::process()`, dari awal sampai akhir:

1. **Pengambilan data**: `Product::withCount('distributions')->withSum('distributions','quantity')->get()` — satu query mengambil semua produk beserta jumlah transaksi dan total kuantitas distribusinya. Kalau tidak ada produk sama sekali → proses dihentikan, redirect dengan pesan error.
2. **Dataset**: satu baris per produk (bukan per transaksi distribusi) — jadi ukuran dataset latih = jumlah produk (15 pada data uji coba), bukan jumlah transaksi (81).
3. **Atribut**: `category` (string kategori produk), `size` (ukuran produk), `frequency_level` (hasil diskritisasi, lihat preprocessing).
4. **Label**: `'Laris'` kalau total kuantitas distribusi produk ≥ rata-rata total kuantitas seluruh produk, selebihnya `'Tidak Laris'`. **Ini training label**, bukan input manual dari Admin.
5. **Preprocessing**: jumlah transaksi (`distributions_count`) tiap produk didiskritisasi jadi `Rendah`/`Sedang`/`Tinggi` berdasarkan persentil ke-33 dan ke-66 (`percentile()`, interpolasi linear) dari seluruh jumlah transaksi produk yang ada — ini satu-satunya langkah "preprocessing" data numerik jadi kategorikal (C4.5 versi ini bekerja murni pada atribut kategorikal).
6. **Pembentukan tree**: `C45Classifier::buildTree($dataset, ['category','size','frequency_level'])` — lihat Bagian 7 untuk detail algoritma.
7. **Klasifikasi**: untuk **setiap** baris dataset (bukan hanya data baru), `classify($row)` dipanggil untuk menentukan `predicted_label`-nya berdasarkan tree yang baru dibentuk — jadi seluruh produk (15 produk pada data seeder) diklasifikasi ulang setiap kali tombol "Jalankan Analisis" ditekan, bukan hanya produk yang datanya berubah.
8. **Penyimpanan `predicted_label`**: `Product::whereKey($id)->update(['predicted_label' => ..., 'last_classified_at' => now()])` — **query builder**, bukan `$model->save()`, sehingga tidak tunduk pada `$fillable` (memang disengaja, karena field ini memang tidak ada di `$fillable`).
9. **Akurasi**: `(jumlah prediksi yang cocok dengan label data latih) / (total data latih) × 100`, dibulatkan 1 desimal. **Ini akurasi terhadap data latih itu sendiri** (bukan terhadap data uji/test set terpisah — tidak ada train-test split di implementasi ini).
10. **Hasil ditampilkan**: struktur `tree`, nilai `accuracy`, dan threshold persentil disimpan ke cache aplikasi (`Cache::forever('c45_analysis', ...)`), lalu redirect ke halaman Prediksi. **`predicted_label` di tabel produk bersifat permanen**, dan visualisasi pohon keputusan juga tetap tampil (termasuk setelah logout/login ulang) sampai data Produk/Distribusi berubah — saat itu cache dihapus dan Admin perlu menjalankan analisis ulang.
11. **Rekomendasi restock**: dihitung **di view** `predictions/index.blade.php` (bukan di Controller) — `$product->predicted_label === 'Laris' && $product->stock < $averageStock` (rata-rata stok seluruh produk, dihitung di `PredictionController::index()`), ditampilkan sebagai badge "Perlu Restock" per baris produk. Logika yang **sama persis** (tapi diimplementasikan terpisah) juga ada di `DashboardController` untuk daftar rekomendasi restock di Dashboard.

---

# 13. Modul Laporan

`ReportController::index()`: filter rentang tanggal (`from`/`to` dari query string, default awal bulan berjalan s.d. hari ini), `Distribution::with('product')->whereBetween('distribution_date', [from,to])->orderBy('distribution_date')->get()`. Ditampilkan sebagai tabel; tombol cetak memakai `window.print()` bawaan browser — **tidak ada** generate PDF di server, **tidak ada** package export Excel/PDF. **Tidak ada** perhitungan keuntungan/laba di laporan ini (fitur ini secara eksplisit ditolak saat requirement-gathering).

---

# 14. Authentication

- **Login**: `AuthenticatedSessionController::store()` → `LoginRequest::authenticate()` → validasi format → cek rate limit (`RateLimiter`, maksimal 5 percobaan gagal per kombinasi email+IP, pakai event `Lockout` Laravel) → `Auth::attempt(['email','password'], remember)` → kalau sukses: `RateLimiter::clear()`, `session()->regenerate()`, redirect ke halaman yang dituju/`dashboard`; kalau gagal: `RateLimiter::hit()`, error `auth.failed`.
- **Logout**: `AuthenticatedSessionController::destroy()` → `Auth::guard('web')->logout()` → `session()->invalidate()` → `session()->regenerateToken()` → redirect ke `/`.
- **Middleware**: `auth` (Laravel default, berbasis session) menjaga seluruh route domain; `guest` menjaga route login/forgot-password supaya tidak diakses saat sudah login. **Tidak ada** middleware role/permission kustom, **tidak ada** API token/Sanctum (murni session-based, cocok untuk aplikasi Blade monolitik single-admin).
- **Rate limit**: khusus percobaan login (5x per email+IP via `RateLimiter` facade); route verifikasi email & reset password punya `throttle:6,1` (bawaan Breeze, di luar domain riset).
- **Session**: driver session mengikuti konfigurasi default Laravel (`.env`), tidak dikustomisasi di kode aplikasi. Regenerasi session dilakukan eksplisit saat login (mencegah session fixation) dan invalidasi eksplisit saat logout.
- **Registrasi**: **tidak ada** — tidak ada `RegisteredUserController`, tidak ada route `/register`. Akun Admin dibuat sekali lewat `DatabaseSeeder`.

---

# 15. UML

UML (Use Case, Activity, Sequence, Class Diagram) dan ERD untuk sistem ini sudah dirancang dan direview bersama secara bertahap berdasarkan implementasi aktual (bukan digambar ulang di sini). Status kesesuaian per jenis diagram, berdasarkan review yang sudah dilakukan:

- **ERD**: sesuai implementasi migration — 3 tabel, cardinality `products 1 -- 0..* distributions`, `users` tanpa relasi. **Catatan terbuka**: draf dokumen BAB III sempat menuliskan panjang VARCHAR yang salah untuk kolom `products` (mis. `code VARCHAR(50)`) padahal migration aktual tidak menentukan panjang eksplisit sehingga sebenarnya VARCHAR(255) — ini kesalahan dokumentasi, bukan kesalahan ERD secara struktural, dan belum diperbaiki di file dokumen.
- **Use Case Diagram**: sesuai cakupan fitur aktual (Login, Logout, Melihat Dashboard, Mengelola Data Produk, Mengelola Data Distribusi, Prediksi C4.5, Melihat Laporan Distribusi). **Catatan terbuka dari review kritis terakhir**: relasi generalisasi (`--|>`) dari Tambah/Ubah/Hapus/Cari ke "Mengelola Data Produk/Distribusi" secara ketat kurang tepat menurut definisi UML formal (children tidak saling menggantikan/substitutable), meski merupakan pola yang lazim dipakai di skripsi S1 Indonesia. Belum ada keputusan final apakah akan direvisi (dua opsi minimal sudah diajukan, menunggu keputusan pengguna).
- **Activity Diagram**: 5 diagram (Login, Mengelola Data Produk, Mengelola Data Distribusi, Prediksi C4.5, Logout) sesuai alur kode aktual. **Catatan minor**: diagram Prediksi tidak eksplisit menyebut langkah "menampilkan rekomendasi restock" meski fitur itu memang ada di halaman Prediksi.
- **Sequence Diagram**: 5 diagram representatif (Login, Tambah Produk, Tambah Distribusi, Prediksi C4.5, Logout) sesuai method/class aktual (nama Controller, Request, Model persis sama dengan kode).
- **Class Diagram**: mencakup 3 Model, 6 Controller domain, 5 Form Request, 1 Service, dengan relasi (`association` untuk Product-Distribution, `generalization` Controller ke base `Controller`, `dependency <<use>>` untuk sisanya) sesuai `use`/pemanggilan aktual di kode. **Catatan terbuka**: gambar yang ditempel di draf dokumen BAB III (Gambar 3.11) tampak terpotong, tidak menampilkan seluruh isi diagram (hanya `ProductController` yang terlihat) — perlu diekspor ulang.

---

# 16. Hal-hal Penting (Keputusan Desain)

- **Kenapa single admin**: skripsi sengaja menyederhanakan actor jadi satu (Admin/Pemilik Toko) sejak tahap analisis kebutuhan — tidak ada kebutuhan multi-role/kolaborasi untuk skala usaha ini, dan ini mengurangi kompleksitas skema & UML secara signifikan.
- **Kenapa `users` tidak punya relasi**: konsekuensi langsung dari single-admin — tidak ada kebutuhan mencatat "siapa pemilik/pembuat data" karena hanya ada satu pengguna yang inheren memiliki seluruh data.
- **Kenapa memakai soft delete** (`products`, `distributions`): supaya riwayat distribusi lama tetap bisa ditelusuri/ditampilkan meski produk terkait sudah "dihapus" oleh Admin — kalau pakai hard delete, penghapusan produk akan merusak integritas riwayat transaksi masa lalu (atau butuh cascade delete yang malah menghilangkan data historis).
- **Kenapa `predicted_label` disimpan di `products`, bukan tabel terpisah**: karena hasil analisis terbaru cukup menimpa yang lama (tidak ada requirement menyimpan riwayat setiap kali analisis dijalankan) — menghindari tabel `classification_runs`/`prediction_results` yang tidak diperlukan, sesuai keputusan penyederhanaan skema di awal.
- **Kenapa distribusi mengurangi stok otomatis**: supaya data stok selalu konsisten dengan riwayat barang keluar tanpa Admin perlu update stok manual dua kali (input distribusi + update stok terpisah) — sekaligus mengurangi risiko human error/data tidak sinkron.
- **Kenapa pohon keputusan (tree) tidak disimpan ke DB**: keputusan desain eksplisit dari tahap ERD — hanya `predicted_label` per produk yang perlu persisten; visualisasi pohon cukup disimpan di cache aplikasi (bukan tabel bisnis) dan diganti setiap kali analisis dijalankan ulang, karena pohon lama sudah tidak relevan begitu ada analisis baru.
- **Kenapa memakai C4.5 (bukan ID3/algoritma lain)**: C4.5 dipilih karena mendukung penanganan atribut yang lebih baik lewat Gain Ratio (menghindari bias ID3 terhadap atribut bernilai banyak) — relevan karena salah satu atribut (`frequency_level`) hasil diskritisasi punya potensi Split Information tinggi.
- **Kenapa memakai Waterfall**: tidak terverifikasi dari source code (lihat Bagian 1) — berdasarkan pernyataan pengguna. Cocok secara natural dengan pola pengembangan bertahap yang benar-benar dijalankan (setiap tahap disetujui sebelum lanjut, tidak ada iterasi mundur ke tahap sebelumnya).
- **Kenapa tidak pakai package admin panel (Filament dkk)**: keputusan eksplisit sejak awal — supaya struktur MVC tetap standar Laravel, mudah dijelaskan & dinilai di skripsi, tanpa ketergantungan pada "magic" package pihak ketiga.
- **Kenapa tidak ada export Excel & rekap keuntungan**: dua fitur ini diusulkan di tengah pengembangan tapi ditolak — Excel import/export akan menambah dependency package baru di luar scope, dan rekap keuntungan butuh kolom harga modal yang tidak ada di skema (`products` hanya punya `price` jual, bukan harga beli/modal), sehingga akan mengubah skema yang sudah disepakati.

---

# 17. Kelebihan Project

- Algoritma C4.5 diimplementasikan **manual dari nol** (bukan library ML), sehingga seluruh langkah (entropy, gain, split info, gain ratio, pembentukan tree, klasifikasi) transparan dan bisa dijelaskan/diverifikasi manual — cocok untuk kebutuhan pembuktian akademik skripsi.
- Skema database sangat minimal dan konsisten (3 tabel), tidak ada over-engineering (tidak ada tabel/fitur yang tidak dipakai).
- Konsistensi tinggi antara ERD, UML, dan implementasi aktual — sudah diverifikasi berulang kali lewat proses review terpisah.
- Penanganan soft delete sudah benar (termasuk edge case relasi `withTrashed()` yang mudah terlewat).
- Validasi cukup ketat untuk konteks bisnisnya (stok tidak boleh minus, kode produk unik, kredensial dengan rate limiting).
- Transaksi database dipakai secara tepat di titik-titik yang memang butuh atomisitas (penyesuaian stok).
- Struktur kode rapi & terpisah sesuai konvensi Laravel (Controller/Model/Request/Service), memudahkan penelusuran untuk keperluan skripsi.

---

# 18. Keterbatasan Project

- **Tidak ada train-test split**: akurasi model dihitung terhadap data latih itu sendiri, bukan data uji terpisah — secara metodologi machine learning ini rawan dianggap kurang ketat (overfitting terhadap data training tidak terdeteksi).
- **Data latih hanya 20 baris** (satu baris per produk, bukan per transaksi) — sangat kecil untuk ukuran dataset klasifikasi pada umumnya, meski cukup untuk pembuktian konsep algoritma di skala skripsi.
- **Randomisasi seeder tanpa seed tetap**: kuantitas per transaksi di `DistributionSeeder` dibangkitkan dengan Faker tanpa `seed()` tetap, sehingga angka entropy/gain ratio persis di dokumentasi skripsi berpotensi (meski kemungkinannya kecil) berbeda kalau database di-reseed ulang.
- **Tidak ada test otomatis untuk logika domain** — `tests/` hanya berisi test scaffolding bawaan Breeze; tidak ada unit test untuk `C45Classifier`, `PredictionController`, atau modul Produk/Distribusi.
- **Duplikasi logika**: perhitungan akurasi & rekomendasi restock diimplementasikan dua kali secara independen (`PredictionController` dan `DashboardController`), berisiko drift kalau salah satu diubah tanpa mengubah yang lain.
- **`ProfileController`/route verifikasi email adalah sisa scaffolding Breeze yang tidak sepenuhnya konsisten** (`User` tidak implement `MustVerifyEmail`, tapi route & tampilannya masih ada) — tidak berbahaya, tapi berpotensi membingungkan pembaca kode.
- **Tidak ada API/mobile-ready layer** — murni server-rendered Blade, tidak ada endpoint JSON untuk kebutuhan integrasi lain.
- **Tidak ada mekanisme audit-trail** (siapa mengubah apa, kapan) di luar `created_at`/`updated_at` bawaan Eloquent — konsekuensi dari desain single-admin tanpa `user_id` di tabel bisnis.
- **Tidak ada fitur laporan keuangan/keuntungan** — skema `products` hanya menyimpan harga jual, bukan harga modal, sehingga analisis profitabilitas di luar cakupan sistem saat ini.
