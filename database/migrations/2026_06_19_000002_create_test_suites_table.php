<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('test_suites')) {
            Schema::create('test_suites', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('status', 40)->default('active');
                $table->string('priority', 40)->default('normal');
                $table->string('owner_name')->nullable();
                $table->json('metadata_json')->nullable();
                $table->timestamps();

                $table->index(['project_id', 'status']);
                $table->index(['project_id', 'priority']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('test_suites');
    }
};
