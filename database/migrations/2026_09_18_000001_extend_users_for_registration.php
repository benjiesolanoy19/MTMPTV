<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'username')) {
                $table->string('username')->nullable()->unique()->after('name');
            }
            if (! Schema::hasColumn('users', 'mobile_number')) {
                $table->string('mobile_number', 30)->nullable()->after('email');
            }
            if (! Schema::hasColumn('users', 'address')) {
                $table->text('address')->nullable()->after('mobile_number');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role VARCHAR(30) NOT NULL DEFAULT 'viewer'");
            DB::statement("ALTER TABLE users MODIFY status VARCHAR(30) NOT NULL DEFAULT 'active'");
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'username')) $table->dropUnique(['username']);
            $columns = array_filter(['username', 'mobile_number', 'address'], fn ($column) => Schema::hasColumn('users', $column));
            if ($columns) $table->dropColumn($columns);
        });
    }
};