<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Transaksi;
use App\Pembayaran;
use App\Asuransi;
use App\Pasien;
use App\Klaim;
use App\BpjsManager;
use App\SettingBpjs;
use App\PemakaianKamarRawatinap;
use App\Tindakan;
use App\ObatTebusItem;
use Excel;
use DateTime;
use DateInterval;

class TransaksiController extends Controller
{
    private function getTransaksi($id = null, $field = null, $kode_pasien = null, $nama_pasien = null)
    {
        if (isset($id)) {
          if (isset($field)) {
            if ($field == 'kode_pasien') {
              return Transaksi::with('pasien')
                ->whereHas('pasien', function ($query) use ($id) {
                  $query->where('kode_pasien', '=', $id);
                })
                ->where('status', '=', 'open')
                ->orderBy('transaksi.waktu_masuk_pasien', 'desc')
                ->get();
            }
            else {
              return response('', 500);
            }
          }
          else {
            return Transaksi::with(['pasien', 'tindakan.daftarTindakan', 'rujukan_pasien', 'pembayaran', 'obatTebus.obatTebusItem.jenisObat', 'obatTebus.resep', 'pemakaianKamarRawatInap.kamar_rawatinap', 'pemakaianKamarJenazah'])->findOrFail($id);
          }
        }
        else {
            if (isset($kode_pasien) && isset($field)) {
                return Transaksi::with(['pasien', 'obatTebus.resep', 'pembayaran.klaim'])
                    ->whereHas('pasien', function ($query) use ($kode_pasien) {
                      $query->where('kode_pasien', '=', $kode_pasien);
                    })
                    ->where('status', '=', $field)
                    ->get();
            }
            else {
                if (isset($nama_pasien) && isset($field)) {
                    return Transaksi::with(['pasien', 'obatTebus.resep', 'pembayaran.klaim'])
                        ->whereHas('pasien', function ($query) use ($nama_pasien) {
                          $query->where('nama_pasien', 'like', $nama_pasien.'%');
                        })
                        ->where('status', '=', $field)
                        ->get();
                }
                if (isset($nama_pasien)) {
                    return Transaksi::with(['pasien', 'obatTebus.resep', 'pembayaran.klaim'])
                        ->whereHas('pasien', function ($query) use ($nama_pasien) {
                          $query->where('nama_pasien', 'like', $nama_pasien.'%');
                        })
                        ->get();
                }
                if (isset($kode_pasien)) {
                    return Transaksi::with(['pasien', 'obatTebus.resep', 'pembayaran.klaim'])
                        ->whereHas('pasien', function ($query) use ($kode_pasien) {
                          $query->where('kode_pasien', '=', $kode_pasien);
                        })
                        ->get();
                }

                if (isset($field)) {
                    return Transaksi::with(['pasien', 'obatTebus.resep', 'pembayaran.klaim'])
                        ->where('status', '=', $field)
                        ->get();
                }

                return Transaksi::with(['pasien', 'obatTebus.resep'])
                    ->get();
            }
        }
    }

    public function export(Request $request)
    {
        if ($request->input('tanggal_awal') !== null && $request->input('tanggal_akhir') !== null) {
            $tanggal_awal = new DateTime($request->input('tanggal_awal'));
            $tanggal_akhir = new DateTime($request->input('tanggal_akhir'));
            $tanggal_akhir->add(new DateInterval("P1D")); // Plus 1 day

            $all_transaksi = Transaksi::with(['pembayaran', 'pasien', 'tindakan.daftarTindakan', 'tindakan.pembayaran.klaim', 'rujukan_pasien', 'obatTebus.obatTebusItem.jenisObat', 'obatTebus.resep', 'obatTebus.obatTebusItem.pembayaran.klaim', 'pemakaianKamarRawatInap.kamar_rawatinap', 'pemakaianKamarRawatInap.pembayaran.klaim', 'pemakaianKamarJenazah'])
                ->where('status', '=', 'closed')
                ->whereBetween('waktu_perubahan_terakhir', array($tanggal_awal, $tanggal_akhir))
                ->get();

            $total_pembayaran_non_BPJS = 0;
            $total_pembayaran_BPJS = 0;
            $total_tarif_klaim = 0;
            $total_surplus_klaim = 0;

            $data = array(
                array()
            );

            foreach ($all_transaksi as $transaksi) {
                $pembayaran_non_BPJS = 0;
                $pembayaran_BPJS = 0;
                $tarif_klaim = 0;
                $surplus_klaim = 0;

                if (!empty($transaksi->tindakan)) {
                    array_push($data, array('Tindakan'));
                    array_push($data, array('Nama', '', '', 'Harga Tindakan', 'Metode Bayar'));
                    foreach ($transaksi->tindakan as $tindakan) {
                        $array = array(
                            $tindakan->daftarTindakan->nama,
                            '',
                            '',
                            $tindakan->harga,
                            $tindakan->pembayaran->metode_bayar
                        );
                        array_push($data, $array);
                    }
                }
                array_push($data, array());

                if (!empty($transaksi->obatTebus)) {
                    array_push($data, array('Obat'));
                    array_push($data, array('Nama', '', '', 'Harga Total', 'Metode Bayar'));
                    foreach ($transaksi->obatTebus as $obatTebus) {
                        foreach ($obatTebus->obatTebusItem as $obat) {
                            $array = array(
                                $obat->jenis_obat->merek_obat,
                                '',
                                '',
                                $obat->jumlah * $obat->harga_jual_realisasi,
                                $obat->pembayaran->metode_bayar
                            );
                            array_push($data, $array);
                        }
                    }
                }
                array_push($data, array());

                if (!empty($transaksi->pemakaianKamarRawatInap)) {
                    array_push($data, array('Kamar Rawat Inap'));
                    array_push($data, array('Kelas Kamar', '', '', 'Harga Total', 'Metode Bayar'));
                    foreach ($transaksi->pemakaianKamarRawatInap as $pemakaianKamar) {
                        $waktuMasuk = Carbon::parse($pemakaianKamar->waktu_masuk);
                        $waktuKeluar = Carbon::parse($pemakaianKamar->waktu_keluar);
                        $los = $waktuMasuk->diffInDays($waktuKeluar);
                        $harga = $los * $pemakaianKamar->kamar_rawatinap->harga_per_hari;
                        $array = array(
                            $pemakaianKamar->kamar_rawatinap->jenis_kamar.' Kelas '.$pemakaianKamar->kamar_rawatinap->kelas,
                            '',
                            '',
                            $harga,
                            $pemakaianKamar->pembayaran->metode_bayar
                        );
                        array_push($data, $array);
                    }
                }
                array_push($data, array());

                foreach ($transaksi->pembayaran as $pembayaran) {
                    if ($pembayaran->metode_bayar == 'bpjs') {
                        $pembayaran_BPJS = $pembayaran->harga_bayar;
                        $total_pembayaran_BPJS += $pembayaran->harga_bayar;

                        if ($pembayaran->klaim != null && $pembayaran->klaim->tarif != null) {
                            $tarif_klaim = $pembayaran->klaim->tarif;
                            $total_tarif_klaim += $pembayaran->klaim->tarif;

                            $surplus_klaim = $tarif_klaim - $pembayaran_BPJS;
                            $total_surplus_klaim += $surplus_klaim;
                        }
                    }
                    else {
                        $pembayaran_non_BPJS = $pembayaran->harga_bayar;
                        $total_pembayaran_non_BPJS += $pembayaran->harga_bayar;
                        if ($pembayaran->pembayaran_tambahan == 1) {
                            array_push($data, array('Biaya Naik Kelas', '', '', $pembayaran_non_BPJS));
                        }
                    }
                }
            }

            $total_array = array('Total Pembayaran Non-BPJS', '', '', $total_pembayaran_non_BPJS);
            array_push($data, $total_array);

            $total_array = array('Total Pembayaran BPJS', '', '', $total_pembayaran_BPJS);
            array_push($data, $total_array);

            $total_array = array('Total Tarif Klaim', '', '', $total_tarif_klaim);
            array_push($data, $total_array);

            $total_array = array('Total Surplus Klaim', '', '', $total_surplus_klaim);
            array_push($data, $total_array);

            $tanggal_awal = $tanggal_awal->format('Y/m/d');
            $tanggal_akhir = $tanggal_akhir->format('Y/m/d');
            $title = "Rekap Transaksi ".$tanggal_awal." - ".$tanggal_akhir;
            return Excel::create($title, function($excel) use ($data) {
                $excel->setTitle('Rekap Transaksi')
                        ->setCreator('user')
                        ->setCompany('RSUD Payakumbuh')
                        ->setDescription('Rekapitulasi Transaksi');
                $excel->sheet('Sheet1', function($sheet) use ($data) {
                    $sheet->fromArray($data);
                });
            })->download('xls');
        }

        return response()->json([
            'code' => '400',
            'message' => 'Malformed Request'
        ], 400);
    }

    public function getRecentTransaksi($nama_pasien)
    {
        $transaksi = Transaksi
                            ::join('pasien', 'transaksi.id_pasien', '=', 'pasien.id')
                            ->orderBy('transaksi.waktu_masuk_pasien', 'desc')
                            ->where('nama_pasien', '=', $nama_pasien)
                            ->select(DB::raw('transaksi.*'))
                            ->get();
        return $transaksi;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $status = $request->input('status');
        $kode_pasien = $request->input('kode_pasien');
        $nama_pasien = $request->input('nama_pasien');
        return response()->json([
            'allTransaksi' => $this->getTransaksi(null, $status, $kode_pasien, $nama_pasien)
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $payload = $request->input('transaksi');
        $transaksi = new Transaksi;
        $transaksi->id_pasien = $payload['id_pasien'];
        $transaksi->rujukan = $payload['rujukan'];

        $transaksiLama = Transaksi::with(['pasien', 'tindakan.daftarTindakan', 'rujukan_pasien', 'pembayaran', 'obatTebus.obatTebusItem.jenisObat', 'obatTebus.resep', 'pemakaianKamarRawatInap.kamar_rawatinap', 'pemakaianKamarJenazah'])
            ->where('id_pasien', '=', $transaksi->id_pasien)
            ->where('status', '=', 'open')
            ->first();

        if ($transaksiLama != null) {
            $tutup = true;
            if (!isset($transaksiLama->no_sep)) {
                foreach ($transaksiLama->tindakan as $tindakan) {
                    if ($tindakan->id_pembayaran === null) {
                        $tutup = false;
                    }
                }

                foreach ($transaksiLama->pemakaianKamarRawatInap as $pemakaian) {
                    if ($pemakaian->id_pembayaran === null) {
                        $tutup = false;
                    }
                }

                foreach ($transaksiLama->obatTebus as $obatTebus) {
                    foreach ($obatTebus->obatTebusItem as $obatItem) {
                        if ($obatItem->id_pembayaran === null) {
                            $tutup = false;
                        }
                    }
                }
            }

            if ($tutup) {
                $transaksiLama->status = 'closed';
                $transaksiLama->save();
                if ($transaksiLama->status == 'closed' && isset($transaksiLama->no_sep)) {
                    try {
                        $coder_nik = SettingBpjs::first()->coder_nik;
                        $bpjs =  new BpjsManager($transaksiLama->no_sep, $coder_nik);
                        $response = json_decode($bpjs->group(1)->getBody(), true);

                        $special_cmg = '';
                        if ($response['metadata']['code'] == 200) {
                            if (isset($response['special_cmg_option'])) {
                                foreach ($response['special_cmg_option'] as $key => $value) {
                                    if (substr($value['code'], 1) != 'D') {
                                        $special_cmg = $special_cmg . "#" . $value['code'];
                                    }
                                    else {
                                        $name = explode(" ", $value['description']);
                                        foreach ($transaksiLama['obat_tebus']['obat_tebus_item'] as $key_obat => $obat) {
                                            if (strtolower($obat['jenis_obat']['nama_generik']) == strtolower($name[0])) {
                                                $special_cmg = $special_cmg . "#" . $value['code'];
                                            }
                                        }
                                    }
                                }
                            }
                            $bpjs->group(2, $special_cmg);
                            $bpjs->finalizeClaim();
                        }
                    }
                    catch(Exception $e) {
                        $transaksiLama->status = 'open';
                        $transaksiLama->save();
                    }
                }
            }
            else {
                return response()->json([
                    'code' => 500,
                    'message' => 'Pasien Memiliki Transaksi Yang Belum Diselesaikan'
                ], 202);
            }
        }

        $transaksi->kode_jenis_pasien = $payload['kode_jenis_pasien']; //1: pasien umum, 2: pasien asuransi

        if ($transaksi->kode_jenis_pasien == 2) {
            $transaksi->asuransi_pasien = $payload['asuransi_pasien'];
        }
        else {
            $transaksi->asuransi_pasien = 'tunai';
        }

        $transaksi->harga_total = 0;
        $transaksi->jenis_rawat = $payload['jenis_rawat']; //1: rawat inap, 2: rawat jalan

        if ($transaksi->jenis_rawat == 2) {
            $transaksi->kelas_rawat = 3;
        }
        else {
            $transaksi->kelas_rawat = $payload['kelas_rawat']; //kelas perawatan saat pasien mendaftar
        }
        $transaksi->status_naik_kelas = 0; //0: pasien tidak naik kelas, 1: pasien naik kelas
        $transaksi->status = 'open'; //status transaksi (open/closed)
        $transaksi->save();

        $newClaimResponse = '';
        $setClaimResponse = '';
        if (isset($payload['no_sep']) && $transaksi->kode_jenis_pasien == 2 && $transaksi->asuransi_pasien
             == 'bpjs') {
            $transaksi->no_sep = $payload['no_sep'];
            $transaksi->save();

            $settingBpjs = SettingBpjs::first();
            $coder_nik = $settingBpjs->coder_nik;
            $bpjs =  new BpjsManager($transaksi->no_sep, $coder_nik);

            $asuransi = Asuransi::where('id_pasien', $transaksi->id_pasien)->where('nama_asuransi', 'bpjs')->first();
            $pasien = Pasien::findOrFail($transaksi->id_pasien);
            $requestNew = array(
                'nomor_kartu' => $asuransi->no_kartu,
                'nomor_rm' => $asuransi->id_pasien,
                'nama_pasien' => $pasien->nama_pasien,
                'tgl_lahir' => $pasien->tanggal_lahir,
                'gender' => $pasien->jender
            );

            $newClaimResponse = $bpjs->newClaim($requestNew);

            $carbon = Carbon::instance($transaksi->waktu_masuk_pasien)->format('Y-m-d H:i:s');
            $requestSet = array(
                'nomor_kartu' => $asuransi->no_kartu,
                'tgl_masuk' => $carbon,
                'jenis_rawat' => $transaksi->jenis_rawat,
                'kelas_rawat' => $transaksi->kelas_rawat,
                'upgrade_class_ind' => $transaksi->status_naik_kelas,
                'tarif_rs' => $settingBpjs->tarif_rs,
                'kode_tarif' => $settingBpjs->kd_tarif_rs,
                'nama_dokter' => 'RUDY, DR',
                'payor_id' => 3,
                'payor_cd' => 'JKN'
            );
            \Log::info($requestSet);
            $setClaimResponse = $bpjs->setClaimData($requestSet);
            $setClaimResponse = "Set Claim";
        }

        $transaksi = Transaksi::findOrFail($transaksi->id);
        $code_str = strtoupper(base_convert($transaksi->id, 10, 36));
        $code_str = str_pad($code_str, 8, '0', STR_PAD_LEFT);
        $transaksi->no_transaksi = 'INV' . $code_str;
        $transaksi->save();

        return response()->json([
            'transaksi' => $transaksi,
            'new_claim' => $newClaimResponse,
            'set_claim' => $setClaimResponse
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, $field = null)
    {
        return response()->json([
          'transaksi' => $this->getTransaksi($id, $field)
        ]);
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
        $payload = $request->input('transaksi');
        $transaksi = Transaksi::findOrFail($id);
        $transaksi->update($payload);

        if ($transaksi->status == 'closed' && isset($transaksi->no_sep)) {
            $transaksi = Transaksi::with(['pasien', 'tindakan.daftarTindakan', 'obatTebus.obatTebusItem.jenisObat', 'obatTebus.resep', 'pemakaianKamarRawatInap.kamar_rawatinap', 'pembayaran'])
                ->findOrFail($id);
            try {
                $coder_nik = SettingBpjs::first()->coder_nik;
                $bpjs =  new BpjsManager($transaksi->no_sep, $coder_nik);
                $response = json_decode($bpjs->group(1)->getBody(), true);

                $special_cmg = '';
                if ($response['metadata']['code'] == 200) {
                    if (isset($response['special_cmg_option'])) {
                        foreach ($response['special_cmg_option'] as $key => $value) {
                            if (substr($value['code'], 1) != 'D') {
                                $special_cmg = $special_cmg . "#" . $value['code'];
                            }
                            else {
                                $name = explode(" ", $value['description']);
                                foreach ($transaksi['obat_tebus']['obat_tebus_item'] as $key_obat => $obat) {
                                    if (strtolower($obat['jenis_obat']['nama_generik']) == strtolower($name[0])) {
                                        $special_cmg = $special_cmg . "#" . $value['code'];
                                    }
                                }
                            }
                        }
                    }
                    $bpjs->group(2, $special_cmg);
                    $bpjs->finalizeClaim();
                }
            }
            catch(Exception $e) {
                $transaksi->status = 'open';
                $transaksi->save();
            }
        }

        return response()->json([
            'transaksi' => $transaksi
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Transaksi::destroy($id);
    }

    public function searchByPasien(Request $request)
    {
        $transaksi = Transaksi::where('id_pasien', $request->input('id_pasien'))
                                ->get();
        return response ($transaksi, 200)
                -> header('Content-Type', 'application/json');
    }

    public function getLatestOpenTransaksi($id_pasien)
    {
        $transaksi = Transaksi::where('id_pasien', $id_pasien)
                            ->where('status', '=', 'open')
                            ->orderBy('transaksi.waktu_masuk_pasien', 'desc')
                            ->firstOrFail();
        return response ($transaksi, 200)
                -> header('Content-Type', 'application/json');
    }

    public function getStatusBpjs($id)
    {
        $pemakaianKamarRawatinap = PemakaianKamarRawatinap::with('kamar_rawatinap')
            ->where('id_transaksi', '=', $id)
            ->where('waktu_keluar', '=', null)
            ->first();

        $transaksi = Transaksi::findOrFail($id);
        $status_bpjs = null;

        if ($transaksi->no_sep != null) {
            $settingBpjs = SettingBpjs::first();
            $coder_nik = $settingBpjs->coder_nik;
            $bpjs =  new BpjsManager($transaksi->no_sep, $coder_nik);

            if ($pemakaianKamarRawatinap != null && $transaksi->status != 'closed') {
                $kelas_sekarang = $pemakaianKamarRawatinap->kamar_rawatinap->kelas;
                $kamar_sekarang = $pemakaianKamarRawatinap->kamar_rawatinap->jenis_kamar;
                $pemakaian_array = PemakaianKamarRawatinap::with('kamar_rawatinap')
                    ->where('id_transaksi', '=', $id)
                    ->whereHas('kamar_rawatinap', function ($query) use ($kamar_sekarang) {
                        $query->where('jenis_kamar', '=', $kamar_sekarang);
                    })
                    ->whereHas('kamar_rawatinap', function ($query) use ($kelas_sekarang) {
                        $query->where('kelas', '=', $kelas_sekarang);
                    })
                    ->get();
                
                $kamar = $pemakaianKamarRawatinap->kamar_rawatinap;

                $carbon = Carbon::parse($transaksi->waktu_masuk_pasien);
                $waktuMasuk = Carbon::parse($pemakaianKamarRawatinap->waktu_masuk);
                $waktuKeluar = Carbon::now('Asia/Jakarta');

                $los = 0;

                if ($waktuMasuk->diffInHours($waktuKeluar) > 2) {
                    $los = $waktuMasuk->diffInDays($waktuKeluar);
                }

                foreach ($pemakaian_array as $pemakaian) {
                    if ($pemakaian->waktu_keluar != null) {
                        $waktuPemakaianMasuk = Carbon::parse($pemakaian->waktu_masuk);
                        $waktuPemakaianKeluar = Carbon::parse($pemakaian->waktu_keluar);
                        $los += $waktuPemakaianMasuk->diffInDays($waktuPemakaianKeluar);
                    }
                }


                if ($transaksi->status_naik_kelas == 1 && $kamar->jenis_kamar != "ICU") {
                    $kelas = "kelas_";
                    if ($kamar->kelas == "vip") {
                        $kelas = "vip";
                    }
                    else {
                        $kelas = $kelas . $kamar->kelas;
                    }

                    $requestSet = array(
                        'upgrade_class_ind' => $transaksi->status_naik_kelas,
                        'upgrade_class_class' => $kelas,
                        'upgrade_class_los' => $los,
                        'add_payment_pct' => $settingBpjs->add_payment_pct
                    );
                    $bpjs->setClaimData($requestSet);
                }
                else {
                    if ($kamar->jenis_kamar == "ICU") {
                        $currentData = json_decode($bpjs->getClaimData()->getBody(), true);
                        $currentIcuLos = $currentData['response']['data']['icu_los'];

                        $requestSet = array(
                            'tgl_masuk' => $carbon->toDateTimeString(),
                            'tgl_pulang' => $waktuKeluar->toDateTimeString(),
                            'icu_indikator' => 1,
                            'icu_los' => $los
                        );
                        $bpjs->setClaimData($requestSet);
                    }
                }

                $requestSet = array(
                    'tgl_pulang' => $waktuKeluar->toDateTimeString()
                );
                $bpjs->setClaimData($requestSet);
                $bpjs->group(1);

            }
            $currentData = json_decode($bpjs->getClaimData()->getBody(), true);
            $status_bpjs = $currentData['response']['data'];
        }

        return response()->json([
            'status_bpjs' => $status_bpjs
        ], 200);
    }
}
