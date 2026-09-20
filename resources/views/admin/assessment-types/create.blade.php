@extends('layouts.app')

@section('title', 'Add Assessment Type')

@section('content')
    <h1>Add assessment type</h1>
    <div class="card">
        @include('admin.assessment-types._form')
    </div>
@endsection
