<?php

use App\Enums\TipeSewa;
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
        Schema::create('sewa', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('kendaraan_id');
            $table->unsignedInteger('garasi_id');
            $table->enum('tipe_sewa', array_column(TipeSewa::cases(), 'value'));
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->decimal('sisa_baterai_kwh', 6, 2)->nullable();
            $table->unsignedInteger('biaya_isi_listrik')->default(0);
            $table->unsignedInteger('total_biaya');
            $table->timestamps();

            $table->foreign('kendaraan_id')->references('id')->on('kendaraan')->restrictOnDelete();
            $table->foreign('garasi_id')->references('id')->on('garasi')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sewa');
    }
};
