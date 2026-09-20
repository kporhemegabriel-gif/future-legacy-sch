@extends('layouts.app')

@section('title', 'My Dashboard')

@section('content')
    <h1>Welcome, {{ auth()->user()->name }}</h1>

    @if ($student)
        <div class="card">
            <table>
                <tr><th>Admission number</th><td>{{ $student->admission_number }}</td></tr>
                <tr><th>Class</th><td>{{ $student->schoolClass->name ?? '—' }}</td></tr>
                <tr><th>Academic year</th><td>{{ $student->academicYear->name ?? '—' }}</td></tr>
            </table>
        </div>
        <p style="color:#6b7280; font-size:0.9rem;">
            Assessment scores, grades, attendance, and report cards will
            appear here once those phases are built.
        </p>
    @else
        <div class="card">
            <p>No student profile is linked to this account yet. Contact the school administrator.</p>
        </div>
    @endif
@endsection
