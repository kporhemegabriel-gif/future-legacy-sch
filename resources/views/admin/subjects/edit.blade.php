@extends('layouts.app')

@section('title', 'Edit Subject')

@section('content')
    <h1>Edit {{ $subject->name }}</h1>
    <div class="card">
        @include('admin.subjects._form')
    </div>
@endsection
