@extends('layouts.app')

@section('title', 'Result: ' . $termResult->term->name)

@section('content')
    <p><a href="{{ route('student.results.index') }}">&larr; Back to my results</a></p>
    <h1>{{ $termResult->term->name }} Result</h1>

    @include('results._breakdown')
@endsection
