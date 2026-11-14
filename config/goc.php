<?php

$list = fn (string $key) => array_values(array_filter(array_map('trim', explode(',', (string) env($key, '')))));

return [
    // Wajibkan 2FA untuk semua pengguna panel (aktifkan setelah semua akun mendaftar 2FA)
    'require_2fa' => (bool) env('GOC_REQUIRE_2FA', false),

    // Daftar IP/CIDR yang boleh membuka panel web. Kosong = tanpa pembatasan.
    // Contoh: GOC_ALLOWED_IPS=127.0.0.1,::1,10.0.0.0/24
    'allowed_ips' => $list('GOC_ALLOWED_IPS'),

    // Daftar IP/CIDR sumber data yang boleh memanggil POST /api/alerts. Kosong = tanpa pembatasan.
    'api_allowed_ips' => $list('GOC_API_ALLOWED_IPS'),
];
