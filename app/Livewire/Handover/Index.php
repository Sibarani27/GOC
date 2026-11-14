<?php

namespace App\Livewire\Handover;

use App\Models\Alert;
use App\Models\Incident;
use App\Models\ShiftHandover;
use App\Models\User;
use App\Support\IncidentOptions;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.goc')]
#[Title('Serah Terima Shift')]
class Index extends Component
{
    use WithPagination;

    public const SHIFTS = [
        'pagi' => 'Pagi (07.00–15.00)',
        'siang' => 'Siang (15.00–23.00)',
        'malam' => 'Malam (23.00–07.00)',
    ];

    private const SEVERITY_ORDER = "case severity when 'critical' then 0 when 'high' then 1 when 'medium' then 2 else 3 end";

    public string $filter = 'all';

    public bool $showForm = false;
    public string $shift = 'pagi';
    public string $toUserId = '';
    public string $summary = '';
    public string $pendingItems = '';

    public ?int $openId = null;

    public string $notice = '';
    public string $noticeType = 'ok';

    public function updatingFilter(): void
    {
        $this->resetPage();
        $this->openId = null;
    }

    protected function authorizeManage(): void
    {
        abort_unless(auth()->user()?->can('handover.manage'), 403);
    }

    protected function currentShift(): string
    {
        $h = now()->hour;

        return match (true) {
            $h >= 7 && $h < 15 => 'pagi',
            $h >= 15 && $h < 23 => 'siang',
            default => 'malam',
        };
    }

    public function openForm(): void
    {
        $this->authorizeManage();
        $this->reset(['toUserId', 'summary', 'pendingItems']);
        $this->shift = $this->currentShift();
        $this->resetValidation();
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
    }

    public function toggle(int $id): void
    {
        $this->openId = $this->openId === $id ? null : $id;
    }

    /** Kondisi alert dan insiden yang masih terbuka pada saat serah terima. */
    protected function snapshot(): array
    {
        $alertTotal = Alert::open()->count();
        $bySeverity = Alert::open()->selectRaw('severity, count(*) as total')
            ->groupBy('severity')->pluck('total', 'severity')->all();

        $alerts = Alert::open()->with('asset')
            ->orderByRaw(self::SEVERITY_ORDER)->latest('detected_at')->limit(15)->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'title' => $a->title,
                'severity' => $a->severity,
                'status' => $a->status,
                'asset' => $a->asset?->name,
            ])->all();

        $openIncidents = Incident::whereNotIn('status', IncidentOptions::CLOSED_STATUSES);
        $incidentTotal = (clone $openIncidents)->count();

        $incidents = (clone $openIncidents)->with('assignee')
            ->orderByRaw(self::SEVERITY_ORDER)->latest('opened_at')->limit(15)->get()
            ->map(fn ($i) => [
                'id' => $i->id,
                'code' => $i->code,
                'title' => $i->title,
                'severity' => $i->severity,
                'status' => $i->status,
                'assignee' => $i->assignee?->name,
            ])->all();

        return [
            'taken_at' => now()->toIso8601String(),
            'alerts' => ['total' => $alertTotal, 'by_severity' => $bySeverity, 'items' => $alerts],
            'incidents' => ['total' => $incidentTotal, 'items' => $incidents],
        ];
    }

    public function save(): void
    {
        $this->authorizeManage();

        $this->validate([
            'shift' => ['required', Rule::in(array_keys(self::SHIFTS))],
            'toUserId' => ['nullable', 'integer', Rule::exists('users', 'id')->where('is_active', true), 'different:' . auth()->id()],
            'summary' => ['required', 'string', 'max:5000'],
            'pendingItems' => ['nullable', 'string', 'max:5000'],
        ], [
            'summary.required' => 'Ringkasan shift wajib diisi.',
            'toUserId.different' => 'Penerima tidak boleh akun Anda sendiri.',
        ]);

        $recipient = null;
        if ($this->toUserId !== '') {
            $recipient = User::role(['admin', 'analyst'])->find((int) $this->toUserId);
            if (! $recipient) {
                $this->addError('toUserId', 'Penerima harus berperan Administrator atau Analis.');
                return;
            }
        }

        $handover = ShiftHandover::create([
            'from_user_id' => auth()->id(),
            'to_user_id' => $recipient?->id,
            'shift' => $this->shift,
            'summary' => $this->summary,
            'pending_items' => $this->pendingItems !== '' ? $this->pendingItems : null,
            'snapshot' => $this->snapshot(),
        ]);

        activity('handover')->performedOn($handover)->causedBy(auth()->user())
            ->withProperties(['shift' => $this->shift, 'to' => $recipient?->email])
            ->log('Catatan serah terima shift dibuat');

        $this->showForm = false;
        $this->setNotice('Catatan serah terima berhasil disimpan.', 'ok');
    }

    public function acknowledge(int $id): void
    {
        $this->authorizeManage();
        $handover = ShiftHandover::findOrFail($id);
        $me = auth()->id();

        if ($handover->acknowledged_at) {
            return;
        }
        if ($handover->from_user_id === $me) {
            $this->setNotice('Anda tidak dapat menerima catatan yang Anda buat sendiri.', 'err');
            return;
        }
        if ($handover->to_user_id && $handover->to_user_id !== $me) {
            $this->setNotice('Catatan ini ditujukan untuk petugas lain.', 'err');
            return;
        }

        $handover->update([
            'to_user_id' => $handover->to_user_id ?? $me,
            'acknowledged_at' => now(),
        ]);

        activity('handover')->performedOn($handover)->causedBy(auth()->user())
            ->log('Catatan serah terima shift diterima');

        $this->setNotice('Serah terima diterima. Selamat bertugas.', 'ok');
    }

    protected function setNotice(string $text, string $type): void
    {
        $this->notice = $text;
        $this->noticeType = $type;
    }

    public function render()
    {
        $me = auth()->id();

        $query = ShiftHandover::query()->with(['from', 'to']);
        if ($this->filter === 'for_me') {
            $query->where(fn ($q) => $q->where('to_user_id', $me)
                ->orWhere(fn ($q2) => $q2->whereNull('to_user_id')->where('from_user_id', '!=', $me)));
        } elseif ($this->filter === 'from_me') {
            $query->where('from_user_id', $me);
        }

        $waiting = ShiftHandover::with('from')
            ->whereNull('acknowledged_at')
            ->where('from_user_id', '!=', $me)
            ->where(fn ($q) => $q->where('to_user_id', $me)->orWhereNull('to_user_id'))
            ->latest()->get();

        return view('livewire.handover.index', [
            'handovers' => $query->latest()->paginate(10),
            'waiting' => $waiting,
            'preview' => $this->showForm ? $this->snapshot() : null,
            'recipients' => User::role(['admin', 'analyst'])->where('is_active', true)
                ->where('id', '!=', $me)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
