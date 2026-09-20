@extends('layouts.app')

@section('title', 'Edit Grade Band')

@section('content')
    <h1>Edit grade band: {{ $gradeBand->grade }}</h1>
    <div class="card">
        @include('admin.grade-bands._form')
    </div>
@endsection
