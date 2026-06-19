<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('role')->default('admin');
                $table->string('locale', 8)->default('en');
                $table->string('timezone')->default('Europe/Budapest');
                $table->boolean('password_change_required')->default(false);
                $table->timestamp('first_login_at')->nullable();
                $table->timestamp('last_login_at')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        } else {
            Schema::table('users', function (Blueprint $table): void {
                if (! Schema::hasColumn('users', 'role')) {
                    $table->string('role')->default('admin')->after('password');
                }
                if (! Schema::hasColumn('users', 'locale')) {
                    $table->string('locale', 8)->default('en')->after('role');
                }
                if (! Schema::hasColumn('users', 'timezone')) {
                    $table->string('timezone')->default('Europe/Budapest')->after('locale');
                }
                if (! Schema::hasColumn('users', 'password_change_required')) {
                    $table->boolean('password_change_required')->default(false)->after('timezone');
                }
                if (! Schema::hasColumn('users', 'first_login_at')) {
                    $table->timestamp('first_login_at')->nullable()->after('password_change_required');
                }
                if (! Schema::hasColumn('users', 'last_login_at')) {
                    $table->timestamp('last_login_at')->nullable()->after('first_login_at');
                }
                if (! Schema::hasColumn('users', 'remember_token')) {
                    $table->rememberToken();
                }
            });
        }

        if (! Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table): void {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
