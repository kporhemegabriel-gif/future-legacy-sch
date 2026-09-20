@extends('layouts.app')

@section('title', 'Edit Parent')

@section('content')
    <h1>Edit {{ $parentGuardian->fullName() }}</h1>
    <div class="card">
        @include('admin.parents._form')
    </div>
@endsection
