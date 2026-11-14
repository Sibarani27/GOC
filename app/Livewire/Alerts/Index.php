<?php

namespace App\Livewire\Alerts;

use App\Models\Alert;
use App\Models\Incident;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.goc')]
#[Title('Alert')]
class Index extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $severity = '';

    #[Url(except: 'open')]
    public string $status = 'open';

    public ?int $selectedId = null;

    public function updating(string $name): void
    {
        if (in_array($name, ['search', 'severity', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function show(int $id): void
    {
        $this->selectedId = $id;
    }

    public function close(): void
    {
        $this->selectedId = null;
    }

    public function setStatus(int $id, string $status): void
    {
        abort_unless(auth()->user()?->can('alerts.manage'), 403);
        abort_unless(in_array($status, Alert::STATUSES, true), 422);

        $alert = Alert::findOrFail($id);
        $old = $alert->status;

        $data = ['status' => $status];
        if ($status === 'acknowledged') {
            $data['acknowledged_by'] = auth()->id();
        }
        $alert->update($data);

        activity('alert')
            ->performedOn($alert)
            ->causedBy(auth()->user())
            ->withProperties(['from' => $old, 'to' => $status])
            ->log("Status alert diubah dari {$old} ke {$status}");
    }

    public function createIncident(int $id)
    {
        abort_unless(auth()->user()?->can('incidents.manage'), 403);

        $alert = Alert::findOrFail($id);

        $incident = Incident::create([
            'title' => $alert->title,
            'description' => $alert->description,
            'category' => 'other',
            'severity' => $alert->severity,
            'status' => 'open',
            'reporter_id' => auth()->id(),
            'detected_at' => $alert->detected_at,
            'opened_at' => now(),
        ]);

        $incident->alerts()->attach($alert->id);
        $incident->notes()->create([
            'user_id' => auth()->id(),
            'type' => 'status_change',
            'body' => "Insiden dibuat dari alert #{$alert->id} ({$alert->source}).",
        ]);

        if (in_array($alert->status, ['new', 'acknowledged'], true)) {
            $alert->update(['status' => 'in_progress', 'acknowledged_by' => $alert->acknowledged_by ?? auth()->id()]);
        }

        activity('alert')->performedOn($alert)->causedBy(auth()->user())
            ->log("Insiden {$incident->code} dibuat dari alert ini");

        return $this->redirectRoute('goc.incidents.show', ['incident' => $incident->id], navigate: true);
    }

    public function render()
    {
        $query = Alert::query()->with('asset');

        if ($this->status === 'open') {
            $query->open();
        } elseif ($this->status !== '' && $this->status !== 'all') {
            $query->where('status', $this->status);
        }

        if ($this->severity !== '') {
            $query->where('severity', $this->severity);
        }

        if (trim($this->search) !== '') {
            $s = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', $s)
                    ->orWhere('source_ip', 'like', $s)
                    ->orWhere('source', 'like', $s)
                    ->orWhereHas('asset', fn ($a) => $a->where('name', 'like', $s));
            });
        }

        return view('livewire.alerts.index', [
            'alerts' => $query->latest('detected_at')->paginate(15),
            'selected' => $this->selectedId
                ? Alert::with(['asset', 'acknowledger', 'incidents'])->find($this->selectedId)
                : null,
        ]);
    }
}
