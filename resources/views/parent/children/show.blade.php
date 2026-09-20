@extends('layouts.app')

@section('title', $student->fullName())

@section('content')
    <p><a href="{{ route('parent.children.index') }}">&larr; Back to my children</a></p>
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
        <h2>Subjects</h2>
        @forelse ($student->enrollments as $enrollment)
            <span class="badge badge-active" style="margin:0 0.3rem 0.3rem 0;">{{ $enrollment->subject->name }}</span>
        @empty
            <p class="empty-state">No subject enrollments yet.</p>
        @endforelse
        <p class="muted" style="margin-top:1rem;"><a href="{{ route('parent.children.results.index', $student) }}">View published results &rarr;</a></p>
        <p class="muted"><a href="{{ route('parent.children.attendance', $student) }}">View attendance &rarr;</a></p>
    </div>
@endsection
