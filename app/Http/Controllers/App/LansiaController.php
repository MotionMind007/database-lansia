<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Models\SurveyResponse;
use App\Models\User;
use Illuminate\Http\Request;

class LansiaController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $role = $user->getRoleNames()->first();

        $query = SurveyResponse::with(['respondent', 'surveyor', 'region'])
            ->select('survey_responses.*');

        // Role-based data scoping
        if ($role === 'surveyor') {
            $query->where('surveyor_id', $user->id);
        } elseif ($role === 'verifikator') {
            $query->whereIn('status', [
                SurveyResponse::STATUS_SUBMITTED,
                SurveyResponse::STATUS_NEED_REVISION,
            ]);
        }
        // Administrator sees all

        // Filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('respondent', function ($q) use ($search) {
                $q->where('full_name', 'ilike', "%{$search}%");
            })->orWhere('questionnaire_number', 'ilike', "%{$search}%");
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
        $regions   = Region::active()->village()->with('parent.parent')->get();
        $surveyors = User::role('surveyor')->where('is_active', true)->get(['id', 'name']);

        return view('app.lansia.index', compact('responses', 'regions', 'surveyors'));
    }

    public function show($id)
    {
        $user = auth()->user();
        $role = $user->getRoleNames()->first();

        $response = SurveyResponse::with([
            'respondent.familyMembers',
            'respondent.documents',
            'surveyor',
            'region.parent.parent',
            'verificationLogs.verifier',
        ])->findOrFail($id);

        // Surveyor hanya bisa lihat miliknya
        if ($role === 'surveyor' && $response->surveyor_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke data ini.');
        }

        return view('app.lansia.show', compact('response'));
    }
}
