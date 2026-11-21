<?php

use App\Enums\TipeGarasi;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('garasi', function (Blueprint $table) {
            $table->increments('id');
            $table->char('kode', 4)->unique();
            $table->enum('tipe', array_column(TipeGarasi::cases(), 'value'));
            $table->unsignedInteger('harga_harian');
            $table->unsignedInteger('harga_listrik_per_kwh')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('garasi');
    }
};
