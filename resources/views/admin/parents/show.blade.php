@extends('layouts.app')

@section('title', $parentGuardian->fullName())

@section('content')
    <div class="page-header">
        <h1>{{ $parentGuardian->fullName() }}</h1>
        <div class="actions">
            <a href="{{ route('admin.parents.edit', $parentGuardian) }}" class="btn btn-secondary">Edit</a>
            <form method="POST" action="{{ route('admin.parents.toggle-account', $parentGuardian) }}">
                @csrf @method('PATCH')
                <button type="submit" class="btn btn-secondary">
                    {{ $parentGuardian->user->status === 'active' ? 'Deactivate account' : 'Reactivate account' }}
                </button>
            </form>
            <form method="POST" action="{{ route('admin.parents.destroy', $parentGuardian) }}" onsubmit="return confirm('Permanently remove this parent account? This cannot be undone.');">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger">Delete</button>
            </form>
        </div>
    </div>

    <div class="card">
        <h2>Contact details</h2>
        <div class="grid">
            <div><div class="stat-label">Email</div><div>{{ $parentGuardian->user->email }}</div></div>
            <div><div class="stat-label">Phone</div><div>{{ $parentGuardian->phone ?? '—' }}</div></div>
            <div><div class="stat-label">Account status</div><div><span class="badge badge-{{ $parentGuardian->user->status === 'active' ? 'active' : 'inactive' }}">{{ ucfirst($parentGuardian->user->status) }}</span></div></div>
        </div>
        @if ($parentGuardian->address)
            <p class="muted">{{ $parentGuardian->address }}</p>
        @endif
    </div>

    <div class="card">
        <h2>Linked children</h2>
        @forelse ($parentGuardian->students as $child)
            <div class="table-wrap">
                <table>
                    <tr>
                        <td><a href="{{ route('admin.students.show', $child) }}">{{ $child->fullName() }}</a></td>
                        <td>{{ $child->schoolClass?->displayName() ?? '—' }}</td>
                        <td>{{ $child->pivot->relationship ?? '—' }}</td>
                        <td>@if ($child->pivot->is_primary) <span class="badge badge-active">Primary</span> @endif</td>
                    </tr>
                </table>
            </div>
        @empty
            <p class="empty-state">No children linked yet.</p>
        @endforelse
    </div>
@endsection
