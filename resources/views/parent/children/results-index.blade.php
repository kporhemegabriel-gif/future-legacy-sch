@extends('layouts.app')

@section('title', $student->fullName() . ' — Results')

@section('content')
    <p><a href="{{ route('parent.children.show', $student) }}">&larr; Back to {{ $student->fullName() }}</a></p>
    <h1>{{ $student->fullName() }} — Results</h1>

    @if ($termResults->isEmpty())
        <div class="card"><p>No published results yet for this child.</p></div>
    @else
        <div class="grid">
            @foreach ($termResults as $result)
                <div class="card">
                    <h2 style="margin-top:0;">{{ $result->term->name }}</h2>
                    <p class="muted">{{ $result->term->academicYear->name }}</p>
                    <p>Average: {{ $result->average_percentage !== null ? $result->average_percentage.'%' : '—' }}
                        @if (\App\Models\Setting::rankingEnabled() && $result->position) &middot; Position: {{ $result->position }} @endif
                    </p>
                    <a href="{{ route('parent.children.results.show', [$student, $result]) }}" class="btn btn-secondary btn-small">View details</a>
                </div>
            @endforeach
        </div>
    @endif
@endsection
