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
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('ticket_number')->nullable()->unique()->after('user_id');
        });

        // Give existing tickets a unique, human readable number.
        DB::table('tickets')->orderBy('id')->get(['id'])->each(function ($ticket) {
            DB::table('tickets')->where('id', $ticket->id)->update([
                'ticket_number' => 'TKT-'.str_pad((string) $ticket->id, 4, '0', STR_PAD_LEFT),
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropUnique(['ticket_number']);
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn('ticket_number');
        });
    }
};
