@extends('layouts.app')

@section('title', 'Subjects')

@section('content')
    <div class="page-header">
        <h1>Subjects</h1>
        <a href="{{ route('admin.subjects.create') }}" class="btn">+ Add subject</a>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Classes</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($subjects as $subject)
                        <tr>
                            <td>{{ $subject->code }}</td>
                            <td>{{ $subject->name }}</td>
                            <td>{{ $subject->classes_count }}</td>
                            <td><span class="badge badge-{{ $subject->status }}">{{ ucfirst($subject->status) }}</span></td>
                            <td class="actions">
                                <a href="{{ route('admin.subjects.edit', $subject) }}" class="btn btn-small btn-secondary">Edit</a>
                                <form method="POST" action="{{ route('admin.subjects.destroy', $subject) }}" onsubmit="return confirm('Delete this subject? Blocked while any enrollment references it.');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-link">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-state">No subjects yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $subjects->links() }}</div>
    </div>
@endsection
