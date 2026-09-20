@extends('layouts.app')

@section('title', 'My Children')

@section('content')
    <h1>My children</h1>

    @if ($children->isEmpty())
        <div class="card">
            <p>No students are linked to your account yet. Contact the school administrator.</p>
        </div>
    @else
        <div class="grid">
            @foreach ($children as $child)
                <div class="card">
                    <h2 style="margin-top:0;">{{ $child->fullName() }}</h2>
                    <p class="muted">{{ $child->admission_number }} &middot; {{ $child->schoolClass?->displayName() ?? 'Unassigned class' }}</p>
                    <a href="{{ route('parent.children.show', $child) }}" class="btn btn-secondary btn-small">View profile</a>
                </div>
            @endforeach
        </div>
    @endif
@endsection
