<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Models\SurveyResponse;
use App\Models\User;
use App\Support\SurveyResponseAccess;
use Illuminate\Http\Request;

class LansiaController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = SurveyResponse::with(['respondent', 'surveyor', 'region'])
            ->select('survey_responses.*');

        SurveyResponseAccess::applyVisibleScope($query, $user);

        // Filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('respondent', function ($respondentQuery) use ($search) {
                    $respondentQuery->where('full_name', 'ilike', "%{$search}%");
                })->orWhere('questionnaire_number', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('region_id')) {
            $query->where('region_id', $request->region_id);
        }

        if ($request->filled('surveyor_id')) {
            $query->where('surveyor_id', $request->surveyor_id);
        }

        $responses = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        // Filter options
        $regions = Region::active()->village()->with('parent.parent')->get();
        $surveyors = User::role('surveyor')->where('is_active', true)->get(['id', 'name']);

        return view('app.lansia.index', compact('responses', 'regions', 'surveyors'));
    }

    public function show($id)
    {
        $user = auth()->user();

        $query = SurveyResponse::with([
            'respondent.familyMembers',
            'respondent.documents',
            'answers',
            'surveyor',
            'region.parent.parent',
            'verificationLogs.verifier',
        ]);

        SurveyResponseAccess::applyVisibleScope($query, $user);

        $response = $query->findOrFail($id);

        return view('app.lansia.show', compact('response'));
    }
}
