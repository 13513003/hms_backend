<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateObatPasienTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('obat_pasien', function (Blueprint $table) {
            // $table->increments('id');
			
			$table->integer('id_obat')->unsigned();				
			$table->foreign('id_obat')
				  ->references('id')->on('obat')
                  ->onDelete('restrict');
			
			$table->string('nomor_batch');
			$table->foreign('id_obat')
				  ->references('nomor_batch')->on('obat_masuk')
                  ->onDelete('restrict');
			
			$table->dateTime('waktu_keluar');			
			$table->integer('jumlah');	
			$table->string('keterangan');			
			
			$table->integer('no_transaksi')->unsigned();
			$table->foreign('no_transaksi')
				  ->references('id')->on('transaksi')
                  ->onDelete('restrict');
			
			$table->integer('id_pasien')->unsigned();	
			$table->foreign('id_pasien')
                  ->references('id')->on('pasien')
                  ->onDelete('restrict');		
					
			$table->dateTime('waktu_resep');	
			$table->foreign('waktu_resep')
                  ->references('tanggal_waktu')->on('resep')
                  ->onDelete('restrict');		
					
            $table->timestamps();
			
			$table->primary(['id_obat', 'nomor_batch', 'waktu_keluar']); // Yakin?
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('obat_pasien');
    }
}
