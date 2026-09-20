@php $editing = isset($assessmentType); @endphp

<form method="POST" action="{{ $editing ? route('admin.assessment-types.update', $assessmentType) : route('admin.assessment-types.store') }}">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="field-row">
        <div class="field">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" value="{{ old('name', $assessmentType->name ?? '') }}" placeholder="e.g. Continuous Assessment" required>
            @error('name') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label for="status">Status</label>
            <select id="status" name="status" required>
                @foreach (['active', 'inactive'] as $option)
                    <option value="{{ $option }}" @selected(old('status', $assessmentType->status ?? 'active') === $option)>{{ ucfirst($option) }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="actions">
        <button type="submit" class="btn">{{ $editing ? 'Save changes' : 'Create type' }}</button>
        <a href="{{ route('admin.assessment-types.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
