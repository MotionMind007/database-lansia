<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\SurveyResponse;
use App\Models\VerificationLog;
use App\Support\SurveyResponseAccess;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function index()
    {
        $responses = SurveyResponse::with(['respondent', 'surveyor', 'region'])
            ->whereIn('status', SurveyResponseAccess::verifiableStatuses())
            ->orderBy('submitted_at', 'asc')
            ->paginate(25);

        return view('app.verification.index', compact('responses'));
    }

    public function show($id)
    {
        $response = SurveyResponseAccess::applyVerificationScope(SurveyResponse::with([
            'respondent.familyMembers',
            'respondent.documents',
            'surveyor',
            'region.parent.parent',
            'answers',
            'verificationLogs.verifier',
        ]))->findOrFail($id);

        return view('app.verification.show', compact('response'));
    }

    public function verify(Request $request, $id)
    {
        $request->validate([
            'status' => ['required', 'in:verified,need_revision,rejected'],
            'note' => ['required_if:status,need_revision,rejected', 'nullable', 'string', 'min:10'],
        ], [
            'note.required_if' => 'Catatan wajib diisi minimal 10 karakter untuk status ini.',
            'note.min' => 'Catatan minimal 10 karakter.',
        ]);

        $response = SurveyResponseAccess::applyVerificationScope(SurveyResponse::query())->findOrFail($id);

        $response->update([
            'status' => $request->status,
            'verified_at' => $request->status === 'verified' ? now() : null,
            'verified_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        VerificationLog::create([
            'survey_response_id' => $response->id,
            'status' => $request->status,
            'note' => $request->note,
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        $messages = [
            'verified' => 'Data berhasil diverifikasi.',
            'need_revision' => 'Data dikembalikan untuk revisi.',
            'rejected' => 'Data ditolak. Surveyor harus turun ulang.',
        ];

        return redirect()->route('app.verification.index')
            ->with('success', $messages[$request->status]);
    }
}
