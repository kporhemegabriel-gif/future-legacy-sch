@extends('layouts.app')

@section('title', 'Students')

@section('content')
    <div class="page-header">
        <h1>Students</h1>
        <a href="{{ route('admin.students.create') }}" class="btn">+ Add student</a>
    </div>

    <form method="GET" class="filter-bar">
        <input type="text" name="search" placeholder="Search name or admission #" value="{{ request('search') }}">
        <select name="class_id">
            <option value="">All classes</option>
            @foreach ($classes as $class)
                <option value="{{ $class->id }}" @selected(request('class_id') == $class->id)>{{ $class->displayName() }}</option>
            @endforeach
        </select>
        <select name="status">
            <option value="">All statuses</option>
            @foreach (['active', 'inactive', 'graduated', 'withdrawn'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-secondary">Filter</button>
    </form>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Admission #</th>
                        <th>Name</th>
                        <th>Class</th>
                        <th>Status</th>
                        <th>Account</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $student)
                        <tr>
                            <td>{{ $student->admission_number }}</td>
                            <td>{{ $student->fullName() }}</td>
                            <td>{{ $student->schoolClass?->displayName() ?? '—' }}</td>
                            <td><span class="badge badge-{{ $student->status }}">{{ ucfirst($student->status) }}</span></td>
                            <td><span class="badge badge-{{ $student->user->status === 'active' ? 'active' : 'inactive' }}">{{ ucfirst($student->user->status) }}</span></td>
                            <td class="actions">
                                <a href="{{ route('admin.students.show', $student) }}" class="btn btn-small btn-secondary">View</a>
                                <a href="{{ route('admin.students.edit', $student) }}" class="btn btn-small btn-secondary">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-state">No students match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $students->links() }}</div>
    </div>
@endsection
