<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ArraySheet implements FromArray, WithTitle, ShouldAutoSize, WithStyles
{
    public function __construct(private string $title, private array $rows)
    {
    }

    public function array(): array
    {
        return array_map(fn ($row) => array_map([self::class, 'safe'], $row), $this->rows);
    }

    public function title(): string
    {
        return $this->title;
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }

    /** Cegah injeksi rumus: teks yang diawali = + - @ dianggap teks biasa. */
    public static function safe(mixed $v): mixed
    {
        if (is_string($v) && $v !== '' && preg_match('/^[=+\-@\t\r]/', $v)) {
            return "'" . $v;
        }

        return $v;
    }
}
