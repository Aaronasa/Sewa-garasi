<?php

use App\Enums\JenisPembayaran;
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
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('sewa_id');
            $table->enum('jenis', array_column(JenisPembayaran::cases(), 'value'));
            $table->unsignedInteger('jumlah');
            $table->string('bukti_pembayaran');
            $table->timestamps();

            $table->foreign('sewa_id')->references('id')->on('sewa')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
    }
};
