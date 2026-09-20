@extends('layouts.app')

@section('title', 'Edit Student')

@section('content')
    <h1>Edit {{ $student->fullName() }}</h1>
    <div class="card">
        @include('admin.students._form')
    </div>
@endsection
