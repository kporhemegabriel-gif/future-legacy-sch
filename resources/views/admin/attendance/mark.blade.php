@extends('layouts.app')

@section('title', 'Mark Attendance')

@section('content')
    <h1>Mark Attendance</h1>

    <form method="GET" class="filter-bar">
        <select name="term_id" required>
            <option value="">Select term</option>
            @foreach ($terms as $term)
                <option value="{{ $term->id }}" data-year="{{ $term->academic_year_id }}" @selected($selectedTerm?->id == $term->id)>{{ $term->name }} ({{ $term->academicYear->name }})</option>
            @endforeach
        </select>
        <select name="class_id" required>
            <option value="">Select class</option>
            @foreach ($classes as $class)
                <option value="{{ $class->id }}" data-year="{{ $class->academic_year_id }}" @selected($selectedClass?->id == $class->id)>{{ $class->displayName() }}</option>
            @endforeach
        </select>
        <input type="date" name="attendance_date" value="{{ $selectedDate }}" required>
        <button type="submit" class="btn btn-secondary">Load class</button>
    </form>

    @if ($selectedClass && $selectedTerm && $selectedDate)
        <div class="card">
            <div class="page-header">
                <h2 style="margin:0;">{{ $selectedClass->displayName() }} &middot; {{ $selectedDate }}</h2>
                <button type="button" id="mark-all-present" class="btn btn-small btn-secondary">Mark all present</button>
            </div>

            @if ($roster->isEmpty())
                <p class="empty-state">No active students in this class.</p>
            @else
                <form method="POST" action="{{ route('admin.attendance.store') }}">
                    @csrf
                    <input type="hidden" name="class_id" value="{{ $selectedClass->id }}">
                    <input type="hidden" name="term_id" value="{{ $selectedTerm->id }}">
                    <input type="hidden" name="attendance_date" value="{{ $selectedDate }}">

                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Admission #</th><th>Student</th><th>Status</th><th>Note</th></tr></thead>
                            <tbody>
                                @foreach ($roster as $index => $student)
                                    @php $current = $existing->get($student->id); @endphp
                                    <tr>
                                        <td>{{ $student->admission_number }}</td>
                                        <td>
                                            {{ $student->fullName() }}
                                            <input type="hidden" name="records[{{ $index }}][student_id]" value="{{ $student->id }}">
                                        </td>
                                        <td>
                                            <select name="records[{{ $index }}][status]" class="attendance-status" required>
                                                @foreach ($statuses as $status)
                                                    <option value="{{ $status }}" @selected(old("records.$index.status", $current?->status ?? 'present') === $status)>{{ ucfirst($status) }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="records[{{ $index }}][note]" value="{{ old("records.$index.note", $current?->note) }}" placeholder="Optional" style="width:10rem;">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="actions" style="margin-top:1rem;">
                        <button type="submit" class="btn">Save attendance</button>
                    </div>
                </form>
            @endif
        </div>
    @else
        <p class="muted">Select a term, class, and date to load the class roster.</p>
    @endif

    <script>
    (function () {
        var button = document.getElementById('mark-all-present');
        if (!button) return;
        button.addEventListener('click', function () {
            document.querySelectorAll('select.attendance-status').forEach(function (select) {
                select.value = 'present';
            });
        });
    })();
    </script>
@endsection
