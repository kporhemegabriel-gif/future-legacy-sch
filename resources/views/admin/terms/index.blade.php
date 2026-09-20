@extends('layouts.app')

@section('title', 'Terms')

@section('content')
    <div class="page-header">
        <h1>Terms</h1>
        <a href="{{ route('admin.terms.create') }}" class="btn">+ Add term</a>
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
                <thead><tr><th>Name</th><th>Academic year</th><th>Current</th><th></th></tr></thead>
                <tbody>
                    @forelse ($terms as $term)
                        <tr>
                            <td>{{ $term->name }}</td>
                            <td>{{ $term->academicYear->name }}</td>
                            <td>@if ($term->is_current) <span class="badge badge-active">Current</span> @endif</td>
                            <td class="actions">
                                <a href="{{ route('admin.terms.edit', $term) }}" class="btn btn-small btn-secondary">Edit</a>
                                <form method="POST" action="{{ route('admin.terms.destroy', $term) }}" onsubmit="return confirm('Delete this term?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-link">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty-state">No terms yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $terms->links() }}</div>
    </div>
@endsection
