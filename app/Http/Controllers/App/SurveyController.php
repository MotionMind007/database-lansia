<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\StoreSurveyRequest;
use App\Http\Requests\App\UpdateSurveyRequest;
use App\Jobs\SyncDashboardFacts;
use App\Models\FamilyMember;
use App\Models\Region;
use App\Models\Respondent;
use App\Models\RespondentDocument;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyResponse;
use App\Support\SecureUploadStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SurveyController extends Controller
{
    /**
     * Show the survey creation form.
     */
    public function create()
    {
        // Langsung tampilkan Kabupaten/Kota (child dari Provinsi Papua)
        $province = Region::active()->province()->first();
        $cities = $province ? Region::active()->city()->where('parent_id', $province->id)->orderBy('name')->get() : collect();
        $survey = Survey::where('is_active', true)->first();

        return view('app.survey.create', compact('cities', 'survey'));
    }

    /**
     * Save as draft or submit.
     */
    public function store(StoreSurveyRequest $request)
    {
        $survey = $this->activeSurvey();

        $response = DB::transaction(function () use ($request, $survey) {
            // 1. Simpan respondent
            $respondent = Respondent::create([
                'full_name' => $request->full_name,
                'gender' => $request->gender,
                'age' => $request->age,
                'education' => $request->education,
                'occupation' => $request->occupation,
                'address' => $request->address,
                'phone' => $request->phone,
                'religion' => $request->religion,
                'ethnicity' => $request->ethnicity,
                'citizenship_status' => $request->status_oap ?? 'WNI',
                'household_status' => $request->household_status,
                'region_id' => $request->region_id,
            ]);

            // Upload foto profil
            $this->storeProfilePhoto($request, $respondent);

            // 2. Simpan anggota keluarga (B2)
            if ($request->has('rt_nama')) {
                foreach ($request->rt_nama as $i => $nama) {
                    if (empty($nama)) {
                        continue;
                    }
                    $gender = $request->rt_gender[$i] ?? null;
                    if ($gender === 'Laki-laki') $gender = 'male';
                    elseif ($gender === 'Perempuan') $gender = 'female';

                    // Pendidikan dari C3 (edu_1, edu_2, ...) — index 1-based
                    $eduKey = 'edu_' . ($i + 1);
                    $education = $request->input($eduKey);

                    // KTP dari E6 (ktp_1, ktp_2, ...) — index 1-based
                    $ktpKey = 'ktp_' . ($i + 1);
                    $ktpStatus = $this->normalizeKtpStatus($request->input($ktpKey));

                    $occupationKey = 'pek_' . ($i + 1);
                    $occupation = $request->input($occupationKey);

                    FamilyMember::create([
                        'respondent_id' => $respondent->id,
                        'name' => $nama,
                        'gender' => $gender,
                        'age' => $request->rt_umur[$i] ?? null,
                        'status' => $request->rt_status[$i] ?? null,
                        'education' => $education,
                        'occupation' => $occupation,
                        'ktp_status' => $ktpStatus,
                    ]);
                }
            }

            $isDraft = $request->input('action') === 'draft';

            // 3. Simpan survey response
            $response = SurveyResponse::create([
                'survey_id' => $survey->id,
                'respondent_id' => $respondent->id,
                'questionnaire_number' => $request->questionnaire_number,
                'surveyor_id' => auth()->id(),
                'region_id' => $request->region_id,
                'interview_date' => $request->interview_date,
                'status' => $isDraft ? SurveyResponse::STATUS_DRAFT : SurveyResponse::STATUS_SUBMITTED,
                'surveyor_notes' => $request->surveyor_notes,
                'submitted_at' => $isDraft ? null : now(),
            ]);

            $response->forceFill([
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ])->save();

            // 4. Simpan jawaban kuesioner sebagai JSON bulk (satu record per response)
            $answers = $this->extractAnswers($request);
            $answersFiltered = array_filter($answers, fn ($v) => $v !== null && $v !== '' && $v !== []);

            if (! empty($answersFiltered)) {
                SurveyAnswer::create([
                    'survey_response_id' => $response->id,
                    'question_id' => null,
                    'answer_json' => $answersFiltered,
                ]);
            }

            // 5. Upload dokumen pendukung
            $this->storeSupportDocuments($request, $respondent, $response);

            return $response;
        });

        SyncDashboardFacts::dispatch($response->id);

        $action = $request->input('action');
        $message = $action === 'draft' ? 'Draft berhasil disimpan.' : 'Survey berhasil disubmit untuk diverifikasi.';

        activity('survey')
            ->causedBy(auth()->user())
            ->performedOn($response)
            ->event($action === 'draft' ? 'survey_draft_created' : 'survey_submitted')
            ->withProperties([
                'questionnaire_number' => $response->questionnaire_number,
                'region_id' => $response->region_id,
                'status' => $response->status,
            ])
            ->log($action === 'draft' ? 'Draft survey dibuat.' : 'Survey disubmit.');

        return redirect()->route('app.lansia.index')
            ->with('success', $message);
    }

    /**
     * Show the survey edit form (for revision).
     */
    public function edit($id)
    {
        $response = SurveyResponse::with(['respondent.familyMembers', 'answers', 'region.parent.parent', 'surveyor'])->findOrFail($id);

        // Only allow edit if status is need_revision or draft, and user is the surveyor or admin
        $user = auth()->user();
        if (! in_array($response->status, [SurveyResponse::STATUS_NEED_REVISION, SurveyResponse::STATUS_DRAFT])) {
            abort(403, 'Data ini tidak dapat direvisi.');
        }

        if (! $user->hasRole('administrator') && $response->surveyor_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses untuk merevisi data ini.');
        }

        $province = Region::active()->province()->first();
        $cities = $province ? Region::active()->city()->where('parent_id', $province->id)->orderBy('name')->get() : collect();
        $survey = Survey::where('is_active', true)->first();

        $respondent = $response->respondent;
        $answers = $response->answers->first()?->answer_json ?? [];

        // Get region hierarchy for pre-selecting dropdowns
        $region = $response->region;
        $selectedVillage = $region;
        $selectedDistrict = $region?->parent;
        $selectedCity = $selectedDistrict?->parent;

        $districts = $selectedCity ? Region::active()->district()->where('parent_id', $selectedCity->id)->orderBy('name')->get() : collect();
        $villages = $selectedDistrict ? Region::active()->village()->where('parent_id', $selectedDistrict->id)->orderBy('name')->get() : collect();

        return view('app.survey.edit', compact(
            'response', 'respondent', 'answers', 'cities', 'districts', 'villages',
            'survey', 'selectedCity', 'selectedDistrict', 'selectedVillage'
        ));
    }

    /**
     * Update the survey (revision).
     */
    public function update(UpdateSurveyRequest $request, $id)
    {
        $response = SurveyResponse::with(['respondent.familyMembers', 'answers'])->findOrFail($id);

        $user = auth()->user();
        if (! in_array($response->status, [SurveyResponse::STATUS_NEED_REVISION, SurveyResponse::STATUS_DRAFT])) {
            abort(403, 'Data ini tidak dapat direvisi.');
        }

        if (! $user->hasRole('administrator') && $response->surveyor_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses untuk merevisi data ini.');
        }

        DB::transaction(function () use ($request, $response) {
            $respondent = $response->respondent;

            // 1. Update respondent
            $respondent->update([
                'full_name' => $request->full_name,
                'gender' => $request->gender,
                'age' => $request->age,
                'education' => $request->education,
                'occupation' => $request->occupation,
                'address' => $request->address,
                'phone' => $request->phone,
                'religion' => $request->religion,
                'ethnicity' => $request->ethnicity,
                'citizenship_status' => $request->status_oap ?? $respondent->citizenship_status,
                'household_status' => $request->household_status,
                'region_id' => $request->region_id,
            ]);

            // Upload foto profil baru (jika ada)
            $this->storeProfilePhoto($request, $respondent);

            // 2. Update anggota keluarga (hapus lama, buat baru)
            $respondent->familyMembers()->delete();
            if ($request->has('rt_nama')) {
                foreach ($request->rt_nama as $i => $nama) {
                    if (empty($nama)) {
                        continue;
                    }
                    $gender = $request->rt_gender[$i] ?? null;
                    if ($gender === 'Laki-laki') $gender = 'male';
                    elseif ($gender === 'Perempuan') $gender = 'female';

                    // Pendidikan dari C3 (edu_1, edu_2, ...) — index 1-based
                    $eduKey = 'edu_' . ($i + 1);
                    $education = $request->input($eduKey);

                    // KTP dari E6 (ktp_1, ktp_2, ...) — index 1-based
                    $ktpKey = 'ktp_' . ($i + 1);
                    $ktpStatus = $this->normalizeKtpStatus($request->input($ktpKey));

                    $occupationKey = 'pek_' . ($i + 1);
                    $occupation = $request->input($occupationKey);

                    FamilyMember::create([
                        'respondent_id' => $respondent->id,
                        'name' => $nama,
                        'gender' => $gender,
                        'age' => $request->rt_umur[$i] ?? null,
                        'status' => $request->rt_status[$i] ?? null,
                        'education' => $education,
                        'occupation' => $occupation,
                        'ktp_status' => $ktpStatus,
                    ]);
                }
            }

            $isDraft = $request->input('action') === 'draft';

            // 3. Update survey response
            $response->update([
                'questionnaire_number' => $request->questionnaire_number,
                'region_id' => $request->region_id,
                'interview_date' => $request->interview_date,
                'status' => $isDraft ? SurveyResponse::STATUS_DRAFT : SurveyResponse::STATUS_SUBMITTED,
                'surveyor_notes' => $request->surveyor_notes,
                'submitted_at' => $isDraft ? $response->submitted_at : now(),
            ]);

            $response->forceFill(['updated_by' => auth()->id()])->save();

            // 4. Update jawaban kuesioner
            $answers = $this->extractAnswers($request);
            $answersFiltered = array_filter($answers, fn ($v) => $v !== null && $v !== '' && $v !== []);

            $existingAnswer = $response->answers->first();
            if ($existingAnswer) {
                $existingAnswer->update(['answer_json' => $answersFiltered]);
            } elseif (! empty($answersFiltered)) {
                SurveyAnswer::create([
                    'survey_response_id' => $response->id,
                    'question_id' => null,
                    'answer_json' => $answersFiltered,
                ]);
            }

            // 5. Upload dokumen pendukung baru (jika ada)
            $this->storeSupportDocuments($request, $respondent, $response);
        });

        SyncDashboardFacts::dispatch($response->id);

        $action = $request->input('action');
        $message = $action === 'draft' ? 'Draft berhasil disimpan.' : 'Revisi berhasil disubmit untuk diverifikasi.';

        activity('survey')
            ->causedBy(auth()->user())
            ->performedOn($response)
            ->event($action === 'draft' ? 'survey_draft_updated' : 'survey_revision_submitted')
            ->withProperties([
                'questionnaire_number' => $response->questionnaire_number,
                'region_id' => $response->region_id,
                'status' => $response->status,
            ])
            ->log($action === 'draft' ? 'Draft survey diperbarui.' : 'Revisi survey disubmit.');

        return redirect()->route('app.lansia.show', $response->id)
            ->with('success', $message);
    }

    private function activeSurvey(): Survey
    {
        $survey = Survey::where('is_active', true)->first();

        if (! $survey) {
            throw ValidationException::withMessages([
                'survey' => 'Belum ada survey aktif. Hubungi administrator sebelum input data.',
            ]);
        }

        return $survey;
    }

    private function storeProfilePhoto(Request $request, Respondent $respondent): void
    {
        if (! $request->hasFile('photo') || ! $request->file('photo')->isValid()) {
            return;
        }

        $storage = app(SecureUploadStorage::class);
        $oldPath = $respondent->photo_path;
        $path = $storage->storeProfilePhoto($request->file('photo'), $respondent);

        $respondent->update(['photo_path' => $path]);

        if ($oldPath && $oldPath !== $path) {
            DB::afterCommit(fn () => $storage->delete($oldPath, ['photos']));
        }
    }

    private function storeSupportDocuments(Request $request, Respondent $respondent, SurveyResponse $response): void
    {
        if (! $request->hasFile('documents')) {
            return;
        }

        $allowedTypes = array_keys(config('uploads.documents.types'));
        $storage = app(SecureUploadStorage::class);

        foreach ($request->file('documents', []) as $type => $file) {
            if (! in_array($type, $allowedTypes, true) || ! $file || ! $file->isValid()) {
                continue;
            }

            $stored = $storage->storeDocument($file, $respondent);

            RespondentDocument::query()
                ->where('respondent_id', $respondent->id)
                ->where('document_type', $type)
                ->where('is_latest', true)
                ->update(['is_latest' => false]);

            RespondentDocument::create([
                'respondent_id' => $respondent->id,
                'survey_response_id' => $response->id,
                'document_type' => $type,
                'file_path' => $stored['file_path'],
                'file_name' => $stored['file_name'],
                'mime_type' => $stored['mime_type'],
                'file_size' => $stored['file_size'],
                'is_latest' => true,
                'uploaded_by' => auth()->id(),
            ]);
        }
    }

    private function extractAnswers(Request $request): array
    {
        return [
            // Section B
            'jml_anggota' => $request->jml_anggota,

            // Section D
            'penghasilan' => $request->penghasilan,

            // Section F
            'pangan_ubi' => $request->pangan_ubi,
            'pangan_beras' => $request->pangan_beras,
            'pangan_sagu' => $request->pangan_sagu,
            'sumber_pangan' => $request->sumber_pangan,
            'sumber_pangan_beli' => $request->sumber_pangan_beli,
            'sumber_pangan_lainnya' => $request->sumber_pangan_lainnya,
            'pola_konsumsi' => $request->pola_konsumsi,
            'konsumsi_hari' => $request->konsumsi_hari,
            'cara_masak' => $request->cara_masak,
            'bahan_bakar' => $request->bahan_bakar,
            'bahan_bakar_lainnya' => $request->bahan_bakar_lainnya,
            'bansos_sembako' => $request->bansos_sembako,
            'pemberi_sembako' => $request->pemberi_sembako,
            'pemberi_sembako_lainnya' => $request->pemberi_sembako_lainnya,

            // Section G
            'keluhan_kes' => $request->keluhan_kes,
            'keluhan_detail' => $request->keluhan_kes_detail,
            'periksa_rutin' => $request->periksa_rutin,
            'frek_periksa' => $request->frek_periksa,
            'layanan_kes' => $this->extractHealthServices($request),
            'layanan_kes_lainnya_nama' => $request->layanan_kes_lainnya_nama,
            'jangkau_kes' => $request->jangkau_kes,
            'jangkau_kes_detail' => $request->jangkau_kes_detail,
            'transport_kes' => $request->transport_kes,
            'transport_kes_lainnya' => $request->transport_kes_lainnya,
            'biaya_kes' => $request->biaya_kes,
            'biaya_kes_lainnya' => $request->biaya_kes_lainnya,
            'masalah_kes' => $request->masalah_kes,

            // Section H
            'status_rumah' => $request->status_rumah,
            'status_rumah_lainnya' => $request->status_rumah_lainnya,
            'jenis_rumah' => $request->jenis_rumah,
            'jenis_rumah_lainnya' => $request->jenis_rumah_lainnya,
            'sumber_air' => $request->sumber_air,
            'mata_air_nama' => $request->mata_air_nama,
            'nama_sungai' => $request->nama_sungai,
            'nama_kali' => $request->nama_kali,
            'sumber_air_lainnya' => $request->sumber_air_lainnya,
            'sistem_air' => $request->sistem_air,
            'sistem_air_lainnya' => $request->sistem_air_lainnya,
            'mck' => $request->mck,
            'bab_fasilitas' => $request->bab_fasilitas,
            'bab_pembuangan' => $request->bab_pembuangan,
            'jenis_kloset' => $request->jenis_kloset,
            'penerangan' => $request->penerangan,
            'penerangan_lainnya' => $request->penerangan_lainnya,
            'lama_penerangan' => $request->lama_penerangan,

            // Section I
            'media_info' => $request->media_info,
            'punya_hp' => $request->punya_hp,
            'media_alternatif' => $request->media_alternatif,

            // Section J
            'bansos' => $request->bansos,
            'jenis_bansos' => $request->jenis_bansos,
            'jamsosial' => $request->jamsosial,
            'jenis_jamsosial' => $request->jenis_jamsosial,
            'pelatihan_lansia' => $request->pelatihan_lansia,
            'jenis_pelatihan' => $request->jenis_pelatihan,
            'masalah_linsos' => $request->masalah_linsos,

            // Section K
            'kunjungi' => $request->kunjungi,
            'perkumpulan' => $request->perkumpulan,
            'rapat_warga' => $request->rapat_warga,
            'pemilu' => $request->pemilu,

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

    private function extractHealthServices(Request $request): array
    {
        $services = [
            'rumah_sakit' => 0,
            'puskesmas' => 1,
            'pustu' => 2,
            'puskesmas_keliling' => 3,
            'klinik' => 4,
            'apotek' => 5,
        ];

        $answers = [];

        foreach ($services as $key => $index) {
            $answers[$key] = [
                'medis' => $request->boolean("layanan_kes.$index.medis"),
                'rutin' => $request->boolean("layanan_kes.$index.rutin"),
            ];
        }

        $answers['lainnya'] = [
            'medis' => $request->boolean('layanan_kes_lain_medis'),
            'rutin' => $request->boolean('layanan_kes_lain_rutin'),
        ];

        return $answers;
    }

    private function normalizeKtpStatus(?string $status): ?string
    {
        return $status === 'ktp_nas' ? 'ktp_nasional' : $status;
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

    public function searchVillages(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        if (mb_strlen($search) < 2) {
            return response()->json([]);
        }

        $likeOperator = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $villages = Region::active()
            ->village()
            ->with('parent.parent')
            ->where(function ($query) use ($search, $likeOperator): void {
                $query->where('name', $likeOperator, "%{$search}%")
                    ->orWhereHas('parent', fn ($district) => $district->where('name', $likeOperator, "%{$search}%"))
                    ->orWhereHas('parent.parent', fn ($city) => $city->where('name', $likeOperator, "%{$search}%"));
            })
            ->orderBy('name')
            ->limit(50)
            ->get();

        return response()->json($villages->map(fn (Region $village): array => [
            'id' => $village->id,
            'name' => $village->name,
            'district' => $village->parent?->name,
            'city' => $village->parent?->parent?->name,
            'label' => collect([$village->name, $village->parent?->name, $village->parent?->parent?->name])
                ->filter()
                ->join(' / '),
        ])->values());
    }
}
