<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->string('source')->index(); // wazuh, suricata, zabbix, manual
            $table->string('external_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('severity')->index(); // low, medium, high, critical
            $table->string('status')->default('new')->index(); // new, acknowledged, in_progress, resolved, false_positive
            $table->foreignId('asset_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_ip', 45)->nullable()->index();
            $table->string('destination_ip', 45)->nullable();
            $table->string('mitre_tactic')->nullable();
            $table->string('mitre_technique')->nullable();
            $table->string('fingerprint')->nullable()->index(); // untuk menggabungkan alert duplikat
            $table->unsignedInteger('occurrences')->default(1);
            $table->timestamp('detected_at')->index();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->unique(['source', 'external_id']);
            $table->index(['status', 'severity', 'detected_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
