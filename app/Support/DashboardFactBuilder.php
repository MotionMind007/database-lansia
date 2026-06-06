<?php

namespace App\Support;

use App\Models\DashboardAnswerFact;
use App\Models\SurveyResponse;
use Illuminate\Support\Facades\DB;

class DashboardFactBuilder
{
    private ?array $catalog = null;

    public function rebuildAll(int $chunk = 500, ?callable $progress = null): int
    {
        DB::disableQueryLog();
        $count = 0;

        DB::transaction(function () use ($chunk, $progress, &$count): void {
            DB::table('dashboard_answer_facts')->delete();

            SurveyResponse::with(['respondent', 'region.parent.parent', 'answers'])
                ->orderBy('id')
                ->chunkById($chunk, function ($responses) use (&$count, $progress): void {
                    $facts = [];
                    $timestamp = now();

                    foreach ($responses as $response) {
                        foreach ($this->factsForResponse($response, $timestamp) as $fact) {
                            $facts[] = $fact;
                        }

                        $count++;
                    }

                    $this->insertFactRows($facts);

                    if ($progress) {
                        $progress($responses->count(), $count);
                    }
                });
        });

        DashboardCache::flush();

        return $count;
    }

    public function rebuildForResponse(SurveyResponse $response): void
    {
        $response->loadMissing(['respondent', 'region.parent.parent', 'answers']);

        DB::transaction(function () use ($response): void {
            DashboardAnswerFact::where('survey_response_id', $response->id)->delete();
            $this->insertFacts($response);
        });

        DashboardCache::flush();
    }

    public function syncResponseStatus(SurveyResponse $response): void
    {
        DashboardAnswerFact::where('survey_response_id', $response->id)
            ->update(['status' => $response->status]);

        DashboardCache::flush();
    }

    private function insertFacts(SurveyResponse $response): void
    {
        $this->insertFactRows($this->factsForResponse($response, now()));
    }

    private function insertFactRows(array $facts): void
    {
        if (empty($facts)) {
            return;
        }

        $insertChunk = DB::connection()->getDriverName() === 'sqlite' ? 500 : 2000;

        foreach (array_chunk($facts, $insertChunk) as $chunk) {
            DB::table('dashboard_answer_facts')->insert($chunk);
        }
    }

    private function factsForResponse(SurveyResponse $response, $timestamp): array
    {
        $catalog = $this->catalog();
        $payload = $this->answerPayload($response);
        $facts = [];

        foreach ($catalog['questions'] as $question) {
            $value = ($question['source'] ?? 'answer') === 'respondent'
                ? data_get($response->respondent, $question['field'])
                : data_get($payload, $question['field']);

            if (($question['transform'] ?? null) === 'ageBucket') {
                $value = $this->ageBucket($value);
            }

            foreach ($this->labelsForValue($value, $question) as $label) {
                $facts[] = $this->factRow($response, $question, $label, null, $timestamp);
            }
        }

        foreach ($catalog['food_fields'] as $field => $foodLabel) {
            $value = data_get($payload, $field);

            if ($this->blank($value)) {
                continue;
            }

            $facts[] = $this->factRow(
                $response,
                $catalog['food_item'],
                $foodLabel,
                $this->optionLabel($value, $catalog['frequency_options'], $catalog['frequency_lookup']),
                $timestamp
            );
        }

        $matrix = data_get($payload, 'layanan_kes', []);
        foreach ($catalog['health_services'] as $key => $serviceLabel) {
            if (data_get($matrix, "{$key}.medis")) {
                $facts[] = $this->factRow($response, $catalog['health_item'], $serviceLabel, 'Medis', $timestamp);
            }

            if (data_get($matrix, "{$key}.rutin")) {
                $facts[] = $this->factRow($response, $catalog['health_item'], $serviceLabel, 'Pemeriksaan rutin', $timestamp);
            }
        }

        return $facts;
    }

    private function catalog(): array
    {
        if ($this->catalog !== null) {
            return $this->catalog;
        }

        $specialItems = DashboardQuestionCatalog::specialItems();
        $questions = array_map(function (array $question): array {
            $question['option_lookup'] = $this->optionLookup($question['options'] ?? []);

            return $question;
        }, DashboardQuestionCatalog::items());
        $frequencyOptions = DashboardQuestionCatalog::frequencyOptions();

        return $this->catalog = [
            'questions' => $questions,
            'food_item' => $specialItems[0],
            'health_item' => $specialItems[1],
            'food_fields' => DashboardQuestionCatalog::foodFields(),
            'health_services' => DashboardQuestionCatalog::healthServices(),
            'frequency_options' => $frequencyOptions,
            'frequency_lookup' => $this->optionLookup($frequencyOptions),
        ];
    }

    private function labelsForValue($value, array $question): array
    {
        if ($this->blank($value)) {
            return [];
        }

        $kind = $question['kind'] ?? 'single';
        $options = $question['options'] ?? [];

        if ($kind === 'text') {
            return [$this->cleanTextValue($value)];
        }

        if ($kind === 'multi') {
            $selected = is_array($value) ? $value : [$value];

            return collect($selected)
                ->reject(fn ($item): bool => $this->blank($item))
                ->map(fn ($item): string => $this->optionLabel($item, $options, $question['option_lookup'] ?? null))
                ->values()
                ->all();
        }

        return [$this->optionLabel($value, $options, $question['option_lookup'] ?? null)];
    }

    private function factRow(SurveyResponse $response, array $question, string $rowLabel, ?string $columnLabel, $timestamp): array
    {
        $district = $response->region?->parent;
        $city = $district?->parent;

        return [
            'survey_response_id' => $response->id,
            'respondent_id' => $response->respondent_id,
            'survey_id' => $response->survey_id,
            'surveyor_id' => $response->surveyor_id,
            'city_id' => $city?->id,
            'district_id' => $district?->id,
            'region_id' => $response->region_id,
            'status' => $response->status,
            'gender' => $response->respondent?->gender,
            'question_key' => $question['key'],
            'question_number' => $question['number'],
            'question_label' => $question['label'],
            'question_group' => $question['group'],
            'question_kind' => $question['kind'],
            'question_display' => $question['display'] ?? 'table',
            'question_sort' => $question['sort'],
            'row_label' => $rowLabel,
            'column_label' => $columnLabel,
            'response_created_at' => $response->created_at,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];
    }

    private function answerPayload(SurveyResponse $response): array
    {
        $payload = [];

        foreach ($response->answers as $answer) {
            $payload = array_replace_recursive($payload, $answer->answer_json ?? []);
        }

        return $payload;
    }

    private function optionLookup(array $options): array
    {
        $lookup = [];

        foreach ($options as $optionValue => $label) {
            $lookup[$this->normalizeValue($optionValue)] = $label;
        }

        return $lookup;
    }

    private function optionLabel($value, array $options, ?array $lookup = null): string
    {
        $normalizedValue = $this->normalizeValue($value);

        if ($lookup !== null && isset($lookup[$normalizedValue])) {
            return $lookup[$normalizedValue];
        }

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
        $value = str_replace(['Ã¢â‚¬â€œ', 'â€“', 'â€”'], '-', $value);

        return mb_strtolower($value);
    }

    private function blank($value): bool
    {
        return $value === null || $value === '' || $value === [];
    }
}
