@extends('layouts.app')

@section('title', 'Attendance')

@section('content')
    <div class="page-header">
        <h1>Attendance</h1>
        <a href="{{ route('admin.attendance.mark') }}" class="btn">Mark attendance</a>
    </div>

    <form method="GET" class="filter-bar">
        <select name="academic_year_id">
            <option value="">All years</option>
            @foreach ($academicYears as $year)
                <option value="{{ $year->id }}" @selected(request('academic_year_id') == $year->id)>{{ $year->name }}</option>
            @endforeach
        </select>
        <select name="term_id">
            <option value="">All terms</option>
            @foreach ($terms as $term)
                <option value="{{ $term->id }}" @selected(request('term_id') == $term->id)>{{ $term->name }}</option>
            @endforeach
        </select>
        <select name="class_id">
            <option value="">All classes</option>
            @foreach ($classes as $class)
                <option value="{{ $class->id }}" @selected(request('class_id') == $class->id)>{{ $class->displayName() }}</option>
            @endforeach
        </select>
        <select name="status">
            <option value="">All statuses</option>
            @foreach ($statuses as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <input type="date" name="date" value="{{ request('date') }}">
        <button type="submit" class="btn btn-secondary">Filter</button>
    </form>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead><tr><th>Date</th><th>Student</th><th>Class</th><th>Term</th><th>Status</th><th>Note</th></tr></thead>
                <tbody>
                    @forelse ($attendances as $record)
                        <tr>
                            <td>{{ $record->attendance_date->format('d M Y') }}</td>
                            <td><a href="{{ route('admin.students.attendance', $record->student) }}">{{ $record->student->fullName() }}</a></td>
                            <td>{{ $record->schoolClass->displayName() }}</td>
                            <td>{{ $record->term->name }}</td>
                            <td><span class="badge badge-{{ $record->status === 'present' ? 'active' : ($record->status === 'absent' ? 'withdrawn' : 'inactive') }}">{{ ucfirst($record->status) }}</span></td>
                            <td>{{ $record->note ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-state">No attendance records match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $attendances->links() }}</div>
    </div>
@endsection
