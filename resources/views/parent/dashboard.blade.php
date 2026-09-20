@extends('layouts.app')

@section('title', 'My Children')

@section('content')
    <h1>Welcome, {{ auth()->user()->name }}</h1>

    @if ($children->isNotEmpty())
        <div class="card">
            <table>
                <thead>
                    <tr><th>Name</th><th>Admission #</th><th>Class</th></tr>
                </thead>
                <tbody>
                    @foreach ($children as $child)
                        <tr>
                            <td>{{ $child->fullName() }}</td>
                            <td>{{ $child->admission_number }}</td>
                            <td>{{ $child->schoolClass->name ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="card">
            <p>No students are linked to your account yet. Contact the school administrator.</p>
        </div>
    @endif
@endsection
