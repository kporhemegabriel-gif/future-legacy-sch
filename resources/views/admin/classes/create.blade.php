@extends('layouts.app')

@section('title', 'Add Class')

@section('content')
    <h1>Add class</h1>
    <div class="card">
        @include('admin.classes._form')
    </div>
@endsection
