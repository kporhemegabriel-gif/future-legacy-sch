@extends('layouts.app')

@section('title', $student->fullName())

@section('content')
    <div class="page-header">
        <h1>{{ $student->fullName() }}</h1>
        <div class="actions">
            <a href="{{ route('admin.students.edit', $student) }}" class="btn btn-secondary">Edit</a>
            <a href="{{ route('admin.students.attendance', $student) }}" class="btn btn-secondary">Attendance</a>
            <form method="POST" action="{{ route('admin.students.toggle-account', $student) }}">
                @csrf @method('PATCH')
                <button type="submit" class="btn btn-secondary">
                    {{ $student->user->status === 'active' ? 'Deactivate account' : 'Reactivate account' }}
                </button>
            </form>
            <form method="POST" action="{{ route('admin.students.destroy', $student) }}" onsubmit="return confirm('Permanently remove this student and their account? This cannot be undone.');">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger">Delete</button>
            </form>
        </div>
    </div>

    <div class="grid">
        <div class="card">
            <div class="stat-label">Admission number</div>
            <div>{{ $student->admission_number }}</div>
        </div>
        <div class="card">
            <div class="stat-label">Class</div>
            <div>{{ $student->schoolClass?->displayName() ?? '—' }}</div>
        </div>
        <div class="card">
            <div class="stat-label">Academic year</div>
            <div>{{ $student->academicYear?->name ?? '—' }}</div>
        </div>
        <div class="card">
            <div class="stat-label">Status</div>
            <div>
                <span class="badge badge-{{ $student->status }}">{{ ucfirst($student->status) }}</span>
                <span class="badge badge-{{ $student->user->status === 'active' ? 'active' : 'inactive' }}">Account {{ $student->user->status }}</span>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>Details</h2>
        <div class="grid">
            <div><div class="stat-label">Email</div><div>{{ $student->user->email }}</div></div>
            <div><div class="stat-label">Phone</div><div>{{ $student->phone ?? '—' }}</div></div>
            <div><div class="stat-label">Date of birth</div><div>{{ optional($student->date_of_birth)->format('d M Y') ?? '—' }}</div></div>
            <div><div class="stat-label">Gender</div><div>{{ $student->gender ? ucfirst($student->gender) : '—' }}</div></div>
            <div><div class="stat-label">Admission date</div><div>{{ $student->admission_date->format('d M Y') }}</div></div>
        </div>
        @if ($student->address)
            <p class="muted">{{ $student->address }}</p>
        @endif
    </div>

    <div class="card">
        <h2>Parents / Guardians</h2>
        @forelse ($student->guardians as $guardian)
            <div class="table-wrap">
                <table>
                    <tr>
                        <td>
                            <a href="{{ route('admin.parents.show', $guardian) }}">{{ $guardian->fullName() }}</a>
                            @if ($guardian->pivot->is_primary)
                                <span class="badge badge-active">Primary</span>
                            @endif
                        </td>
                        <td>{{ $guardian->pivot->relationship ?? '—' }}</td>
                    </tr>
                </table>
            </div>
        @empty
            <p class="empty-state">No guardians linked yet. Link one from the <a href="{{ route('admin.parents.index') }}">Parents</a> page.</p>
        @endforelse
    </div>

    <div class="card">
        <h2>Subject enrollment</h2>
        @forelse ($student->enrollments as $enrollment)
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Subject</th><th>Academic year</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $enrollment->subject->name }} ({{ $enrollment->subject->code }})</td>
                            <td>{{ $enrollment->academicYear->name }}</td>
                            <td><span class="badge badge-active">{{ ucfirst($enrollment->status) }}</span></td>
                            <td>
                                <form method="POST" action="{{ route('admin.students.enrollments.destroy', [$student, $enrollment]) }}" onsubmit="return confirm('Remove this enrollment?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-link">Remove</button>
                                </form>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @empty
            <p class="empty-state">Not enrolled in any subjects yet.</p>
        @endforelse

        <h2 style="margin-top:1.5rem;">Enroll in a subject</h2>
        @if (! $student->class_id)
            <p class="empty-state">This student isn't assigned to a class yet. Assign a class first — enrollment is only allowed in subjects the student's class teaches.</p>
        @elseif ($enrollableSubjects->isEmpty())
            <p class="empty-state">
                {{ $student->schoolClass->displayName() }} has no subjects assigned yet.
                <a href="{{ route('admin.classes.subjects.edit', $student->schoolClass) }}">Assign subjects to this class</a> first.
            </p>
        @else
            <form method="POST" action="{{ route('admin.students.enrollments.store', $student) }}">
                @csrf
                <div class="field-row">
                    <div class="field">
                        <label for="subject_id">Subject (only subjects assigned to {{ $student->schoolClass->displayName() }} are listed)</label>
                        <select id="subject_id" name="subject_id" required>
                            <option value="">Select subject</option>
                            @foreach ($enrollableSubjects as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->name }} ({{ $subject->code }})</option>
                            @endforeach
                        </select>
                        @error('subject_id') <div class="error">{{ $message }}</div> @enderror
                    </div>
                </div>
                <button type="submit" class="btn btn-secondary">Enroll</button>
            </form>
        @endif
    </div>
@endsection
