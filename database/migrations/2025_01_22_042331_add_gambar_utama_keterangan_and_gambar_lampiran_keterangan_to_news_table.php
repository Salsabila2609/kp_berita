<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('news', function (Blueprint $table) {
            $table->string('gambar_utama_keterangan')->nullable();  // Kolom untuk keterangan gambar utama
            $table->json('gambar_lampiran_keterangan')->nullable();  // Kolom untuk keterangan gambar lampiran
        });
    }
    
    public function down()
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropColumn('gambar_utama_keterangan');
            $table->dropColumn('gambar_lampiran_keterangan');
        });
    }
    
};
