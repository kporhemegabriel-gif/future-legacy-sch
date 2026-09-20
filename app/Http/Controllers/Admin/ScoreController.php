<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateScoresRequest;
use App\Models\Assessment;
use App\Models\Enrollment;
use App\Models\Score;
use App\Models\ScoreAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ScoreController extends Controller
{
    /** One page: every student enrolled in this assessment's subject/class/year, with their current score (if any). */
    public function edit(Assessment $assessment): View
    {
        Gate::authorize('manageScores', $assessment);

        $assessment->load(['subject', 'schoolClass', 'term']);

        $enrollments = Enrollment::query()
            ->with('student')
            ->where('subject_id', $assessment->subject_id)
            ->where('class_id', $assessment->class_id)
            ->where('academic_year_id', $assessment->academic_year_id)
            ->where('status', 'enrolled')
            ->get();

        $existingScores = Score::where('assessment_id', $assessment->id)
            ->get()
            ->keyBy('student_id');

        return view('admin.assessments.scores', [
            'assessment' => $assessment,
            'enrollments' => $enrollments->sortBy(fn ($e) => $e->student->fullName()),
            'existingScores' => $existingScores,
        ]);
    }

    public function update(UpdateScoresRequest $request, Assessment $assessment): RedirectResponse
    {
        $rows = $request->validated()['scores'] ?? [];
        $recordedBy = $request->user()->id;
        $changedCount = 0;

        DB::transaction(function () use ($rows, $assessment, $recordedBy, &$changedCount) {
            foreach ($rows as $row) {
                // Blank input means "not entered" — skip rather than
                // writing a 0, so a student simply not yet scored is
                // distinguishable from a student who scored zero.
                if (! isset($row['score']) || $row['score'] === '' || $row['score'] === null) {
                    continue;
                }

                $enrollment = Enrollment::where('student_id', $row['student_id'])
                    ->where('subject_id', $assessment->subject_id)
                    ->where('class_id', $assessment->class_id)
                    ->where('academic_year_id', $assessment->academic_year_id)
                    ->where('status', 'enrolled')
                    ->first();

                // Re-checked here too (not just in the FormRequest) since
                // this closes the same request/response cycle — belt and
                // suspenders on the one write path that touches academic
                // records directly.
                if (! $enrollment) {
                    continue;
                }

                $existing = Score::where('assessment_id', $assessment->id)
                    ->where('student_id', $row['student_id'])
                    ->first();

                $oldScore = $existing?->score;
                $newScore = round((float) $row['score'], 2);

                if ($existing) {
                    $existing->update(['score' => $newScore, 'recorded_by' => $recordedBy]);
                } else {
                    Score::create([
                        'assessment_id' => $assessment->id,
                        'enrollment_id' => $enrollment->id,
                        'student_id' => $row['student_id'],
                        'subject_id' => $assessment->subject_id,
                        'class_id' => $assessment->class_id,
                        'academic_year_id' => $assessment->academic_year_id,
                        'term_id' => $assessment->term_id,
                        'score' => $newScore,
                        'recorded_by' => $recordedBy,
                    ]);
                }

                // Skip the audit write entirely if nothing actually
                // changed (re-submitting the same form shouldn't pad the
                // history with no-op entries).
                if ($oldScore !== null && (float) $oldScore === $newScore) {
                    continue;
                }

                ScoreAudit::create([
                    'score_id' => $existing?->id ?? Score::where('assessment_id', $assessment->id)->where('student_id', $row['student_id'])->value('id'),
                    'student_id' => $row['student_id'],
                    'assessment_id' => $assessment->id,
                    'changed_by' => $recordedBy,
                    'old_score' => $oldScore,
                    'new_score' => $newScore,
                    'changed_at' => now(),
                ]);
                $changedCount++;
            }
        });

        return redirect()->route('admin.assessments.show', $assessment)->with('success', "Scores saved ({$changedCount} updated).");
    }
}
