<?php

namespace App\Livewire\Audit;

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

#[Layout('layouts.goc')]
#[Title('Audit Trail')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $logName = '';
    public string $causerId = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public ?int $openId = null;

    public function updating(string $name): void
    {
        if (in_array($name, ['search', 'logName', 'causerId', 'dateFrom', 'dateTo'], true)) {
            $this->resetPage();
            $this->openId = null;
        }
    }

    public function toggle(int $id): void
    {
        $this->openId = $this->openId === $id ? null : $id;
    }

    public function render()
    {
        // Halaman ini hanya membaca: tidak ada fitur ubah atau hapus log
        $query = Activity::query()->with('causer')->latest('id');

        if ($this->logName !== '') {
            $query->where('log_name', $this->logName);
        }
        if ($this->causerId !== '') {
            $query->where('causer_type', User::class)->where('causer_id', (int) $this->causerId);
        }
        if ($this->dateFrom !== '') {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo !== '') {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }
        if (trim($this->search) !== '') {
            $query->where('description', 'like', '%' . trim($this->search) . '%');
        }

        return view('livewire.audit.index', [
            'logs' => $query->paginate(20),
            'logNames' => Activity::query()->distinct()->orderBy('log_name')->pluck('log_name')->filter()->values(),
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
