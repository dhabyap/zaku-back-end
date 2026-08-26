# Cara Kerja Sistem Budget Zaku

Sistem budget Zaku dirancang untuk membantu pengguna memantau pengeluaran berdasarkan kategori dalam periode bulanan.

## Alur Kerja (Step-by-Step)

1.  **Definisi Budget (Backend)**
    *   User membuat budget melalui `BudgetController@store`.
    *   Data disimpan di tabel `budgets` (user_id, category_id, amount, period, start_date).
    *   Validasi memastikan satu kategori hanya memiliki satu budget aktif per periode.

2.  **Pengambilan Data (API)**
    *   **Dashboard**: `DashboardController@index` mengambil ringkasan pengeluaran total.
    *   **Progress**: `BudgetController@allProgress` (endpoint `/v1/budgets/progress`) menghitung real-time:
        *   `spent`: Total transaksi pada kategori tersebut di bulan berjalan.
        *   `remaining`: `amount` - `spent`.
        *   `percentage`: `(spent / amount) * 100`.
        *   `status`: Logika status (aman/waspada/boros) berdasarkan persentase.

3.  **Tampilan (Frontend - Alpine.js)**
    *   `alpine-components.js` (`dashboardHome` component) memanggil `/v1/budgets/progress` saat `fetchDashboard` dijalankan.
    *   Data dipetakan ke variabel `catBudgets`, `catTotalSpent`, `catTopBudgets`.
    *   `home.blade.php` merender data tersebut menggunakan template Alpine (`x-for` untuk list kategori, `x-text` untuk angka).

4.  **Interaksi User**
    *   User melihat status budget di dashboard.
    *   User menekan "KELOLA BUDGET" untuk menambah/mengubah budget.
    *   Saat transaksi baru ditambahkan, `spent` di `BudgetController` akan otomatis terupdate karena dihitung berdasarkan transaksi yang ada.

## Ringkasan Teknis
*   **Endpoint Utama**: `/api/v1/budgets/progress` (mengambil semua progress budget user).
*   **Logika Perhitungan**: Dilakukan di `BudgetService@getBudgetProgress` dengan menjumlahkan transaksi terkait kategori budget.
*   **State Management**: Dikelola oleh Alpine.js di frontend untuk sinkronisasi data tanpa reload halaman.

## Pertanyaan Umum (FAQ)

### Bagaimana jika berganti bulan?
Sistem budget Zaku bersifat **otomatis dan berkelanjutan (recurring)**.
*   **Tidak perlu tambah ulang**: Budget yang sudah dibuat akan tetap aktif untuk bulan-bulan berikutnya selama tidak dihapus.
*   **Reset Otomatis**: Saat bulan berganti, `BudgetService` akan menghitung ulang `spent` berdasarkan transaksi yang terjadi di bulan berjalan saja.
*   **Data Historis**: Budget bulan lalu tetap tersimpan sebagai data historis, namun progress di dashboard akan selalu menampilkan data untuk bulan aktif saat ini.
