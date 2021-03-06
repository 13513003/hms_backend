<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateObatPindahTable extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('obat_pindah', function (Blueprint $table) {
      $table->increments('id');

      $table->integer('id_jenis_obat')->unsigned();
      $table->foreign('id_jenis_obat')
            ->references('id')->on('jenis_obat')
            ->onDelete('restrict');             
            
      $table->integer('id_stok_obat_asal')->unsigned();
      $table->foreign('id_stok_obat_asal')
            ->references('id')->on('stok_obat')
            ->onDelete('restrict');   

      $table->integer('id_stok_obat_tujuan')->unsigned()->nullable();
      $table->foreign('id_stok_obat_tujuan')
            ->references('id')->on('stok_obat')
            ->onDelete('restrict');    
	  
			$table->dateTime('waktu_pindah');
			$table->integer('jumlah');	
			$table->string('keterangan')->nullable();	

      $table->integer('asal')->unsigned();                     
      $table->foreign('asal')
            ->references('id')->on('lokasi_obat')
            ->onDelete('restrict');

			$table->integer('tujuan')->unsigned();						
			$table->foreign('tujuan')
	          ->references('id')->on('lokasi_obat')
            ->onDelete('restrict');
	  
      $table->timestamps();						
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
      Schema::dropIfExists('obat_pindah');
  }
}
