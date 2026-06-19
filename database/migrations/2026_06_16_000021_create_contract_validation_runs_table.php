<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('contract_validation_runs')) {
            return;
        }

        Schema::create('contract_validation_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('validated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source_name')->nullable();
            $table->string('source_version')->nullable();
            $table->string('openapi_version')->nullable();
            $table->string('status')->default('warning');
            $table->unsignedInteger('documented_operations')->default(0);
            $table->unsignedInteger('inventory_operations')->default(0);
            $table->unsignedInteger('matched_operations')->default(0);
            $table->unsignedInteger('undocumented_inventory_operations')->default(0);
            $table->unsignedInteger('missing_inventory_operations')->default(0);
            $table->unsignedInteger('blocker_count')->default(0);
            $table->unsignedInteger('warning_count')->default(0);
            $table->json('summary_json')->nullable();
            $table->longText('contract_json')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index('validated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_validation_runs');
    }
};
