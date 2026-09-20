@extends('layouts.app')

@section('title', 'Term Result')

@section('content')
    <p><a href="{{ route('admin.term-results.index', ['class_id' => $termResult->class_id, 'term_id' => $termResult->term_id]) }}">&larr; Back to list</a></p>
    <h1>{{ $termResult->student->fullName() }} — {{ $termResult->term->name }}</h1>

    <div class="actions" style="margin-bottom:1rem;">
        @if ($termResult->status === 'published')
            <form method="POST" action="{{ route('admin.term-results.unpublish', $termResult) }}">
                @csrf @method('PATCH')
                <button type="submit" class="btn btn-secondary">Unpublish</button>
            </form>
        @else
            <form method="POST" action="{{ route('admin.term-results.publish', $termResult) }}">
                @csrf @method('PATCH')
                <button type="submit" class="btn">Publish</button>
            </form>
        @endif
    </div>

    @include('results._breakdown')
@endsection
