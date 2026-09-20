@extends('layouts.app')

@section('title', 'Edit Assessment Type')

@section('content')
    <h1>Edit {{ $assessmentType->name }}</h1>
    <div class="card">
        @include('admin.assessment-types._form')
    </div>
@endsection
