@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
    <h1>Admin dashboard</h1>
    <p>Welcome back, {{ auth()->user()->name }}.</p>

    <div class="grid">
        <div class="card">
            <div class="stat-number">{{ $stats['total_students'] }}</div>
            <div class="stat-label">Total students</div>
        </div>
        <div class="card">
            <div class="stat-number">{{ $stats['total_parents'] }}</div>
            <div class="stat-label">Total parents</div>
        </div>
        <div class="card">
            <div class="stat-number">{{ $stats['total_classes'] }}</div>
            <div class="stat-label">Total classes</div>
        </div>
    </div>

    <div class="card">
        <p style="color:#6b7280; font-size:0.9rem; margin:0;">
            Attendance summaries, outstanding fees, recent payments, and result
            activity will appear here as their respective phases are built
            (see the project README, sections 4–7).
        </p>
    </div>
@endsection
