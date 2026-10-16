<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('prioriteit')->default('gemiddeld')->after('status');
            $table->foreignId('developer_id')->nullable()->after('prioriteit')->index();
            $table->timestamp('gesloten_op')->nullable()->after('developer_id');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['developer_id']);
            $table->dropColumn(['prioriteit', 'developer_id', 'gesloten_op']);
        });
    }
};
