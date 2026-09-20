@extends('layouts.app')

@section('title', 'Edit Assessment')

@section('content')
    <h1>Edit {{ $assessment->name }}</h1>
    @if ($assessment->scores()->exists())
        <div class="alert alert-error">This assessment already has recorded scores — class, subject, term, and the maximum score (below its highest recorded score) are locked.</div>
    @endif
    <div class="card">
        @include('admin.assessments._form')
    </div>
@endsection
