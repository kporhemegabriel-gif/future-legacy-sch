@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
    <h1>{{ $student->fullName() }}</h1>

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
            <div><span class="badge badge-{{ $student->status }}">{{ ucfirst($student->status) }}</span></div>
        </div>
    </div>

    <div class="card">
        <h2>My details</h2>
        <div class="grid">
            <div><div class="stat-label">Email</div><div>{{ $student->user->email }}</div></div>
            <div><div class="stat-label">Phone</div><div>{{ $student->phone ?? '—' }}</div></div>
            <div><div class="stat-label">Date of birth</div><div>{{ optional($student->date_of_birth)->format('d M Y') ?? '—' }}</div></div>
            <div><div class="stat-label">Gender</div><div>{{ $student->gender ? ucfirst($student->gender) : '—' }}</div></div>
        </div>
    </div>

    <div class="card">
        <h2>Parents / guardians</h2>
        @forelse ($student->guardians as $guardian)
            <p>{{ $guardian->fullName() }} @if ($guardian->pivot->relationship) ({{ $guardian->pivot->relationship }}) @endif</p>
        @empty
            <p class="empty-state">No guardian on file. Contact the school office.</p>
        @endforelse
    </div>

    <div class="card">
        <h2>My subjects</h2>
        @forelse ($student->enrollments as $enrollment)
            <span class="badge badge-active" style="margin:0 0.3rem 0.3rem 0;">{{ $enrollment->subject->name }}</span>
        @empty
            <p class="empty-state">No subject enrollments yet.</p>
        @endforelse
        <p class="muted" style="margin-top:1rem;"><a href="{{ route('student.results.index') }}">View my published results &rarr;</a></p>
        <p class="muted"><a href="{{ route('student.attendance.index') }}">View my attendance &rarr;</a></p>
    </div>
@endsection
