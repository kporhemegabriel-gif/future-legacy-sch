@extends('layouts.app')

@section('title', 'Add Subject')

@section('content')
    <h1>Add subject</h1>
    <div class="card">
        @include('admin.subjects._form')
    </div>
@endsection
