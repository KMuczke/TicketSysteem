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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('naam')->unique();
            $table->string('slug')->unique();
            $table->string('kleur')->default('sky');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->string('naam')->unique();
            $table->string('url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();

        DB::table('categories')->insert([
            [
                'naam' => 'Bug',
                'slug' => 'bug',
                'kleur' => 'red',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'naam' => 'Storing',
                'slug' => 'storing',
                'kleur' => 'amber',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'naam' => 'Wijzigingsverzoek',
                'slug' => 'wijzigingsverzoek',
                'kleur' => 'purple',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'naam' => 'Vraag',
                'slug' => 'vraag',
                'kleur' => 'sky',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'naam' => 'Toegang/account',
                'slug' => 'toegang_account',
                'kleur' => 'emerald',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
        Schema::dropIfExists('categories');
    }
};
