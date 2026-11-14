<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // contoh: INC-2026-0001
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->index(); // malware, phishing, ddos, data_leak, dll
            $table->string('severity')->index();
            $table->string('status')->default('open')->index(); // open, investigating, contained, resolved, closed
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('detected_at')->nullable();
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('contained_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('root_cause')->nullable();
            $table->text('resolution')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('incident_alert', function (Blueprint $table) {
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->foreignId('alert_id')->constrained()->cascadeOnDelete();
            $table->primary(['incident_id', 'alert_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_alert');
        Schema::dropIfExists('incidents');
    }
};
