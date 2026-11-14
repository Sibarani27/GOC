@php
    use App\Support\ReportBuilder as R;
    $a = $r['alerts'];
    $i = $r['incidents'];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Laporan GOC {{ $r['from'] }} - {{ $r['to'] }}</title>
<style>
    @page { margin: 28px 32px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; }
    h1 { font-size: 17px; margin: 0; }
    h2 { font-size: 12px; margin: 18px 0 6px; padding-bottom: 3px; border-bottom: 1px solid #cbd5e1; }
    .muted { color: #64748b; }
    table { width: 100%; border-collapse: collapse; }
    th, td { text-align: left; padding: 4px 6px; border-bottom: 1px solid #e2e8f0; }
    th { background: #f1f5f9; font-size: 9px; text-transform: uppercase; color: #475569; }
    td.n, th.n { text-align: right; }
    .cards td { border: 1px solid #cbd5e1; padding: 8px; width: 33%; vertical-align: top; }
    .big { font-size: 15px; font-weight: bold; }
    .bar { height: 7px; background: #d97706; }
    .barbg { background: #e2e8f0; }
    .trend td { padding: 0 1px; border: 0; vertical-align: bottom; }
    .footer { margin-top: 20px; font-size: 8px; color: #64748b; }
</style>
</head>
<body>
    <h1>GARUDA OPERATIONS CENTER</h1>
    <div class="muted">Laporan Operasional · Periode {{ $r['from'] }} s.d. {{ $r['to'] }} ({{ $r['days'] }} hari)</div>
    <div class="muted">Dibuat {{ $r['generated_at'] }} oleh {{ $by }}</div>

    <h2>Waktu respons insiden</h2>
    <table class="cards"><tr>
        @foreach ([['MTTD', 'mttd', 'n_mttd', 'Deteksi → insiden dibuka'], ['MTTC', 'mttc', 'n_mttc', 'Dibuka → terkendali'], ['MTTR', 'mttr', 'n_mttr', 'Dibuka → ditutup']] as [$label, $k, $nk, $hint])
            <td>
                <div class="muted">{{ $label }}</div>
                <div class="big">{{ R::duration($i[$k]) }}</div>
                <div class="muted">{{ $hint }} · dari {{ $i[$nk] }} insiden</div>
            </td>
        @endforeach
    </tr></table>

    <h2>Ringkasan</h2>
    <table>
        <tr><td>Total alert</td><td class="n">{{ $a['total'] }}</td><td>Insiden dibuka</td><td class="n">{{ $i['opened'] }}</td></tr>
        <tr><td>Total kejadian (termasuk duplikat)</td><td class="n">{{ $a['events'] }}</td><td>Insiden selesai / ditutup</td><td class="n">{{ $i['resolved'] }}</td></tr>
        <tr><td>False positive</td><td class="n">{{ $a['false_positive_pct'] }}%</td><td>Insiden masih terbuka</td><td class="n">{{ $i['still_open'] }}</td></tr>
    </table>

    <h2>Tren alert harian</h2>
    <table class="trend" style="height: 80px;"><tr>
        @foreach ($a['daily'] as $d)
            <td><div style="height: {{ $d['total'] > 0 ? max(2, round($d['total'] / $a['max_daily'] * 70)) : 0 }}px; background: #d97706;"></div></td>
        @endforeach
    </tr></table>
    <table><tr><td class="muted">{{ $r['from'] }}</td><td class="muted n">{{ $r['to'] }}</td></tr></table>

    <h2>Alert per tingkat</h2>
    <table>
        <tr><th style="width:90px">Tingkat</th><th>Proporsi</th><th class="n" style="width:50px">Jumlah</th></tr>
        @foreach (['critical', 'high', 'medium', 'low'] as $s)
            @php($n = $a['by_severity'][$s] ?? 0)
            <tr>
                <td>{{ R::severityLabel($s) }}</td>
                <td><div class="barbg"><div class="bar" style="width: {{ $a['total'] ? round($n / $a['total'] * 100) : 0 }}%"></div></div></td>
                <td class="n">{{ $n }}</td>
            </tr>
        @endforeach
    </table>

    <h2>Alert per status</h2>
    <table>
        <tr><th>Status</th><th class="n">Jumlah</th></tr>
        @forelse ($a['by_status'] as $k => $v)
            <tr><td>{{ R::statusLabel($k) }}</td><td class="n">{{ $v }}</td></tr>
        @empty
            <tr><td colspan="2" class="muted">Tidak ada data.</td></tr>
        @endforelse
    </table>

    <h2>Insiden per kategori</h2>
    <table>
        <tr><th>Kategori</th><th class="n">Jumlah</th></tr>
        @forelse ($i['by_category'] as $k => $v)
            <tr><td>{{ R::categoryLabel($k) }}</td><td class="n">{{ $v }}</td></tr>
        @empty
            <tr><td colspan="2" class="muted">Tidak ada insiden pada periode ini.</td></tr>
        @endforelse
    </table>

    <h2>Aset dengan alert terbanyak</h2>
    <table>
        <tr><th>Aset</th><th class="n">Jumlah alert</th></tr>
        @forelse ($a['top_assets'] as $x)
            <tr><td>{{ $x['name'] }}</td><td class="n">{{ $x['total'] }}</td></tr>
        @empty
            <tr><td colspan="2" class="muted">Tidak ada data.</td></tr>
        @endforelse
    </table>

    <h2>IP sumber dengan alert terbanyak</h2>
    <table>
        <tr><th>IP sumber</th><th class="n">Jumlah alert</th></tr>
        @forelse ($a['top_ips'] as $x)
            <tr><td>{{ $x['ip'] }}</td><td class="n">{{ $x['total'] }}</td></tr>
        @empty
            <tr><td colspan="2" class="muted">Tidak ada data.</td></tr>
        @endforelse
    </table>

    <div class="footer">
        MTTD, MTTC, dan MTTR dihitung dari insiden yang dibuka pada periode laporan. MTTC hanya mencakup insiden yang sudah terkendali,
        dan MTTR hanya insiden yang sudah ditutup. Dokumen ini dihasilkan otomatis oleh Garuda Operations Center.
    </div>
</body>
</html>
