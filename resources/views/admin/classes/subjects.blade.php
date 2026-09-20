@extends('layouts.app')

@section('title', 'Assign Subjects')

@section('content')
    <h1>Subjects for {{ $class->displayName() }}</h1>

    <div class="card">
        <form method="POST" action="{{ route('admin.classes.subjects.update', $class) }}">
            @csrf
            @method('PUT')

            @forelse ($subjects as $subject)
                <label class="link-row" style="grid-template-columns: auto 1fr;">
                    <input type="checkbox" name="subject_ids[]" value="{{ $subject->id }}"
                        @checked($class->subjects->contains($subject->id))>
                    <span>{{ $subject->name }} ({{ $subject->code }})</span>
                </label>
            @empty
                <p class="empty-state">No active subjects yet. <a href="{{ route('admin.subjects.create') }}">Add one first</a>.</p>
            @endforelse

            <div class="actions" style="margin-top:1rem;">
                <button type="submit" class="btn">Save subjects</button>
                <a href="{{ route('admin.classes.show', $class) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
@endsection
