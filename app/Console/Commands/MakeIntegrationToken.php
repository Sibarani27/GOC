<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class MakeIntegrationToken extends Command
{
    protected $signature = 'goc:make-token {source : Nama sumber data, contoh: wazuh}';
    protected $description = 'Buat token API (izin alerts:create) untuk sumber data eksternal';

    public function handle(): int
    {
        $source = Str::slug($this->argument('source'));

        $user = User::firstOrCreate(
            ['email' => "{$source}@integration.local"],
            [
                'name' => 'Integrasi ' . ucfirst($source),
                'password' => Hash::make(Str::random(40)), // tidak untuk login
            ]
        );

        Role::findOrCreate('integration');
        $user->assignRole('integration');

        $token = $user->createToken($source, ['alerts:create'])->plainTextToken;

        $this->info("Token untuk sumber '{$source}' berhasil dibuat.");
        $this->warn('Simpan sekarang, token ini TIDAK akan ditampilkan lagi:');
        $this->line($token);

        return self::SUCCESS;
    }
}
