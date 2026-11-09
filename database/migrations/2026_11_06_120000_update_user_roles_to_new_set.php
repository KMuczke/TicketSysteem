<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('users')->where('role', 'admin')->update(['role' => 'beheerder']);
        DB::table('users')->where('role', 'user')->update(['role' => 'melder']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('melder')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')->where('role', 'beheerder')->update(['role' => 'admin']);
        DB::table('users')->where('role', 'melder')->update(['role' => 'user']);
        DB::table('users')->where('role', 'developer')->update(['role' => 'user']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user')->change();
        });
    }
};
