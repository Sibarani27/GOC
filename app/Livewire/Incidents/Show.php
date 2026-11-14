<?php

namespace App\Livewire\Incidents;

use App\Models\Incident;
use App\Models\User;
use App\Support\IncidentOptions;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.goc')]
#[Title('Detail Insiden')]
class Show extends Component
{
    use WithFileUploads;

    public Incident $incident;

    public string $noteBody = '';
    public string $rootCause = '';
    public string $resolution = '';
    public $upload = null;

    public function mount(Incident $incident): void
    {
        $this->incident = $incident;
        $this->rootCause = $incident->root_cause ?? '';
        $this->resolution = $incident->resolution ?? '';
    }

    public function addNote(): void
    {
        $this->authorizeManage();
        $this->validate(['noteBody' => ['required', 'string', 'max:5000']]);

        $this->addTimeline('note', $this->noteBody);
        $this->noteBody = '';
    }

    public function setStatus(string $status): void
    {
        $this->authorizeManage();
        abort_unless(array_key_exists($status, IncidentOptions::STATUSES), 422);

        if ($status === 'closed') {
            abort_unless(auth()->user()?->can('incidents.close'), 403);
        }
        if ($status === $this->incident->status) {
            return;
        }

        $old = $this->incident->status;
        $data = ['status' => $status];

        if ($status === 'contained' && ! $this->incident->contained_at) {
            $data['contained_at'] = now();
        }
        if ($status === 'closed') {
            $data['closed_at'] = now();
        } elseif ($this->incident->closed_at) {
            $data['closed_at'] = null; // dibuka kembali
        }

        $this->incident->update($data);

        $from = IncidentOptions::STATUSES[$old] ?? $old;
        $to = IncidentOptions::STATUSES[$status];
        $this->addTimeline('status_change', "Status diubah dari {$from} ke {$to}.");
    }

    public function assign(string $userId): void
    {
        $this->authorizeManage();

        $assignee = null;
        if ($userId !== '') {
            $assignee = User::role(['admin', 'analyst'])->findOrFail((int) $userId);
        }

        $this->incident->update(['assignee_id' => $assignee?->id]);
        $this->addTimeline('status_change', $assignee
            ? "Penanggung jawab ditetapkan: {$assignee->name}."
            : 'Penanggung jawab dihapus.');
    }

    public function saveResolution(): void
    {
        $this->authorizeManage();
        $data = $this->validate([
            'rootCause' => ['nullable', 'string', 'max:5000'],
            'resolution' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->incident->update([
            'root_cause' => $data['rootCause'] ?: null,
            'resolution' => $data['resolution'] ?: null,
        ]);
        $this->addTimeline('action', 'Akar masalah dan resolusi diperbarui.');
    }

    public function uploadAttachment(): void
    {
        $this->authorizeManage();
        $this->validate([
            'upload' => [
                'required', 'file', 'max:10240',
                'extensions:pdf,png,jpg,jpeg,txt,log,csv,json,zip,pcap,evtx',
            ],
        ]);

        $file = $this->upload;
        $safeName = preg_replace('/[^\w.\- ]+/u', '_', basename($file->getClientOriginalName()));
        $hash = hash_file('sha256', $file->getRealPath());

        // Disimpan di disk privat (bukan folder publik) dengan nama acak
        $path = $file->store("incident-attachments/{$this->incident->id}", 'local');

        $this->incident->attachments()->create([
            'uploaded_by' => auth()->id(),
            'original_name' => $safeName,
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'sha256' => $hash,
        ]);

        $this->addTimeline('action', "Melampirkan bukti: {$safeName}");
        $this->reset('upload');
    }

    protected function addTimeline(string $type, string $body): void
    {
        $this->incident->notes()->create([
            'user_id' => auth()->id(),
            'type' => $type,
            'body' => $body,
        ]);
    }

    protected function authorizeManage(): void
    {
        abort_unless(auth()->user()?->can('incidents.manage'), 403);
    }

    public function render()
    {
        $this->incident->refresh()->load(['alerts.asset', 'attachments.uploader', 'notes.user', 'assignee', 'reporter']);

        return view('livewire.incidents.show', [
            'analysts' => User::role(['admin', 'analyst'])->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
