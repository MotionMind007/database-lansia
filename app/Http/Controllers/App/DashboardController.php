<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Models\SurveyAnswer;
use App\Models\SurveyResponse;
use App\Support\DashboardCache;
use App\Support\DashboardFactReader;
use App\Support\DashboardQuestionCatalog;
use App\Support\SurveyResponseAccess;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $cityId = request('city_id');
        $districtId = request('district_id');
        $villageId = request('village_id');
        $gender = request('gender');
        $category = request('category');

        if (! in_array($gender, ['male', 'female'], true)) {
            $gender = null;
        }

        $payload = DashboardCache::remember(
            $user,
            compact('cityId', 'districtId', 'villageId', 'gender', 'category'),
            fn (): array => $this->buildDashboardPayload($user, $cityId, $districtId, $villageId, $gender, $category)
        );

        $stats = $payload['stats'];
        $questionAnalytics = $payload['questionAnalytics'];
        $dashboardSummary = $payload['dashboardSummary'];
        $categoryOptions = $payload['categoryOptions'];
        $category = $payload['category'];

        $province = Region::active()->province()->first();
        $cities = $province ? Region::active()->city()->where('parent_id', $province->id)->orderBy('name')->get() : collect();
        $districts = $cityId ? Region::active()->district()->where('parent_id', $cityId)->orderBy('name')->get() : collect();
        $villages = $districtId ? Region::active()->village()->where('parent_id', $districtId)->orderBy('name')->get() : collect();

        return view('app.dashboard', compact(
            'stats',
            'questionAnalytics',
            'dashboardSummary',
            'cities',
            'districts',
            'villages',
            'cityId',
            'districtId',
            'villageId',
            'gender',
            'category',
            'categoryOptions'
        ));
    }

    private function buildDashboardPayload($user, $cityId, $districtId, $villageId, ?string $gender, ?string $category): array
    {
        $baseQuery = SurveyResponse::query();
        SurveyResponseAccess::applyVisibleScope($baseQuery, $user);

        if ($villageId) {
            $baseQuery->where('region_id', $villageId);
        } elseif ($districtId) {
            $regionIds = Region::where('parent_id', $districtId)->pluck('id');
            $baseQuery->whereIn('region_id', $regionIds);
        } elseif ($cityId) {
            $districtIds = Region::where('parent_id', $cityId)->pluck('id');
            $regionIds = Region::whereIn('parent_id', $districtIds)->pluck('id');
            $baseQuery->whereIn('region_id', $regionIds);
        }

        if ($gender) {
            $baseQuery->whereHas('respondent', function ($query) use ($gender) {
                $query->where('gender', $gender);
            });
        }

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'this_month' => (clone $baseQuery)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            'verified' => (clone $baseQuery)->where('status', SurveyResponse::STATUS_VERIFIED)->count(),
            'need_revision' => (clone $baseQuery)->where('status', SurveyResponse::STATUS_NEED_REVISION)->count(),
            'submitted' => (clone $baseQuery)->where('status', SurveyResponse::STATUS_SUBMITTED)->count(),
            'draft' => (clone $baseQuery)->where('status', SurveyResponse::STATUS_DRAFT)->count(),
            'rejected' => (clone $baseQuery)->where('status', SurveyResponse::STATUS_REJECTED)->count(),
        ];

        $factReader = app(DashboardFactReader::class);
        if ($factReader->hasFacts()) {
            return $factReader->build($user, $cityId, $districtId, $villageId, $gender, $category, $stats);
        }

        $responses = (clone $baseQuery)
            ->with(['respondent'])
            ->limit(max(1, (int) config('dashboard.raw_fallback_limit', 5000)))
            ->get();
        $responseIds = $responses->pluck('id');

        $answerPayloads = SurveyAnswer::whereIn('survey_response_id', $responseIds)
            ->whereNotNull('answer_json')
            ->get()
            ->groupBy('survey_response_id')
            ->map(function ($answers) {
                return $answers->reduce(function (array $carry, SurveyAnswer $answer) {
                    return array_replace_recursive($carry, $answer->answer_json ?? []);
                }, []);
            });

        $questionAnalytics = $this->buildQuestionAnalytics($responses, $answerPayloads);
        $categoryOptions = collect($questionAnalytics)
            ->pluck('group')
            ->filter(fn ($group): bool => is_string($group) && $group !== '')
            ->unique()
            ->values();

        if ($category && $categoryOptions->contains($category)) {
            $questionAnalytics = collect($questionAnalytics)
                ->where('group', $category)
                ->values()
                ->all();
        } else {
            $category = null;
        }

        return [
            'stats' => $stats,
            'questionAnalytics' => $questionAnalytics,
            'dashboardSummary' => $this->buildDashboardSummary($questionAnalytics, $responses->count()),
            'categoryOptions' => $categoryOptions,
            'category' => $category,
        ];
    }

    private function buildQuestionAnalytics($responses, $answerPayloads): array
    {
        $items = [];

        foreach (DashboardQuestionCatalog::items() as $question) {
            $values = [];

            foreach ($responses as $response) {
                if (($question['source'] ?? 'answer') === 'respondent') {
                    $value = data_get($response->respondent, $question['field']);
                } else {
                    $payload = $answerPayloads->get($response->id, []);
                    $value = data_get($payload, $question['field']);
                }

                if (isset($question['transform']) && method_exists($this, $question['transform'])) {
                    $value = $this->{$question['transform']}($value);
                }

                $values[] = $value;
            }

            $items[] = $this->buildDistributionItem($question, $values, $responses->count(), 'responden');
        }

        $items[] = $this->buildFoodFrequencyChart($responses, $answerPayloads);
        $items[] = $this->buildHealthServiceMatrix($responses, $answerPayloads);

        return collect($items)
            ->filter()
            ->sortBy(fn ($item) => $item['sort'])
            ->values()
            ->all();
    }

    private function buildDistributionItem(array $question, array $values, int $baseTotal, string $denominatorLabel): array
    {
        $kind = $question['kind'] ?? 'single';
        $options = $question['options'] ?? [];
        $counts = $options ? array_fill_keys(array_values($options), 0) : [];
        $answered = 0;
        $mentions = 0;

        foreach ($values as $value) {
            if ($this->blank($value)) {
                continue;
            }

            if ($kind === 'text') {
                $answered++;
                $label = $this->cleanTextValue($value);
                $counts[$label] = ($counts[$label] ?? 0) + 1;
                continue;
            }

            if ($kind === 'multi') {
                $selected = is_array($value) ? $value : [$value];
                $selected = array_values(array_filter($selected, fn ($item) => ! $this->blank($item)));

                if (empty($selected)) {
                    continue;
                }

                $answered++;

                foreach ($selected as $selectedValue) {
                    $label = $this->optionLabel($selectedValue, $options);
                    $counts[$label] = ($counts[$label] ?? 0) + 1;
                    $mentions++;
                }

                continue;
            }

            $answered++;
            $label = $this->optionLabel($value, $options);
            $counts[$label] = ($counts[$label] ?? 0) + 1;
            $mentions++;
        }

        arsort($counts);

        $rows = collect($counts)->map(function ($count, $label) use ($answered, $baseTotal) {
            return [
                'label' => $label,
                'count' => $count,
                'pct' => $answered > 0 ? round(($count / $answered) * 100, 1) : 0,
                'coverage_pct' => $baseTotal > 0 ? round(($count / $baseTotal) * 100, 1) : 0,
            ];
        })->values()->all();

        $top = collect($rows)->first(fn ($row) => $row['count'] > 0);

        return [
            'key' => $question['key'],
            'number' => $question['number'],
            'label' => $question['label'],
            'group' => $question['group'],
            'kind' => $kind,
            'display' => $question['display'] ?? 'table',
            'wide' => $question['wide'] ?? false,
            'sort' => $question['sort'],
            'rows' => $rows,
            'answered' => $answered,
            'base_total' => $baseTotal,
            'mentions' => $kind === 'multi' ? $mentions : $answered,
            'denominator_label' => $denominatorLabel,
            'insight' => $this->buildInsight($top, $answered, $kind),
        ];
    }

    private function buildFoodFrequencyChart($responses, $answerPayloads): array
    {
        $item = collect(DashboardQuestionCatalog::specialItems())->firstWhere('key', 'frekuensi_pangan_pokok');
        $foods = DashboardQuestionCatalog::foodFields();
        $frequencyOptions = DashboardQuestionCatalog::frequencyOptions();
        $rows = [];
        $answered = 0;

        foreach ($foods as $field => $foodLabel) {
            $rows[$field] = [
                'label' => $foodLabel,
                'counts' => array_fill_keys(array_values($frequencyOptions), 0),
            ];
        }

        foreach ($responses as $response) {
            $payload = $answerPayloads->get($response->id, []);
            $hasAnswer = false;

            foreach ($foods as $field => $foodLabel) {
                $value = data_get($payload, $field);

                if ($this->blank($value)) {
                    continue;
                }

                $label = $this->optionLabel($value, $frequencyOptions);
                $rows[$field]['counts'][$label] = ($rows[$field]['counts'][$label] ?? 0) + 1;
                $hasAnswer = true;
            }

            if ($hasAnswer) {
                $answered++;
            }
        }

        $datasets = collect($rows)->map(function ($row) {
            return [
                'label' => $row['label'],
                'data' => array_values($row['counts']),
            ];
        })->values()->all();

        return [
            'key' => $item['key'],
            'number' => $item['number'],
            'label' => $item['label'],
            'group' => $item['group'],
            'kind' => $item['kind'],
            'display' => $item['display'],
            'wide' => $item['wide'] ?? true,
            'sort' => $item['sort'],
            'rows' => array_values($rows),
            'chart_labels' => array_values($frequencyOptions),
            'datasets' => $datasets,
            'answered' => $answered,
            'base_total' => $responses->count(),
            'mentions' => collect($datasets)->sum(fn ($dataset) => array_sum($dataset['data'])),
            'denominator_label' => 'responden',
            'insight' => $answered > 0 ? 'Beras, ubi-ubian, dan sagu dibandingkan dalam satu diagram batang berkelompok.' : 'Belum ada jawaban terisi.',
        ];
    }

    private function buildHealthServiceMatrix($responses, $answerPayloads): array
    {
        $item = collect(DashboardQuestionCatalog::specialItems())->firstWhere('key', 'layanan_kes');
        $services = DashboardQuestionCatalog::healthServices();

        $rows = [];
        $answered = 0;

        foreach ($services as $key => $label) {
            $rows[$key] = [
                'label' => $label,
                'medis' => 0,
                'rutin' => 0,
            ];
        }

        foreach ($responses as $response) {
            $payload = $answerPayloads->get($response->id, []);
            $matrix = data_get($payload, 'layanan_kes', []);
            $hasAnswer = false;

            foreach ($services as $key => $label) {
                if (data_get($matrix, "$key.medis")) {
                    $rows[$key]['medis']++;
                    $hasAnswer = true;
                }

                if (data_get($matrix, "$key.rutin")) {
                    $rows[$key]['rutin']++;
                    $hasAnswer = true;
                }
            }

            if ($hasAnswer) {
                $answered++;
            }
        }

        return [
            'key' => $item['key'],
            'number' => $item['number'],
            'label' => $item['label'],
            'group' => $item['group'],
            'kind' => $item['kind'],
            'display' => $item['display'],
            'wide' => $item['wide'] ?? true,
            'sort' => $item['sort'],
            'rows' => array_values($rows),
            'chart_labels' => array_values($services),
            'datasets' => [
                ['label' => 'Medis', 'data' => collect($rows)->pluck('medis')->values()->all()],
                ['label' => 'Pemeriksaan rutin', 'data' => collect($rows)->pluck('rutin')->values()->all()],
            ],
            'answered' => $answered,
            'base_total' => $responses->count(),
            'mentions' => collect($rows)->sum('medis') + collect($rows)->sum('rutin'),
            'denominator_label' => 'responden',
            'insight' => $answered > 0 ? 'Terbaca pada '.$answered.' responden.' : 'Belum ada jawaban terisi.',
        ];
    }

    private function buildDashboardSummary(array $questionAnalytics, int $responseCount): array
    {
        $questionsWithData = collect($questionAnalytics)->where('answered', '>', 0)->count();

        return [
            'response_count' => $responseCount,
            'questions_total' => count($questionAnalytics),
            'questions_with_data' => $questionsWithData,
            'completion_pct' => count($questionAnalytics) > 0 ? round(($questionsWithData / count($questionAnalytics)) * 100, 1) : 0,
        ];
    }

    private function optionLabel($value, array $options): string
    {
        $normalizedValue = $this->normalizeValue($value);

        foreach ($options as $optionValue => $label) {
            if ($this->normalizeValue($optionValue) === $normalizedValue) {
                return $label;
            }
        }

        return $this->cleanTextValue($value);
    }

    private function ageBucket($age): ?string
    {
        if ($this->blank($age)) {
            return null;
        }

        $age = (int) $age;

        return match (true) {
            $age < 60 => '<60',
            $age <= 64 => '60-64',
            $age <= 69 => '65-69',
            $age <= 74 => '70-74',
            $age <= 79 => '75-79',
            default => '80+',
        };
    }

    private function buildInsight(?array $top, int $answered, string $kind): string
    {
        if (! $top || $answered === 0) {
            return 'Belum ada jawaban terisi.';
        }

        if ($kind === 'multi') {
            return 'Pilihan paling sering muncul: '.$top['label'].' ('.$top['pct'].'% dari responden yang menjawab).';
        }

        if ($kind === 'text') {
            return 'Isian yang paling sering muncul: '.$top['label'].' ('.$top['count'].' kali).';
        }

        return 'Mayoritas: '.$top['label'].' ('.$top['pct'].'% dari responden yang menjawab).';
    }

    private function cleanTextValue($value): string
    {
        if (is_array($value)) {
            $value = implode(', ', array_filter($value));
        }

        $value = trim((string) $value);
        $value = preg_replace('/\s+/', ' ', $value) ?: '-';

        return mb_strlen($value) > 90 ? mb_substr($value, 0, 87).'...' : $value;
    }

    private function normalizeValue($value): string
    {
        $value = $this->cleanTextValue($value);
        $value = str_replace(['â€“', '–', '—'], '-', $value);

        return mb_strtolower($value);
    }

    private function blank($value): bool
    {
        return $value === null || $value === '' || $value === [];
    }
}
