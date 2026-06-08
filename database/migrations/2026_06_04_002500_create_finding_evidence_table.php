<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finding_evidence', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('finding_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40)->default('note');
            $table->string('source_label', 160)->nullable();
            $table->text('content')->nullable();
            $table->string('url', 1000)->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->index(['finding_id', 'created_at']);
            $table->index(['project_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finding_evidence');
    }
};
