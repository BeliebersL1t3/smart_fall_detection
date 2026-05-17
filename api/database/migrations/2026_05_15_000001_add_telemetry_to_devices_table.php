<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->float('last_magnitude')->nullable()->after('battery_level');
            $table->float('last_ax')->nullable()->after('last_magnitude');
            $table->float('last_ay')->nullable()->after('last_ax');
            $table->float('last_az')->nullable()->after('last_ay');
            $table->string('last_status')->default('normal')->after('last_az');
            $table->timestamp('last_seen_at')->nullable()->after('last_status');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn([
                'last_magnitude', 'last_ax', 'last_ay', 'last_az',
                'last_status', 'last_seen_at',
            ]);
        });
    }
};
