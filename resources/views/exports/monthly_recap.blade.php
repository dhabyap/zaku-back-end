<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Bulanan {{ $month_year }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; margin: 20px; }
        h1 { font-size: 22px; margin-bottom: 4px; }
        .subtitle { color: #666; margin-bottom: 20px; }
        .summary { display: flex; gap: 10px; margin-bottom: 20px; }
        .card { flex: 1; border: 1px solid #ccc; padding: 12px; }
        .card .label { font-size: 10px; text-transform: uppercase; color: #888; }
        .card .value { font-size: 18px; font-weight: bold; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background: #f0f0f0; }
        .cat-row td:first-child { font-weight: bold; }
        .right { text-align: right; }
        .footer { margin-top: 30px; font-size: 10px; color: #999; text-align: center; }
    </style>
</head>
<body>
    <h1>Rekap Bulanan</h1>
    <div class="subtitle">{{ $month_year }} — ZAKU Financial Report</div>

    <div class="summary">
        <div class="card">
            <div class="label">Pemasukan</div>
            <div class="value">Rp {{ number_format($total_income, 0, ',', '.') }}</div>
        </div>
        <div class="card">
            <div class="label">Pengeluaran</div>
            <div class="value">Rp {{ number_format($total_expense, 0, ',', '.') }}</div>
        </div>
        <div class="card">
            <div class="label">Net Cashflow</div>
            <div class="value">Rp {{ number_format($net_cashflow, 0, ',', '.') }}</div>
        </div>
        <div class="card">
            <div class="label">Savings Rate</div>
            <div class="value">{{ $savings_rate }}%</div>
        </div>
    </div>

    <h3>Pengeluaran per Kategori</h3>
    <table>
        <thead>
            <tr><th>Kategori</th><th class="right">Jumlah</th></tr>
        </thead>
        <tbody>
            @forelse($expense_by_category as $cat)
                <tr class="cat-row">
                    <td>{{ $cat['category_icon'] ?? '📌' }} {{ $cat['category_name'] }}</td>
                    <td class="right">Rp {{ number_format($cat['amount'] ?? 0, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="2">Tidak ada pengeluaran.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h3>Top 5 Pengeluaran Terbesar</h3>
    <table>
        <thead>
            <tr><th>Tanggal</th><th>Deskripsi</th><th>Kategori</th><th class="right">Nominal</th></tr>
        </thead>
        <tbody>
            @forelse($top_expenses as $t)
                <tr>
                    <td>{{ $t['date'] ?? '' }}</td>
                    <td>{{ $t['description'] }}</td>
                    <td>{{ $t['category_icon'] ?? '📌' }} {{ $t['category_name'] }}</td>
                    <td class="right">Rp {{ number_format($t['amount'] ?? 0, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Tidak ada transaksi.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Dibuat otomatis oleh ZAKU — {{ now()->format('d M Y H:i') }}</div>
</body>
</html>
