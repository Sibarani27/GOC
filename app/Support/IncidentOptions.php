<?php

namespace App\Support;

class IncidentOptions
{
    public const CATEGORIES = [
        'malware' => 'Malware',
        'phishing' => 'Phishing',
        'ddos' => 'DDoS',
        'data_leak' => 'Kebocoran data',
        'unauthorized_access' => 'Akses tidak sah',
        'vulnerability' => 'Kerentanan',
        'other' => 'Lainnya',
    ];

    public const STATUSES = [
        'open' => 'Terbuka',
        'investigating' => 'Investigasi',
        'contained' => 'Terkendali',
        'resolved' => 'Selesai',
        'closed' => 'Ditutup',
    ];

    public const SEVERITIES = ['low', 'medium', 'high', 'critical'];

    public const CLOSED_STATUSES = ['resolved', 'closed'];
}
