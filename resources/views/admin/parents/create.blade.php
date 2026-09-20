@extends('layouts.app')

@section('title', 'Add Parent')

@section('content')
    <h1>Add parent</h1>
    <div class="card">
        @include('admin.parents._form')
    </div>
@endsection
