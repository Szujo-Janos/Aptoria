<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('endpoints', function (Blueprint $table): void {
            $table->json('request_headers')->nullable();
            $table->string('request_body_type', 50)->nullable();
            $table->longText('request_body_preview')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('endpoints', function (Blueprint $table): void {
            $table->dropColumn(['request_headers', 'request_body_type', 'request_body_preview']);
        });
    }
};
