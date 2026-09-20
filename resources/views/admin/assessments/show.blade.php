@extends('layouts.app')

@section('title', $assessment->name)

@section('content')
    <div class="page-header">
        <h1>{{ $assessment->name }}</h1>
        <div class="actions">
            <a href="{{ route('admin.assessments.scores.edit', $assessment) }}" class="btn">Enter / edit scores</a>
            <a href="{{ route('admin.assessments.edit', $assessment) }}" class="btn btn-secondary">Edit</a>
            <form method="POST" action="{{ route('admin.assessments.destroy', $assessment) }}" onsubmit="return confirm('Delete this assessment?');">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger">Delete</button>
            </form>
        </div>
    </div>

    <div class="grid">
        <div class="card"><div class="stat-label">Type</div><div>{{ $assessment->assessmentType->name }}</div></div>
        <div class="card"><div class="stat-label">Class</div><div>{{ $assessment->schoolClass->displayName() }}</div></div>
        <div class="card"><div class="stat-label">Subject</div><div>{{ $assessment->subject->name }}</div></div>
        <div class="card"><div class="stat-label">Term</div><div>{{ $assessment->term->name }} ({{ $assessment->academicYear->name }})</div></div>
        <div class="card"><div class="stat-label">Maximum score</div><div>{{ $assessment->max_score }}</div></div>
        <div class="card"><div class="stat-label">Status</div><div><span class="badge badge-{{ $assessment->status }}">{{ ucfirst($assessment->status) }}</span></div></div>
    </div>
@endsection
