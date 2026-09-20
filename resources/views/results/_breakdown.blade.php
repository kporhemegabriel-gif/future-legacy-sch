{{-- Expects: $termResult, $breakdown (Collection from ResultCalculationService::studentTermBreakdown) --}}

<div class="grid">
    <div class="card"><div class="stat-label">Student</div><div>{{ $termResult->student->fullName() }}</div></div>
    <div class="card"><div class="stat-label">Class</div><div>{{ $termResult->schoolClass->displayName() }}</div></div>
    <div class="card"><div class="stat-label">Term</div><div>{{ $termResult->term->name }} ({{ $termResult->academicYear->name }})</div></div>
    <div class="card"><div class="stat-label">Average</div><div>{{ $termResult->average_percentage !== null ? $termResult->average_percentage.'%' : '—' }}</div></div>
    @if (\App\Models\Setting::rankingEnabled() && $termResult->position)
        <div class="card"><div class="stat-label">Position in class</div><div>{{ $termResult->position }}</div></div>
    @endif
    <div class="card"><div class="stat-label">Status</div><div><span class="badge badge-{{ $termResult->status === 'published' ? 'active' : 'inactive' }}">{{ ucfirst($termResult->status) }}</span></div></div>
</div>

<div class="card">
    <h2>Subjects</h2>
    @forelse ($breakdown as $subjectResult)
        <div class="table-wrap" style="margin-bottom:1.25rem;">
            <h3 style="margin-bottom:0.4rem;">{{ $subjectResult['subject']->name }}</h3>
            <table>
                <thead>
                    <tr>
                        @foreach ($subjectResult['assessments'] as $row)
                            <th>{{ $row['assessment']->name }} (/{{ $row['assessment']->max_score }})</th>
                        @endforeach
                        <th>Total</th>
                        <th>Grade</th>
                        <th>Remark</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        @foreach ($subjectResult['assessments'] as $row)
                            <td>{{ $row['recorded'] ? $row['score'] : '—' }}</td>
                        @endforeach
                        <td>{{ $subjectResult['total_score'] }} / {{ $subjectResult['total_max_score'] }}
                            @if ($subjectResult['percentage'] !== null) ({{ $subjectResult['percentage'] }}%) @endif
                        </td>
                        <td>{{ $subjectResult['grade'] ?? '—' }}</td>
                        <td>{{ $subjectResult['remark'] ?? '—' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @empty
        <p class="empty-state">No subjects to show for this term.</p>
    @endforelse
</div>
