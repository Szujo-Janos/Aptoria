<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finding_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('finding_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 40)->default('qa_note');
            $table->text('body');
            $table->timestamps();

            $table->index(['project_id', 'type']);
            $table->index(['finding_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finding_comments');
    }
};
