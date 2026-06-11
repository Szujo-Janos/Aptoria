<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('endpoints', function (Blueprint $table): void {
            $table->string('behavior_role', 40)->nullable()->after('request_body_preview');
            $table->string('behavior_resource', 150)->nullable()->after('behavior_role');
            $table->boolean('destructive_action')->default(false)->after('behavior_resource');
            $table->boolean('auth_boundary')->default(false)->after('destructive_action');
            $table->boolean('sequence_candidate')->default(false)->after('auth_boundary');
            $table->text('behavior_notes')->nullable()->after('sequence_candidate');
        });

        Schema::create('endpoint_behavior_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('producer_endpoint_id')->constrained('endpoints')->cascadeOnDelete();
            $table->foreignId('consumer_endpoint_id')->constrained('endpoints')->cascadeOnDelete();
            $table->string('dependency_type', 50)->default('path_parameter');
            $table->string('resource_key', 150)->nullable();
            $table->string('path_parameter', 100)->nullable();
            $table->unsignedTinyInteger('confidence')->default(50);
            $table->text('suggested_sequence')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'producer_endpoint_id', 'consumer_endpoint_id', 'dependency_type'], 'endpoint_behavior_unique_link');
            $table->index(['project_id', 'dependency_type']);
            $table->index(['project_id', 'confidence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('endpoint_behavior_links');

        Schema::table('endpoints', function (Blueprint $table): void {
            $table->dropColumn([
                'behavior_role',
                'behavior_resource',
                'destructive_action',
                'auth_boundary',
                'sequence_candidate',
                'behavior_notes',
            ]);
        });
    }
};
