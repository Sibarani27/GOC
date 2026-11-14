<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->index(); // server, router, firewall, workstation, dll
            $table->string('ip_address', 45)->nullable()->index();
            $table->string('location')->nullable();
            $table->string('status')->default('unknown')->index(); // online, offline, degraded, unknown
            $table->string('criticality')->default('medium'); // low, medium, high, critical
            $table->timestamp('last_seen_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
