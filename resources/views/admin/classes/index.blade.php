@extends('layouts.app')

@section('title', 'Classes')

@section('content')
    <div class="page-header">
        <h1>Classes</h1>
        <a href="{{ route('admin.classes.create') }}" class="btn">+ Add class</a>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Academic year</th>
                        <th>Students</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($classes as $class)
                        <tr>
                            <td>{{ $class->displayName() }}</td>
                            <td>{{ $class->academicYear->name }}</td>
                            <td>{{ $class->students_count }}</td>
                            <td><span class="badge badge-{{ $class->status }}">{{ ucfirst($class->status) }}</span></td>
                            <td class="actions">
                                <a href="{{ route('admin.classes.show', $class) }}" class="btn btn-small btn-secondary">View</a>
                                <a href="{{ route('admin.classes.edit', $class) }}" class="btn btn-small btn-secondary">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-state">No classes yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $classes->links() }}</div>
    </div>
@endsection
