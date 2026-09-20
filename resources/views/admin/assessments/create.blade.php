@extends('layouts.app')

@section('title', 'Add Assessment')

@section('content')
    <h1>Add assessment</h1>
    <div class="card">
        @include('admin.assessments._form')
    </div>
@endsection
