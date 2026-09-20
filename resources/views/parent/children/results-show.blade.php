@extends('layouts.app')

@section('title', $student->fullName() . ' — ' . $termResult->term->name)

@section('content')
    <p><a href="{{ route('parent.children.results.index', $student) }}">&larr; Back to {{ $student->fullName() }}'s results</a></p>
    <h1>{{ $student->fullName() }} — {{ $termResult->term->name }}</h1>

    @include('results._breakdown')
@endsection
