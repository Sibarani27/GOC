<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',
            'alerts.view', 'alerts.manage',
            'incidents.view', 'incidents.manage', 'incidents.close',
            'assets.view', 'assets.manage',
            'reports.view', 'reports.export',
            'users.manage', 'audit.view',
            'handover.manage',
        ];

        foreach ($permissions as $name) {
            Permission::findOrCreate($name);
        }

        Role::findOrCreate('admin')->syncPermissions($permissions);

        Role::findOrCreate('analyst')->syncPermissions([
            'dashboard.view', 'alerts.view', 'alerts.manage',
            'incidents.view', 'incidents.manage',
            'assets.view', 'reports.view', 'handover.manage',
        ]);

        Role::findOrCreate('executive')->syncPermissions([
            'dashboard.view', 'incidents.view', 'reports.view', 'reports.export',
        ]);

        // Akun admin awal: GANTI email dan password sebelum dipakai!
        $admin = User::firstOrCreate(
            ['email' => 'admin@garuda.local'],
            ['name' => 'Administrator', 'password' => bcrypt('GantiPasswordIni!2026')]
        );
        $admin->assignRole('admin');
    }
}
