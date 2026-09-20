@php $editing = isset($subject); @endphp

<form method="POST" action="{{ $editing ? route('admin.subjects.update', $subject) : route('admin.subjects.store') }}">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="field-row">
        <div class="field">
            <label for="code">Subject code</label>
            <input type="text" id="code" name="code" value="{{ old('code', $subject->code ?? '') }}" placeholder="e.g. MATH101" required>
            @error('code') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label for="name">Subject name</label>
            <input type="text" id="name" name="name" value="{{ old('name', $subject->name ?? '') }}" placeholder="e.g. Mathematics" required>
            @error('name') <div class="error">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="field">
        <label for="status">Status</label>
        <select id="status" name="status" required>
            @foreach (['active', 'inactive'] as $option)
                <option value="{{ $option }}" @selected(old('status', $subject->status ?? 'active') === $option)>{{ ucfirst($option) }}</option>
            @endforeach
        </select>
    </div>

    <div class="actions">
        <button type="submit" class="btn">{{ $editing ? 'Save changes' : 'Create subject' }}</button>
        <a href="{{ route('admin.subjects.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
