@extends('layouts.app')

@section('title', 'Grading Scale')

@section('content')
    <div class="page-header">
        <h1>Grading Scale</h1>
        <a href="{{ route('admin.grade-bands.create', ['academic_year_id' => $selectedYear?->id]) }}" class="btn">+ Add grade band</a>
    </div>
    <p class="muted">
        Grading setup: <a href="{{ route('admin.terms.index') }}">Terms</a> &middot;
        <a href="{{ route('admin.assessment-types.index') }}">Assessment Types</a> &middot;
        <a href="{{ route('admin.grade-bands.index') }}">Grading Scale</a> &middot;
        <a href="{{ route('admin.settings.edit') }}">Result Settings</a>
    </p>
    <p class="muted">Each academic year has its own grading scale — e.g. 80-100 = A = Excellent. Ranges cannot overlap within the same year. Changing a year's scale never affects another year's already-computed results.</p>

    <form method="GET" class="filter-bar">
        <select name="academic_year_id" onchange="this.form.submit()">
            @foreach ($academicYears as $year)
                <option value="{{ $year->id }}" @selected($selectedYear?->id === $year->id)>{{ $year->name }}</option>
            @endforeach
        </select>
    </form>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead><tr><th>Range</th><th>Grade</th><th>Remark</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($gradeBands as $band)
                        <tr>
                            <td>{{ $band->min_score }}–{{ $band->max_score }}</td>
                            <td>{{ $band->grade }}</td>
                            <td>{{ $band->remark }}</td>
                            <td><span class="badge badge-{{ $band->status }}">{{ ucfirst($band->status) }}</span></td>
                            <td class="actions">
                                <a href="{{ route('admin.grade-bands.edit', $band) }}" class="btn btn-small btn-secondary">Edit</a>
                                <form method="POST" action="{{ route('admin.grade-bands.destroy', $band) }}" onsubmit="return confirm('Delete this grade band?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-link">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-state">No grading scale configured yet for {{ $selectedYear?->name ?? 'this year' }}. Results will show a percentage but no letter grade until you add bands.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
