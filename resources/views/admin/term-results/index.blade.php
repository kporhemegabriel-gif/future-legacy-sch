@extends('layouts.app')

@section('title', 'Term Results')

@section('content')
    <h1>Term Results</h1>

    <form method="GET" class="filter-bar">
        <select name="class_id">
            <option value="">Select class</option>
            @foreach ($classes as $class)
                <option value="{{ $class->id }}" @selected($selectedClass?->id == $class->id)>{{ $class->displayName() }}</option>
            @endforeach
        </select>
        <select name="term_id">
            <option value="">Select term</option>
            @foreach ($terms as $term)
                <option value="{{ $term->id }}" @selected($selectedTerm?->id == $term->id)>{{ $term->name }} ({{ $term->academicYear->name }})</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-secondary">View</button>
    </form>

    @if ($selectedClass && $selectedTerm)
        <form method="POST" action="{{ route('admin.term-results.compute') }}" style="margin-bottom:1.25rem;">
            @csrf
            <input type="hidden" name="class_id" value="{{ $selectedClass->id }}">
            <input type="hidden" name="term_id" value="{{ $selectedTerm->id }}">
            <button type="submit" class="btn">Compute / recompute results for {{ $selectedClass->displayName() }} — {{ $selectedTerm->name }}</button>
        </form>

        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Position</th><th>Student</th><th>Subjects</th><th>Average %</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($termResults as $result)
                            <tr>
                                <td>{{ $result->position ?? '—' }}</td>
                                <td>{{ $result->student->fullName() }}</td>
                                <td>{{ $result->total_subjects }}</td>
                                <td>{{ $result->average_percentage !== null ? $result->average_percentage.'%' : '—' }}</td>
                                <td><span class="badge badge-{{ $result->status === 'published' ? 'active' : 'inactive' }}">{{ ucfirst($result->status) }}</span></td>
                                <td class="actions">
                                    <a href="{{ route('admin.term-results.show', $result) }}" class="btn btn-small btn-secondary">View</a>
                                    @if ($result->status === 'published')
                                        <form method="POST" action="{{ route('admin.term-results.unpublish', $result) }}">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="btn-link">Unpublish</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.term-results.publish', $result) }}">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="btn-link">Publish</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="empty-state">No results computed yet for this class/term — use the button above.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <p class="muted">Select a class and a term to view or compute results.</p>
    @endif
@endsection
