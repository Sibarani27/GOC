<?php

namespace Database\Seeders;

use App\Models\Alert;
use App\Models\Asset;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $assets = collect([
            ['server-web-01', 'server', '10.0.1.10', 'Data Center Jakarta', 'online', 'critical'],
            ['server-db-01', 'server', '10.0.1.20', 'Data Center Jakarta', 'online', 'critical'],
            ['fw-perimeter-01', 'firewall', '10.0.0.1', 'Data Center Jakarta', 'online', 'high'],
            ['router-core-01', 'router', '10.0.0.2', 'Data Center Jakarta', 'degraded', 'high'],
            ['switch-lt2-01', 'switch', '10.0.2.5', 'Gedung A Lantai 2', 'online', 'medium'],
            ['pc-admin-07', 'workstation', '10.0.3.77', 'Gedung A Lantai 3', 'offline', 'low'],
        ])->map(fn ($a) => Asset::firstOrCreate(
            ['name' => $a[0]],
            ['type' => $a[1], 'ip_address' => $a[2], 'location' => $a[3], 'status' => $a[4],
             'criticality' => $a[5], 'last_seen_at' => now()]
        ));

        $templates = [
            ['Percobaan login SSH gagal berulang', 'high', 'Credential Access', 'T1110'],
            ['Pemindaian port terdeteksi', 'medium', 'Discovery', 'T1046'],
            ['Trafik DDoS volumetrik', 'critical', 'Impact', 'T1498'],
            ['Malware terdeteksi pada endpoint', 'critical', 'Execution', 'T1204'],
            ['Login admin di luar jam kerja', 'medium', 'Initial Access', 'T1078'],
            ['Lonjakan trafik keluar tidak wajar', 'high', 'Exfiltration', 'T1048'],
            ['Perubahan konfigurasi firewall', 'low', 'Defense Evasion', 'T1562'],
            ['Sertifikat TLS hampir kedaluwarsa', 'low', null, null],
        ];

        foreach (range(1, 40) as $i) {
            $t = $templates[array_rand($templates)];
            $asset = $assets->random();
            $detected = now()->subMinutes(random_int(1, 24 * 60));
            $ip = random_int(11, 223) . '.' . random_int(0, 255) . '.' . random_int(0, 255) . '.' . random_int(1, 254);

            Alert::create([
                'source' => collect(['wazuh', 'suricata', 'zabbix'])->random(),
                'title' => $t[0],
                'description' => 'Data contoh untuk keperluan pengembangan.',
                'severity' => $t[1],
                'status' => collect(['new', 'new', 'new', 'acknowledged', 'in_progress', 'resolved', 'false_positive'])->random(),
                'asset_id' => $asset->id,
                'source_ip' => $ip,
                'mitre_tactic' => $t[2],
                'mitre_technique' => $t[3],
                'fingerprint' => sha1(Str::random(12)),
                'occurrences' => random_int(1, 12),
                'detected_at' => $detected,
                'raw_payload' => ['demo' => true, 'rule_id' => random_int(1000, 9999)],
            ]);
        }
    }
}
