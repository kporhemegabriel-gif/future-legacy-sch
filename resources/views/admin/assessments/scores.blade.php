@extends('layouts.app')

@section('title', 'Enter Scores')

@section('content')
    <p><a href="{{ route('admin.assessments.show', $assessment) }}">&larr; Back to {{ $assessment->name }}</a></p>
    <h1>Scores: {{ $assessment->name }}</h1>
    <p class="muted">{{ $assessment->subject->name }} &middot; {{ $assessment->schoolClass->displayName() }} &middot; {{ $assessment->term->name }} &middot; Max score: {{ $assessment->max_score }}</p>

    <div class="card">
        @if ($enrollments->isEmpty())
            <p class="empty-state">No students are enrolled in this subject for this class/year yet.</p>
        @else
            <form method="POST" action="{{ route('admin.assessments.scores.update', $assessment) }}">
                @csrf
                @method('PUT')
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Admission #</th><th>Student</th><th>Score (out of {{ $assessment->max_score }})</th></tr></thead>
                        <tbody>
                            @foreach ($enrollments as $index => $enrollment)
                                @php $existing = $existingScores->get($enrollment->student_id); @endphp
                                <tr>
                                    <td>{{ $enrollment->student->admission_number }}</td>
                                    <td>
                                        {{ $enrollment->student->fullName() }}
                                        <input type="hidden" name="scores[{{ $index }}][student_id]" value="{{ $enrollment->student_id }}">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" max="{{ $assessment->max_score }}"
                                               name="scores[{{ $index }}][score]"
                                               value="{{ old("scores.$index.score", $existing?->score) }}"
                                               style="width:6rem;">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="actions" style="margin-top:1rem;">
                    <button type="submit" class="btn">Save scores</button>
                </div>
            </form>
        @endif
    </div>
@endsection
