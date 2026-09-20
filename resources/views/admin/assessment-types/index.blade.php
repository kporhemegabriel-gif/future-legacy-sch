@extends('layouts.app')

@section('title', 'Assessment Types')

@section('content')
    <div class="page-header">
        <h1>Assessment Types</h1>
        <a href="{{ route('admin.assessment-types.create') }}" class="btn">+ Add type</a>
    </div>
    <p class="muted">
        Grading setup: <a href="{{ route('admin.terms.index') }}">Terms</a> &middot;
        <a href="{{ route('admin.assessment-types.index') }}">Assessment Types</a> &middot;
        <a href="{{ route('admin.grade-bands.index') }}">Grading Scale</a> &middot;
        <a href="{{ route('admin.settings.edit') }}">Result Settings</a>
    </p>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead><tr><th>Name</th><th>Used by</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($assessmentTypes as $type)
                        <tr>
                            <td>{{ $type->name }}</td>
                            <td>{{ $type->assessments_count }} assessment(s)</td>
                            <td><span class="badge badge-{{ $type->status }}">{{ ucfirst($type->status) }}</span></td>
                            <td class="actions">
                                <a href="{{ route('admin.assessment-types.edit', $type) }}" class="btn btn-small btn-secondary">Edit</a>
                                <form method="POST" action="{{ route('admin.assessment-types.destroy', $type) }}" onsubmit="return confirm('Delete this assessment type?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-link">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty-state">No assessment types yet — e.g. "Continuous Assessment", "Test", "Examination".</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $assessmentTypes->links() }}</div>
    </div>
@endsection
