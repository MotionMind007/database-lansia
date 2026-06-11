<?php

namespace App\Support;

use App\Models\DashboardAnswerFact;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardFactReader
{
    public function hasFacts(): bool
    {
        return Schema::hasTable('dashboard_answer_facts')
            && DashboardAnswerFact::query()->exists();
    }

    public function build(User $user, $cityId, $districtId, $villageId, ?string $gender, ?string $category, array $stats): array
    {
        $baseTotal = (int) $stats['total'];
        $items = [];
        $baseQuery = $this->scopedQuery($user, $cityId, $districtId, $villageId, $gender);
        $normalQuestions = DashboardQuestionCatalog::items();
        $normalKeys = collect($normalQuestions)->pluck('key')->all();
        $normalCounts = $this->groupedCounts($baseQuery, $normalKeys, true);
        $normalAnswered = $this->answeredCounts($baseQuery, $normalKeys, true);

        foreach ($normalQuestions as $question) {
            $items[] = $this->buildDistributionItem(
                $question,
                $normalCounts[$question['key']] ?? [],
                $normalAnswered[$question['key']] ?? 0,
                $baseTotal
            );
        }

        $specialCounts = $this->groupedCounts($baseQuery, ['frekuensi_pangan_pokok', 'layanan_kes'], false);
        $specialAnswered = $this->answeredCounts($baseQuery, ['frekuensi_pangan_pokok', 'layanan_kes'], false);

        $items[] = $this->buildFoodFrequencyChart(
            $specialCounts['frekuensi_pangan_pokok'] ?? [],
            $specialAnswered['frekuensi_pangan_pokok'] ?? 0,
            $baseTotal
        );

        $items[] = $this->buildHealthServiceMatrix(
            $specialCounts['layanan_kes'] ?? [],
            $specialAnswered['layanan_kes'] ?? 0,
            $baseTotal
        );

        $questionAnalytics = collect($items)
            ->sortBy(fn (array $item): int => $item['sort'])
            ->values()
            ->all();

        $categoryOptions = collect(DashboardQuestionCatalog::allItems())
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
            'dashboardSummary' => $this->buildDashboardSummary($questionAnalytics, $baseTotal),
            'categoryOptions' => $categoryOptions,
            'category' => $category,
        ];
    }

    private function scopedQuery(User $user, $cityId, $districtId, $villageId, ?string $gender): Builder
    {
        $query = DashboardAnswerFact::query();
        $role = SurveyResponseAccess::roleFor($user);

        if ($role === 'surveyor') {
            $query->where('surveyor_id', $user->id);
        } elseif ($role === 'verifikator') {
            $query->whereIn('status', SurveyResponseAccess::verifiableStatuses());
        } elseif ($role !== 'administrator') {
            abort(403, 'Anda tidak memiliki akses ke data ini.');
        }

        if ($villageId) {
            $query->where('region_id', $villageId);
        } elseif ($districtId) {
            $query->where('district_id', $districtId);
        } elseif ($cityId) {
            $query->where('city_id', $cityId);
        }

        if ($gender) {
            $query->where('gender', $gender);
        }

        return $query;
    }

    private function groupedCounts(Builder $baseQuery, array $questionKeys, bool $onlyRows): array
    {
        $query = (clone $baseQuery)
            ->whereIn('question_key', $questionKeys)
            ->select('question_key', 'row_label', 'column_label', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('question_key', 'row_label', 'column_label');

        if ($onlyRows) {
            $query->whereNull('column_label');
        }

        return $query->get()
            ->groupBy('question_key')
            ->map(function ($rows): array {
                return $rows->mapWithKeys(function ($row): array {
                    $key = $row->column_label
                        ? $row->row_label.'|'.$row->column_label
                        : $row->row_label;

                    return [$key => (int) $row->aggregate];
                })->all();
            })
            ->all();
    }

    private function answeredCounts(Builder $baseQuery, array $questionKeys, bool $onlyRows): array
    {
        $query = (clone $baseQuery)
            ->whereIn('question_key', $questionKeys)
            ->select('question_key', DB::raw('COUNT(DISTINCT survey_response_id) as aggregate'))
            ->groupBy('question_key');

        if ($onlyRows) {
            $query->whereNull('column_label');
        }

        return $query->pluck('aggregate', 'question_key')
            ->map(fn ($count): int => (int) $count)
            ->all();
    }

    private function buildDistributionItem(array $question, array $answerCounts, int $answered, int $baseTotal): array
    {
        $kind = $question['kind'] ?? 'single';
        $counts = ($question['options'] ?? [])
            ? array_fill_keys(array_values($question['options']), 0)
            : [];

        foreach ($answerCounts as $label => $count) {
            $counts[$label] = $count;
        }

        arsort($counts);

        $mentions = $kind === 'multi' ? array_sum($counts) : $answered;

        $rows = collect($counts)->map(function ($count, $label) use ($answered, $baseTotal) {
            return [
                'label' => $label,
                'count' => $count,
                'pct' => $answered > 0 ? round(($count / $answered) * 100, 1) : 0,
                'coverage_pct' => $baseTotal > 0 ? round(($count / $baseTotal) * 100, 1) : 0,
            ];
        })->values()->all();

        $top = collect($rows)->first(fn (array $row): bool => $row['count'] > 0);

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
            'mentions' => $mentions,
            'denominator_label' => 'responden',
            'insight' => $this->buildInsight($top, $answered, $kind),
        ];
    }

    private function buildFoodFrequencyChart(array $counts, int $answered, int $baseTotal): array
    {
        $item = collect(DashboardQuestionCatalog::specialItems())->firstWhere('key', 'frekuensi_pangan_pokok');
        $frequencyOptions = DashboardQuestionCatalog::frequencyOptions();
        $rows = [];

        foreach (DashboardQuestionCatalog::foodFields() as $field => $foodLabel) {
            $rows[$foodLabel] = [
                'label' => $foodLabel,
                'counts' => array_fill_keys(array_values($frequencyOptions), 0),
            ];
        }

        foreach ($counts as $compoundLabel => $count) {
            [$rowLabel, $columnLabel] = array_pad(explode('|', $compoundLabel, 2), 2, null);

            if (! isset($rows[$rowLabel]) || ! $columnLabel) {
                continue;
            }

            $rows[$rowLabel]['counts'][$columnLabel] = (int) $count;
        }

        $datasets = collect($rows)->map(function (array $row): array {
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
            'wide' => true,
            'sort' => $item['sort'],
            'rows' => array_values($rows),
            'chart_labels' => array_values($frequencyOptions),
            'datasets' => $datasets,
            'answered' => $answered,
            'base_total' => $baseTotal,
            'mentions' => collect($datasets)->sum(fn (array $dataset): int => array_sum($dataset['data'])),
            'denominator_label' => 'responden',
            'insight' => $answered > 0 ? 'Beras, ubi-ubian, dan sagu dibandingkan dalam satu diagram batang berkelompok.' : 'Belum ada jawaban terisi.',
        ];
    }

    private function buildHealthServiceMatrix(array $counts, int $answered, int $baseTotal): array
    {
        $item = collect(DashboardQuestionCatalog::specialItems())->firstWhere('key', 'layanan_kes');
        $rows = [];

        foreach (DashboardQuestionCatalog::healthServices() as $key => $serviceLabel) {
            $rows[$serviceLabel] = [
                'label' => $serviceLabel,
                'medis' => 0,
                'rutin' => 0,
            ];
        }

        foreach ($counts as $compoundLabel => $count) {
            [$rowLabel, $columnLabel] = array_pad(explode('|', $compoundLabel, 2), 2, null);

            if (! isset($rows[$rowLabel])) {
                continue;
            }

            if ($columnLabel === 'Medis') {
                $rows[$rowLabel]['medis'] = (int) $count;
            }

            if ($columnLabel === 'Pemeriksaan rutin') {
                $rows[$rowLabel]['rutin'] = (int) $count;
            }
        }

        return [
            'key' => $item['key'],
            'number' => $item['number'],
            'label' => $item['label'],
            'group' => $item['group'],
            'kind' => $item['kind'],
            'display' => $item['display'],
            'wide' => true,
            'sort' => $item['sort'],
            'rows' => array_values($rows),
            'chart_labels' => array_values(DashboardQuestionCatalog::healthServices()),
            'datasets' => [
                ['label' => 'Medis', 'data' => collect($rows)->pluck('medis')->values()->all()],
                ['label' => 'Pemeriksaan rutin', 'data' => collect($rows)->pluck('rutin')->values()->all()],
            ],
            'answered' => $answered,
            'base_total' => $baseTotal,
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
}
