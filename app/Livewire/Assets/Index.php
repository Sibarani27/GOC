<?php

namespace App\Livewire\Assets;

use App\Models\Asset;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.goc')]
#[Title('Aset')]
class Index extends Component
{
    use WithPagination;

    public const TYPES = ['server', 'router', 'switch', 'firewall', 'workstation', 'iot', 'other'];
    public const STATUSES = ['online', 'offline', 'degraded', 'unknown'];
    public const CRITICALITIES = ['low', 'medium', 'high', 'critical'];

    public string $search = '';

    public bool $showForm = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $type = 'server';
    public string $ip_address = '';
    public string $location = '';
    public string $status = 'unknown';
    public string $criticality = 'medium';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(self::TYPES)],
            'ip_address' => ['nullable', 'ip'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(self::STATUSES)],
            'criticality' => ['required', Rule::in(self::CRITICALITIES)],
        ];
    }

    public function create(): void
    {
        $this->authorizeManage();
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->authorizeManage();
        $asset = Asset::findOrFail($id);

        $this->editingId = $asset->id;
        $this->name = $asset->name;
        $this->type = $asset->type;
        $this->ip_address = $asset->ip_address ?? '';
        $this->location = $asset->location ?? '';
        $this->status = $asset->status;
        $this->criticality = $asset->criticality;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorizeManage();
        $data = $this->validate();
        $data = array_map(fn ($v) => $v === '' ? null : $v, $data);

        if ($this->editingId) {
            $asset = Asset::findOrFail($this->editingId);
            $asset->update($data);
            $action = 'diperbarui';
        } else {
            $asset = Asset::create($data);
            $action = 'ditambahkan';
        }

        activity('asset')->performedOn($asset)->causedBy(auth()->user())
            ->log("Aset {$asset->name} {$action}");

        $this->closeForm();
    }

    public function delete(int $id): void
    {
        $this->authorizeManage();
        $asset = Asset::findOrFail($id);

        activity('asset')->performedOn($asset)->causedBy(auth()->user())
            ->log("Aset {$asset->name} dihapus");

        $asset->delete();
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'ip_address', 'location']);
        $this->type = 'server';
        $this->status = 'unknown';
        $this->criticality = 'medium';
        $this->resetValidation();
    }

    protected function authorizeManage(): void
    {
        abort_unless(auth()->user()?->can('assets.manage'), 403);
    }

    public function render()
    {
        $query = Asset::query();

        if (trim($this->search) !== '') {
            $s = '%' . trim($this->search) . '%';
            $query->where(fn ($q) => $q->where('name', 'like', $s)
                ->orWhere('ip_address', 'like', $s)
                ->orWhere('location', 'like', $s));
        }

        return view('livewire.assets.index', [
            'assets' => $query->orderBy('name')->paginate(15),
        ]);
    }
}
