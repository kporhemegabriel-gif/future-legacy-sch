@extends('layouts.app')

@section('title', 'Add Term')

@section('content')
    <h1>Add term</h1>
    <div class="card">
        @include('admin.terms._form')
    </div>
@endsection
