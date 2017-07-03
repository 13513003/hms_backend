<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ObatRusak;
use App\StokObat;

class ObatRusakController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return ObatRusak::with('obatMasuk','jenisObat','lokasiAsal')->get();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {        
        // TO-DO: Make into transaction?
        // TO-DO: Restriction checking (jumlah > 0 etc.)
        $obat_rusak = new ObatRusak;
        $obat_rusak->id_jenis_obat = $request->input('id_jenis_obat');
        $obat_rusak->id_obat_masuk = $request->input('id_obat_masuk');
        $obat_rusak->waktu_keluar = $request->input('waktu_keluar');
        $obat_rusak->jumlah = $request->input('jumlah');        
        $obat_rusak->alasan = $request->input('alasan');
        $obat_rusak->keterangan = $request->input('keterangan');
        $obat_rusak->asal = $request->input('asal');
        $obat_rusak->save();

        $stok_obat_asal = StokObat::where('id_obat_masuk', $obat_rusak->id_obat_masuk)
                                    ->where('lokasi', $obat_rusak->asal)
                                    ->first(); //TO-DO: Error handling - firstOrFail?
        $stok_obat_asal->jumlah = ($stok_obat_asal->jumlah) - ($obat_rusak->jumlah);
        $stok_obat_asal->save();

        return response ($obat_rusak, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return ObatRusak::with('obatMasuk','jenisObat','lokasiAsal')->findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $obat_rusak = ObatRusak::findOrFail($id);
        $obat_rusak->id_jenis_obat = $request->input('id_jenis_obat');
        $obat_rusak->id_obat_masuk = $request->input('id_obat_masuk');
        $obat_rusak->waktu_keluar = $request->input('waktu_keluar');
        $obat_rusak->jumlah = $request->input('jumlah');        
        $obat_rusak->alasan = $request->input('alasan');
        $obat_rusak->keterangan = $request->input('keterangan');
        $obat_rusak->asal = $request->input('asal');
        $obat_rusak->save();
        return response ($obat_rusak, 200)
            -> header('Content-Type', 'application/json');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $obat_rusak = ObatRusak::find($id);
        $obat_rusak->delete();
        return response ($id.' deleted', 200);
    }
}