<?php

namespace App\Livewire\Users;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.goc')]
#[Title('Pengguna')]
class Index extends Component
{
    use WithPagination;

    public const ROLES = [
        'admin' => 'Administrator',
        'analyst' => 'Analis',
        'executive' => 'Pimpinan',
    ];

    public string $search = '';
    public string $roleFilter = '';

    public bool $showForm = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $email = '';
    public string $role = 'analyst';
    public string $password = '';
    public string $password_confirmation = '';

    public string $notice = '';
    public string $noticeType = 'ok';

    public function updating(string $name): void
    {
        if (in_array($name, ['search', 'roleFilter'], true)) {
            $this->resetPage();
        }
    }

    protected function authorizeManage(): void
    {
        abort_unless(auth()->user()?->can('users.manage'), 403);
    }

    /** Akun integrasi (token API) tidak dikelola dari halaman ini. */
    protected function baseQuery()
    {
        return User::query()->whereDoesntHave('roles', fn ($r) => $r->where('name', 'integration'));
    }

    protected function activeAdmins(): int
    {
        return User::role('admin')->where('is_active', true)->count();
    }

    protected function killSessions(User $user): void
    {
        if ($user->id !== auth()->id() && Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }
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
        $user = $this->baseQuery()->findOrFail($id);

        $this->resetForm();
        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $role = $user->getRoleNames()->first();
        $this->role = array_key_exists($role, self::ROLES) ? $role : 'analyst';
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorizeManage();

        $pw = Password::min(12)->mixedCase()->numbers();

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingId)],
            'role' => ['required', Rule::in(array_keys(self::ROLES))],
            'password' => $this->editingId ? ['nullable', $pw, 'confirmed'] : ['required', $pw, 'confirmed'],
        ], [
            'password.min' => 'Password minimal 12 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
            'email.unique' => 'Email sudah dipakai akun lain.',
        ]);

        if ($this->editingId) {
            $user = $this->baseQuery()->findOrFail($this->editingId);
            $oldRole = $user->getRoleNames()->first();

            if ($oldRole === 'admin' && $this->role !== 'admin') {
                if ($user->id === auth()->id()) {
                    $this->addError('role', 'Anda tidak dapat menurunkan peran akun Anda sendiri.');
                    return;
                }
                if ($this->activeAdmins() <= 1) {
                    $this->addError('role', 'Harus tersisa minimal satu administrator aktif.');
                    return;
                }
            }

            $user->name = $this->name;
            $user->email = $this->email;
            if ($this->password !== '') {
                $user->password = Hash::make($this->password);
                $this->killSessions($user);
            }
            $user->save();

            if ($oldRole !== $this->role) {
                $user->syncRoles([$this->role]);
            }

            activity('user')->performedOn($user)->causedBy(auth()->user())
                ->withProperties(['role_lama' => $oldRole, 'role_baru' => $this->role, 'password_diganti' => $this->password !== ''])
                ->log("Akun {$user->email} diperbarui");

            $this->notice = "Akun {$user->email} berhasil diperbarui.";
        } else {
            $user = new User();
            $user->name = $this->name;
            $user->email = $this->email;
            $user->password = Hash::make($this->password);
            $user->email_verified_at = now();
            $user->is_active = true;
            $user->save();
            $user->syncRoles([$this->role]);

            activity('user')->performedOn($user)->causedBy(auth()->user())
                ->withProperties(['role' => $this->role])
                ->log("Akun {$user->email} dibuat");

            $this->notice = "Akun {$user->email} berhasil dibuat.";
        }

        $this->noticeType = 'ok';
        $this->closeForm();
    }

    public function toggleActive(int $id): void
    {
        $this->authorizeManage();
        $user = $this->baseQuery()->findOrFail($id);

        if ($user->id === auth()->id()) {
            $this->setNotice('Anda tidak dapat menonaktifkan akun Anda sendiri.', 'err');
            return;
        }

        if ($user->is_active) {
            if ($user->hasRole('admin') && $this->activeAdmins() <= 1) {
                $this->setNotice('Harus tersisa minimal satu administrator aktif.', 'err');
                return;
            }
            $user->is_active = false;
            $user->save();
            $this->killSessions($user);
            $user->tokens()->delete();
            $state = 'dinonaktifkan';
        } else {
            $user->is_active = true;
            $user->save();
            $state = 'diaktifkan kembali';
        }

        activity('user')->performedOn($user)->causedBy(auth()->user())
            ->log("Akun {$user->email} {$state}");

        $this->setNotice("Akun {$user->email} {$state}.", 'ok');
    }

    public function resetTwoFactor(int $id): void
    {
        $this->authorizeManage();
        $user = $this->baseQuery()->findOrFail($id);

        if ($user->id === auth()->id()) {
            $this->setNotice('Untuk akun Anda sendiri, gunakan halaman Keamanan.', 'err');
            return;
        }
        if (! $user->two_factor_confirmed_at && ! $user->two_factor_secret) {
            $this->setNotice('Akun ini belum mengaktifkan 2FA.', 'err');
            return;
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
        $this->killSessions($user);

        activity('user')->performedOn($user)->causedBy(auth()->user())
            ->log("2FA akun {$user->email} direset oleh administrator");

        $this->setNotice("2FA akun {$user->email} direset. Pengguna perlu mengaktifkannya kembali.", 'ok');
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    protected function setNotice(string $text, string $type): void
    {
        $this->notice = $text;
        $this->noticeType = $type;
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'email', 'password', 'password_confirmation']);
        $this->role = 'analyst';
        $this->resetValidation();
    }

    public function render()
    {
        $query = $this->baseQuery()->with('roles');

        if ($this->roleFilter !== '') {
            $query->whereHas('roles', fn ($r) => $r->where('name', $this->roleFilter));
        }

        if (trim($this->search) !== '') {
            $s = '%' . trim($this->search) . '%';
            $query->where(fn ($q) => $q->where('name', 'like', $s)->orWhere('email', 'like', $s));
        }

        return view('livewire.users.index', [
            'users' => $query->orderBy('name')->paginate(15),
        ]);
    }
}
