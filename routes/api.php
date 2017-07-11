<?php

use Illuminate\Http\Request;
use App\Http\Controllers\LayananController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::resource('pasien', 'PasienController', ['except' => [
  'edit', 'create'
]]);

Route::resource('rekam_medis', 'RekamMedisController', ['except' => [
  'edit', 'create'
]]);

Route::resource('antrian_front_office', 'AntrianFrontOfficeController', ['except' => [
  'edit', 'show', 'create', 'update', 'delete'
]]);
Route::get('antrian_front_office/{kategori_antrian}', 'AntrianFrontOfficeController@show');
Route::put('antrian_front_office/{nama_layanan}/{no_antrian}', 'AntrianFrontOfficeController@update');
Route::delete('antrian_front_office/{nama_layanan}/{no_antrian}', 'AntrianFrontOfficeController@destroy');

Route::resource('antrian', 'AntrianController', ['except' => [
  'edit', 'show', 'create', 'update', 'delete'
]]);
Route::get('antrian/{nama_layanan}', 'AntrianController@show');
Route::put('antrian/{nama_layanan}/{no_antrian}', 'AntrianController@update');
Route::delete('antrian/{nama_layanan}/{no_antrian}', 'AntrianController@destroy');

Route::post('bpjs', 'BpjsController@process');
Route::resource('transaksi', 'TransaksiController');
Route::get('transaksi/search/{nama_pasien}', 'TransaksiController@getRecentTransaksi');

Route::resource('klaim', 'KlaimController');
Route::resource('pembayaran', 'PembayaranController');
Route::resource('asuransi', 'AsuransiController');
Route::get('asuransi/search/{id_pasien}', 'AsuransiController@getAsuransiByIdPasien');

Route::resource('setting_bpjs', 'SettingBpjsController', ['except' => [
  'edit', 'create'
]]);

Route::resource('jaminan', 'JaminanController', ['except' => [
  'edit', 'create'
]]);

Route::resource('cob', 'CobController', ['except' => [
  'edit', 'create'
]]);

Route::resource('daftar_diagnosis', 'DaftarDiagnosisController', ['except' => [
  'edit', 'create'
]]);

Route::resource('diagnosis', 'DiagnosisController', ['except' => [
  'edit', 'create'
]]);

Route::resource('daftar_tindakan', 'DaftarTindakanController', ['except' => [
  'edit', 'create'
]]);

Route::resource('tindakan', 'TindakanController', ['except' => [
  'edit', 'create', 'show', 'update', 'destroy'
]]);

Route::get('tindakan/{no_transaksi}/{no_tindakan?}', 'TindakanController@show');
Route::put('tindakan/{no_transaksi}/{no_tindakan}', 'TindakanController@update');
Route::delete('tindakan/{no_transaksi}/{no_tindakan?}', 'TindakanController@destroy');

Route::resource('poliklinik', 'PoliklinikController', ['except' => [
  'edit', 'create'
]]);

Route::resource('laboratorium', 'LaboratoriumController', ['except' => [
  'edit', 'create'
]]);

Route::resource('hasil_lab', 'HasilLabController', ['except' => [
  'edit', 'create'
]]);

Route::resource('ambulans', 'AmbulansController', ['except' => [
  'edit', 'create'
]]);

Route::resource('tenaga_medis', 'TenagaMedisController', ['except' => [
  'edit', 'create'
]]);

Route::resource('dokter', 'DokterController', ['except' => [
  'edit', 'create'
]]);

Route::get('jadwal_dokter/{nama_poli}/{np_dokter}/{tanggal}', 'JadwalDokterController@show');
Route::put('jadwal_dokter/{nama_poli}/{np_dokter}/{tanggal}', 'JadwalDokterController@update');
Route::delete('jadwal_dokter/{nama_poli}/{np_dokter}/{tanggal}', 'JadwalDokterController@destroy');

Route::resource('jadwal_dokter', 'JadwalDokterController', ['except' => [
  'edit', 'create', 'show', 'update', 'destroy'
]]);

Route::resource('rawatinap', 'KamarRawatInapController', ['except' => [
  'edit', 'create'
]]);


Route::resource('pemakaiankamaroperasi', 'PemakaianKamarOperasiController', ['except' => [
  'edit', 'create'
]]);

Route::resource('pemakaiankamarjenazah', 'PemakaianKamarJenazahController', ['except' => [
  'edit', 'create'
]]);

Route::resource('kamaroperasi', 'KamarOperasiController', ['except' => [
  'edit', 'create'
]]);

Route::resource('kamarjenazah', 'KamarJenazahController', ['except' => [
  'edit', 'create'
]]);

Route::get('rawatinap/{no_kamar}', 'KamarRawatInapController@show');
Route::put('rawatinap/{no_kamar}', 'KamarRawatInapController@update');
Route::post('rawatinap/{no_kamar}', 'PemakaianKamarRawatInapController@store');

Route::put('tempattidur/{no_kamar}/{no_tempat_tidur}', 'TempatTidurController@update');

Route::resource('resep', 'ResepController');
Route::resource('resep_item', 'ResepItemController');
Route::resource('racikan_item', 'RacikanItemController');


Route::get('jenis_obat/search', 'JenisObatController@search');
Route::resource('jenis_obat', 'JenisObatController');
Route::resource('lokasi_obat', 'LokasiObatController');
Route::get('obat_masuk/search', 'ObatMasukController@search');
Route::resource('obat_masuk', 'ObatMasukController');
Route::get('stok_obat/search', 'StokObatController@search');
Route::get('stok_obat/search_by_location', 'StokObatController@searchByLocation');
Route::resource('stok_obat', 'StokObatController');
Route::resource('obat_pindah', 'ObatPindahController');
Route::resource('obat_rusak', 'ObatRusakController');
Route::resource('obat_tebus', 'ObatTebusController');
Route::resource('obat_tindakan', 'ObatTindakanController');
Route::resource('obat_eceran', 'ObatEceranController');
