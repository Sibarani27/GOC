<?php

namespace App\Livewire\Incidents;

use App\Models\Incident;
use App\Support\IncidentOptions;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.goc')]
#[Title('Insiden')]
class Index extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $severity = '';

    #[Url(except: 'open')]
    public string $status = 'open';

    public bool $showForm = false;
    public string $formTitle = '';
    public string $formCategory = 'other';
    public string $formSeverity = 'medium';
    public string $formDescription = '';

    public function updating(string $name): void
    {
        if (in_array($name, ['search', 'severity', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function openForm(): void
    {
        abort_unless(auth()->user()?->can('incidents.manage'), 403);
        $this->reset(['formTitle', 'formDescription']);
        $this->formCategory = 'other';
        $this->formSeverity = 'medium';
        $this->resetValidation();
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
    }

    public function createIncident()
    {
        abort_unless(auth()->user()?->can('incidents.manage'), 403);

        $data = $this->validate([
            'formTitle' => ['required', 'string', 'max:255'],
            'formCategory' => ['required', Rule::in(array_keys(IncidentOptions::CATEGORIES))],
            'formSeverity' => ['required', Rule::in(IncidentOptions::SEVERITIES)],
            'formDescription' => ['nullable', 'string', 'max:5000'],
        ]);

        $incident = Incident::create([
            'title' => $data['formTitle'],
            'category' => $data['formCategory'],
            'severity' => $data['formSeverity'],
            'description' => $data['formDescription'] ?: null,
            'status' => 'open',
            'reporter_id' => auth()->id(),
            'detected_at' => now(),
            'opened_at' => now(),
        ]);

        $incident->notes()->create([
            'user_id' => auth()->id(),
            'type' => 'status_change',
            'body' => 'Insiden dibuat secara manual.',
        ]);

        return $this->redirectRoute('goc.incidents.show', ['incident' => $incident->id], navigate: true);
    }

    public function render()
    {
        $query = Incident::query()->with('assignee');

        if ($this->status === 'open') {
            $query->whereNotIn('status', IncidentOptions::CLOSED_STATUSES);
        } elseif ($this->status !== '' && $this->status !== 'all') {
            $query->where('status', $this->status);
        }

        if ($this->severity !== '') {
            $query->where('severity', $this->severity);
        }

        if (trim($this->search) !== '') {
            $s = '%' . trim($this->search) . '%';
            $query->where(fn ($q) => $q->where('title', 'like', $s)->orWhere('code', 'like', $s));
        }

        return view('livewire.incidents.index', [
            'incidents' => $query->latest('opened_at')->paginate(15),
        ]);
    }
}
