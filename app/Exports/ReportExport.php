<?php

namespace App\Exports;

use App\Support\ReportBuilder as R;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ReportExport implements WithMultipleSheets
{
    public function __construct(private array $r)
    {
    }

    public function sheets(): array
    {
        $r = $this->r;
        $a = $r['alerts'];
        $i = $r['incidents'];

        $summary = [
            ['Metrik', 'Nilai'],
            ['Periode', $r['from'] . ' s.d. ' . $r['to']],
            ['Dibuat', $r['generated_at']],
            ['Total alert', $a['total']],
            ['Total kejadian (termasuk duplikat)', $a['events']],
            ['False positive (%)', $a['false_positive_pct']],
            ['Insiden dibuka', $i['opened']],
            ['Insiden selesai/ditutup', $i['resolved']],
            ['Insiden masih terbuka', $i['still_open']],
            ['MTTD (rata-rata)', R::duration($i['mttd'])],
            ['MTTC (rata-rata)', R::duration($i['mttc'])],
            ['MTTR (rata-rata)', R::duration($i['mttr'])],
        ];

        $sev = [['Tingkat', 'Jumlah']];
        foreach (['critical', 'high', 'medium', 'low'] as $s) {
            $sev[] = [R::severityLabel($s), $a['by_severity'][$s] ?? 0];
        }

        $status = [['Status', 'Jumlah']];
        foreach ($a['by_status'] as $k => $v) {
            $status[] = [R::statusLabel($k), $v];
        }

        $cat = [['Kategori insiden', 'Jumlah']];
        foreach ($i['by_category'] as $k => $v) {
            $cat[] = [R::categoryLabel($k), $v];
        }

        $daily = [['Tanggal', 'Jumlah alert']];
        foreach ($a['daily'] as $d) {
            $daily[] = [$d['date'], $d['total']];
        }

        $assets = [['Aset', 'Jumlah alert']];
        foreach ($a['top_assets'] as $x) {
            $assets[] = [$x['name'], $x['total']];
        }

        $ips = [['IP sumber', 'Jumlah alert']];
        foreach ($a['top_ips'] as $x) {
            $ips[] = [$x['ip'], $x['total']];
        }

        $sources = [['Sumber data', 'Jumlah alert']];
        foreach ($a['by_source'] as $k => $v) {
            $sources[] = [$k, $v];
        }

        return [
            new ArraySheet('Ringkasan', $summary),
            new ArraySheet('Alert per tingkat', $sev),
            new ArraySheet('Alert per status', $status),
            new ArraySheet('Insiden per kategori', $cat),
            new ArraySheet('Tren harian', $daily),
            new ArraySheet('Aset teratas', $assets),
            new ArraySheet('IP teratas', $ips),
            new ArraySheet('Sumber data', $sources),
        ];
    }
}
