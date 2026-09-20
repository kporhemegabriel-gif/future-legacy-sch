@extends('layouts.app')

@section('title', $class->displayName())

@section('content')
    <div class="page-header">
        <h1>{{ $class->displayName() }}</h1>
        <div class="actions">
            <a href="{{ route('admin.classes.edit', $class) }}" class="btn btn-secondary">Edit</a>
            <a href="{{ route('admin.classes.subjects.edit', $class) }}" class="btn btn-secondary">Manage subjects</a>
            <form method="POST" action="{{ route('admin.classes.destroy', $class) }}" onsubmit="return confirm('Delete this class? This is blocked while students are still assigned to it.');">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger">Delete</button>
            </form>
        </div>
    </div>

    <p class="muted">{{ $class->academicYear->name }} &middot; <span class="badge badge-{{ $class->status }}">{{ ucfirst($class->status) }}</span></p>

    <div class="card">
        <h2>Subjects taught</h2>
        @forelse ($class->subjects as $subject)
            <span class="badge badge-active" style="margin:0 0.3rem 0.3rem 0;">{{ $subject->name }} ({{ $subject->code }})</span>
        @empty
            <p class="empty-state">No subjects assigned yet. <a href="{{ route('admin.classes.subjects.edit', $class) }}">Assign subjects</a>.</p>
        @endforelse
    </div>

    <div class="card">
        <h2>Students in this class</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Admission #</th><th>Name</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($class->students as $student)
                        <tr>
                            <td>{{ $student->admission_number }}</td>
                            <td>{{ $student->fullName() }}</td>
                            <td><span class="badge badge-{{ $student->status }}">{{ ucfirst($student->status) }}</span></td>
                            <td><a href="{{ route('admin.students.show', $student) }}" class="btn btn-small btn-secondary">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty-state">No students assigned to this class yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
