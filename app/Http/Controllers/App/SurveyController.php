<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\FamilyMember;
use App\Models\Region;
use App\Models\Respondent;
use App\Models\RespondentDocument;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SurveyController extends Controller
{
    /**
     * Show the survey creation form.
     */
    public function create()
    {
        // Langsung tampilkan Kabupaten/Kota (child dari Provinsi Papua)
        $province = Region::active()->province()->first();
        $cities   = $province ? Region::active()->city()->where('parent_id', $province->id)->orderBy('name')->get() : collect();
        $survey   = Survey::where('is_active', true)->first();
        return view('app.survey.create', compact('cities', 'survey'));
    }

    /**
     * Save as draft or submit.
     */
    public function store(Request $request)
    {
        $request->validate([
            'questionnaire_number' => ['required', 'string', 'unique:survey_responses,questionnaire_number'],
            'region_id'            => ['required', 'exists:regions,id'],
            'interview_date'       => ['required', 'date'],
            'full_name'            => ['required', 'string', 'max:255'],
            'gender'               => ['required', 'in:male,female'],
            'age'                  => ['required', 'integer', 'min:1', 'max:150'],
        ], [
            'questionnaire_number.unique' => 'Nomor kuesioner sudah digunakan.',
            'full_name.required'          => 'Nama lengkap wajib diisi.',
            'gender.required'             => 'Jenis kelamin wajib dipilih.',
            'age.required'                => 'Umur wajib diisi.',
        ]);

        DB::transaction(function () use ($request) {
            // 1. Simpan respondent
            $respondent = Respondent::create([
                'full_name'          => $request->full_name,
                'gender'             => $request->gender,
                'age'                => $request->age,
                'education'          => $request->education,
                'occupation'         => $request->occupation,
                'address'            => $request->address,
                'phone'              => $request->phone,
                'religion'           => $request->religion,
                'ethnicity'          => $request->ethnicity,
                'citizenship_status' => $request->status_oap ?? 'WNI',
                'household_status'   => $request->household_status,
                'region_id'          => $request->region_id,
            ]);

            // 2. Simpan anggota keluarga (B2)
            if ($request->has('rt_nama')) {
                foreach ($request->rt_nama as $i => $nama) {
                    if (empty($nama)) continue;
                    FamilyMember::create([
                        'respondent_id' => $respondent->id,
                        'name'          => $nama,
                        'gender'        => $request->rt_gender[$i] ?? null,
                        'age'           => $request->rt_umur[$i] ?? null,
                        'status'        => $request->rt_status[$i] ?? null,
                        'education'     => $request->rt_edu[$i] ?? null,
                        'ktp_status'    => $request->rt_ktp[$i] ?? null,
                    ]);
                }
            }

            $isDraft = $request->input('action') === 'draft';

            // 3. Simpan survey response
            $survey = Survey::where('is_active', true)->first();
            $response = SurveyResponse::create([
                'survey_id'            => $survey?->id ?? 1,
                'respondent_id'        => $respondent->id,
                'questionnaire_number' => $request->questionnaire_number,
                'surveyor_id'          => auth()->id(),
                'region_id'            => $request->region_id,
                'interview_date'       => $request->interview_date,
                'status'               => $isDraft ? SurveyResponse::STATUS_DRAFT : SurveyResponse::STATUS_SUBMITTED,
                'surveyor_notes'       => $request->surveyor_notes,
                'submitted_at'         => $isDraft ? null : now(),
                'created_by'           => auth()->id(),
                'updated_by'           => auth()->id(),
            ]);

            // 4. Simpan jawaban kuesioner sebagai JSON bulk (satu record per response)
            $answers = $this->extractAnswers($request);
            $answersFiltered = array_filter($answers, fn($v) => $v !== null && $v !== '' && $v !== []);

            if (! empty($answersFiltered)) {
                SurveyAnswer::create([
                    'survey_response_id' => $response->id,
                    'question_id'        => null,
                    'answer_json'        => $answersFiltered,
                ]);
            }

            // 5. Upload dokumen pendukung
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $type => $file) {
                    if (! $file || ! $file->isValid()) continue;
                    $path = $file->store('documents/' . $respondent->id, 'public');
                    RespondentDocument::create([
                        'respondent_id'      => $respondent->id,
                        'survey_response_id' => $response->id,
                        'document_type'      => $type,
                        'file_path'          => $path,
                        'file_name'          => $file->getClientOriginalName(),
                        'mime_type'          => $file->getMimeType(),
                        'file_size'          => $file->getSize(),
                        'is_latest'          => true,
                        'uploaded_by'        => auth()->id(),
                    ]);
                }
            }
        });

        $action = $request->input('action');
        $message = $action === 'draft' ? 'Draft berhasil disimpan.' : 'Survey berhasil disubmit untuk diverifikasi.';

        return redirect()->route('app.lansia.index')
            ->with('success', $message);
    }

    /**
     * Extract all form answers into an organized array.
     */
    private function extractAnswers(Request $request): array
    {
        return [
            // Section D
            'penghasilan'      => $request->penghasilan,

            // Section F
            'pola_konsumsi'    => $request->pola_konsumsi,
            'konsumsi_hari'    => $request->konsumsi_hari,
            'cara_masak'       => $request->cara_masak,
            'bahan_bakar'      => $request->bahan_bakar,
            'bansos_sembako'   => $request->bansos_sembako,
            'pemberi_sembako'  => $request->pemberi_sembako,

            // Section G
            'keluhan_kes'      => $request->keluhan_kes,
            'keluhan_detail'   => $request->keluhan_kes_detail,
            'periksa_rutin'    => $request->periksa_rutin,
            'frek_periksa'     => $request->frek_periksa,
            'jangkau_kes'      => $request->jangkau_kes,
            'transport_kes'    => $request->transport_kes,
            'biaya_kes'        => $request->biaya_kes,
            'masalah_kes'      => $request->masalah_kes,

            // Section H
            'status_rumah'     => $request->status_rumah,
            'jenis_rumah'      => $request->jenis_rumah,
            'sumber_air'       => $request->sumber_air,
            'sistem_air'       => $request->sistem_air,
            'mck'              => $request->mck,
            'penerangan'       => $request->penerangan,
            'lama_penerangan'  => $request->lama_penerangan,

            // Section I
            'media_info'       => $request->media_info,
            'punya_hp'         => $request->punya_hp,
            'media_alternatif' => $request->media_alternatif,

            // Section J
            'bansos'           => $request->bansos,
            'jenis_bansos'     => $request->jenis_bansos,
            'jamsosial'        => $request->jamsosial,
            'jenis_jamsosial'  => $request->jenis_jamsosial,
            'pelatihan_lansia' => $request->pelatihan_lansia,
            'jenis_pelatihan'  => $request->jenis_pelatihan,
            'masalah_linsos'   => $request->masalah_linsos,

            // Section K
            'kunjungi'         => $request->kunjungi,
            'perkumpulan'      => $request->perkumpulan,
            'rapat_warga'      => $request->rapat_warga,
            'pemilu'           => $request->pemilu,

            // Section L
            'pengeluaran_total' => $request->pengeluaran_total,
            'pengeluaran_items' => [
                'Konsumsi (Kebutuhan Pokok)' => $request->input('pengeluaran_konsumsi'),
                'Energi (penerangan & bahan bakar)' => $request->input('pengeluaran_energi'),
                'Pendidikan' => $request->input('pengeluaran_pendidikan'),
                'Kesehatan' => $request->input('pengeluaran_kesehatan'),
                'Acara Sosial/Keagamaan/Adat' => $request->input('pengeluaran_sosial'),
                'Transportasi' => $request->input('pengeluaran_transport'),
            ],
        ];
    }

    /**
     * Get districts by city (AJAX).
     */
    public function getDistricts(Request $request)
    {
        $districts = Region::active()
            ->district()
            ->where('parent_id', $request->city_id)
            ->orderBy('name')
            ->get(['id', 'name']);
        return response()->json($districts);
    }

    /**
     * Get villages by district (AJAX).
     */
    public function getVillages(Request $request)
    {
        $villages = Region::active()
            ->village()
            ->where('parent_id', $request->district_id)
            ->orderBy('name')
            ->get(['id', 'name']);
        return response()->json($villages);
    }
}
