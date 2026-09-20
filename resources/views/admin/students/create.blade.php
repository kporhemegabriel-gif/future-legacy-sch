@extends('layouts.app')

@section('title', 'Add Student')

@section('content')
    <h1>Add student</h1>
    <div class="card">
        @include('admin.students._form')
    </div>
@endsection
