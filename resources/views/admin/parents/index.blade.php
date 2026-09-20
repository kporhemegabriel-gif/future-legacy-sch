@extends('layouts.app')

@section('title', 'Parents')

@section('content')
    <div class="page-header">
        <h1>Parents</h1>
        <a href="{{ route('admin.parents.create') }}" class="btn">+ Add parent</a>
    </div>

    <form method="GET" class="filter-bar">
        <input type="text" name="search" placeholder="Search name" value="{{ request('search') }}">
        <button type="submit" class="btn btn-secondary">Search</button>
    </form>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Children</th>
                        <th>Account</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($parents as $parentGuardian)
                        <tr>
                            <td>{{ $parentGuardian->fullName() }}</td>
                            <td>{{ $parentGuardian->user->email }}</td>
                            <td>{{ $parentGuardian->students->pluck('first_name')->implode(', ') ?: '—' }}</td>
                            <td><span class="badge badge-{{ $parentGuardian->user->status === 'active' ? 'active' : 'inactive' }}">{{ ucfirst($parentGuardian->user->status) }}</span></td>
                            <td class="actions">
                                <a href="{{ route('admin.parents.show', $parentGuardian) }}" class="btn btn-small btn-secondary">View</a>
                                <a href="{{ route('admin.parents.edit', $parentGuardian) }}" class="btn btn-small btn-secondary">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-state">No parents found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $parents->links() }}</div>
    </div>
@endsection
