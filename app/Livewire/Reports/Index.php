<?php

namespace App\Livewire\Reports;

use App\Support\ReportBuilder;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.goc')]
#[Title('Laporan')]
class Index extends Component
{
    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    public string $error = '';

    public function mount(): void
    {
        if ($this->from === '' || $this->to === '') {
            $this->setPreset(30);
        }
    }

    public function setPreset(int $days): void
    {
        if (! in_array($days, [7, 30, 90], true)) {
            return;
        }

        $this->to = now()->format('Y-m-d');
        $this->from = now()->subDays($days - 1)->format('Y-m-d');
    }

    /** @return array{0: Carbon, 1: Carbon}|null */
    protected function range(): ?array
    {
        $this->error = '';

        try {
            $from = Carbon::createFromFormat('Y-m-d', $this->from)->startOfDay();
            $to = Carbon::createFromFormat('Y-m-d', $this->to)->startOfDay();
        } catch (\Throwable) {
            $this->error = 'Format tanggal tidak valid.';
            return null;
        }

        if ($from->gt($to)) {
            $this->error = 'Tanggal awal tidak boleh setelah tanggal akhir.';
            return null;
        }
        if ($from->diffInDays($to) >= ReportBuilder::MAX_DAYS) {
            $this->error = 'Rentang laporan maksimal ' . ReportBuilder::MAX_DAYS . ' hari.';
            return null;
        }

        return [$from, $to];
    }

    public function render()
    {
        $range = $this->range();

        return view('livewire.reports.index', [
            'r' => $range ? ReportBuilder::build($range[0], $range[1]) : null,
        ]);
    }
}
