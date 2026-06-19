<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('project_settings')) {
            Schema::create('project_settings', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->string('key');
                $table->text('value')->nullable();
                $table->timestamps();

                $table->unique(['project_id', 'key']);
            });

            return;
        }

        Schema::table('project_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('project_settings', 'project_id')) {
                $table->foreignId('project_id')->nullable()->constrained()->cascadeOnDelete();
            }
            if (! Schema::hasColumn('project_settings', 'key')) {
                $table->string('key')->default('unknown');
            }
            if (! Schema::hasColumn('project_settings', 'value')) {
                $table->text('value')->nullable();
            }
            if (! Schema::hasColumn('project_settings', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_settings');
    }
};
