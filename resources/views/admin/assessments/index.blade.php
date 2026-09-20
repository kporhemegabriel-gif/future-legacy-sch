@extends('layouts.app')

@section('title', 'Assessments')

@section('content')
    <div class="page-header">
        <h1>Assessments</h1>
        <a href="{{ route('admin.assessments.create') }}" class="btn">+ Add assessment</a>
    </div>

    <form method="GET" class="filter-bar">
        <select name="term_id">
            <option value="">All terms</option>
            @foreach ($terms as $term)
                <option value="{{ $term->id }}" @selected(request('term_id') == $term->id)>{{ $term->name }} ({{ $term->academicYear->name }})</option>
            @endforeach
        </select>
        <select name="class_id">
            <option value="">All classes</option>
            @foreach ($classes as $class)
                <option value="{{ $class->id }}" @selected(request('class_id') == $class->id)>{{ $class->displayName() }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-secondary">Filter</button>
    </form>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead><tr><th>Name</th><th>Type</th><th>Class</th><th>Subject</th><th>Term</th><th>Max</th><th>Scores</th><th></th></tr></thead>
                <tbody>
                    @forelse ($assessments as $assessment)
                        <tr>
                            <td>{{ $assessment->name }}</td>
                            <td>{{ $assessment->assessmentType->name }}</td>
                            <td>{{ $assessment->schoolClass->displayName() }}</td>
                            <td>{{ $assessment->subject->name }}</td>
                            <td>{{ $assessment->term->name }}</td>
                            <td>{{ $assessment->max_score }}</td>
                            <td>{{ $assessment->scores_count }}</td>
                            <td class="actions">
                                <a href="{{ route('admin.assessments.show', $assessment) }}" class="btn btn-small btn-secondary">View</a>
                                <a href="{{ route('admin.assessments.scores.edit', $assessment) }}" class="btn btn-small btn-secondary">Scores</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="empty-state">No assessments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $assessments->links() }}</div>
    </div>
@endsection
