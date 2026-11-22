<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detail_resep', function (Blueprint $table) {
            $table->boolean('stok_terpotong')->default(false)->after('status_tebus');
        });
    }

    public function down(): void
    {
        Schema::table('detail_resep', function (Blueprint $table) {
            $table->dropColumn('stok_terpotong');
        });
    }
};
