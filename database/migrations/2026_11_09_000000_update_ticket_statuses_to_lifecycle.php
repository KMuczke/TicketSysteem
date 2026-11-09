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
        // Tickets that were still open but already had an assignee belong to
        // the new "Toegewezen" status.
        DB::table('tickets')
            ->where('status', 'open')
            ->whereNull('assignee_id')
            ->update(['status' => 'nieuw']);

        DB::table('tickets')
            ->where('status', 'open')
            ->whereNotNull('assignee_id')
            ->update(['status' => 'toegewezen']);

        DB::table('ticket_activities')
            ->where('field', 'status')
            ->where('old_value', 'open')
            ->update(['old_value' => 'nieuw']);

        DB::table('ticket_activities')
            ->where('field', 'status')
            ->where('new_value', 'open')
            ->update(['new_value' => 'nieuw']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('tickets')
            ->whereIn('status', ['nieuw', 'toegewezen'])
            ->update(['status' => 'open']);

        DB::table('tickets')
            ->where('status', 'wacht_op_melder')
            ->update(['status' => 'in_behandeling']);

        DB::table('tickets')
            ->where('status', 'opgelost')
            ->update(['status' => 'in_behandeling']);

        DB::table('tickets')
            ->where('status', 'afgewezen')
            ->update(['status' => 'gesloten']);

        DB::table('ticket_activities')
            ->where('field', 'status')
            ->whereIn('old_value', ['nieuw', 'toegewezen'])
            ->update(['old_value' => 'open']);

        DB::table('ticket_activities')
            ->where('field', 'status')
            ->whereIn('new_value', ['nieuw', 'toegewezen'])
            ->update(['new_value' => 'open']);

        DB::table('ticket_activities')
            ->where('field', 'status')
            ->where('old_value', 'wacht_op_melder')
            ->update(['old_value' => 'in_behandeling']);

        DB::table('ticket_activities')
            ->where('field', 'status')
            ->where('new_value', 'wacht_op_melder')
            ->update(['new_value' => 'in_behandeling']);

        DB::table('ticket_activities')
            ->where('field', 'status')
            ->where('old_value', 'opgelost')
            ->update(['old_value' => 'in_behandeling']);

        DB::table('ticket_activities')
            ->where('field', 'status')
            ->where('new_value', 'opgelost')
            ->update(['new_value' => 'in_behandeling']);

        DB::table('ticket_activities')
            ->where('field', 'status')
            ->where('old_value', 'afgewezen')
            ->update(['old_value' => 'gesloten']);

        DB::table('ticket_activities')
            ->where('field', 'status')
            ->where('new_value', 'afgewezen')
            ->update(['new_value' => 'gesloten']);
    }
};
