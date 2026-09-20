@extends('layouts.app')

@section('title', 'Add Grade Band')

@section('content')
    <h1>Add grade band</h1>
    <div class="card">
        @include('admin.grade-bands._form')
    </div>
@endsection
