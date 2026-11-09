<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('tickets')->orderBy('id')->get(['id', 'user_id', 'created_at'])->each(function ($ticket) {
            $exists = DB::table('ticket_activities')
                ->where('ticket_id', $ticket->id)
                ->where('type', 'created')
                ->exists();

            if ($exists) {
                return;
            }

            DB::table('ticket_activities')->insert([
                'ticket_id' => $ticket->id,
                'user_id' => $ticket->user_id,
                'type' => 'created',
                'description' => 'Ticket aangemaakt',
                'created_at' => $ticket->created_at,
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * This is a data backfill. Removing the generated history would also
     * delete legitimate entries, so there is nothing to roll back.
     */
    public function down(): void
    {
        //
    }
};
