@extends('layouts.app')

@section('title', 'My Attendance')

@section('content')
    <h1>My Attendance</h1>

    <form method="GET" class="filter-bar">
        <select name="term_id" onchange="this.form.submit()">
            <option value="">All terms</option>
            @foreach ($terms as $term)
                <option value="{{ $term->id }}" @selected($selectedTerm?->id === $term->id)>{{ $term->name }} ({{ $term->academicYear->name }})</option>
            @endforeach
        </select>
    </form>

    @include('attendance._summary')
@endsection
