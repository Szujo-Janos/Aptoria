<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('dashboard_layouts');
    }

    public function down(): void
    {
        // The dashboard layout editor was reverted. Do not recreate the removed editor table.
    }
};
