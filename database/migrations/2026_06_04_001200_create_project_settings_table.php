<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->text('value')->nullable();
            $table->string('type', 30)->default('string');
            $table->string('group', 80)->default('general');
            $table->timestamps();

            $table->unique(['project_id', 'key']);
            $table->index(['project_id', 'group']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_settings');
    }
};
