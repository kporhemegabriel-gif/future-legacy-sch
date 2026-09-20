@extends('layouts.app')

@section('title', 'Edit Term')

@section('content')
    <h1>Edit {{ $term->name }}</h1>
    <div class="card">
        @include('admin.terms._form')
    </div>
@endsection
