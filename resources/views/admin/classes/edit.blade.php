@extends('layouts.app')

@section('title', 'Edit Class')

@section('content')
    <h1>Edit {{ $class->displayName() }}</h1>
    <div class="card">
        @include('admin.classes._form')
    </div>
@endsection
