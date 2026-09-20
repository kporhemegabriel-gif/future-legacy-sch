@php $editing = isset($class); @endphp

<form method="POST" action="{{ $editing ? route('admin.classes.update', $class) : route('admin.classes.store') }}">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="field-row">
        <div class="field">
            <label for="name">Class name</label>
            <input type="text" id="name" name="name" value="{{ old('name', $class->name ?? '') }}" placeholder="e.g. Grade 9A" required>
            @error('name') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label for="section">Section (optional)</label>
            <input type="text" id="section" name="section" value="{{ old('section', $class->section ?? '') }}" placeholder="e.g. Blue">
        </div>
    </div>

    <div class="field-row">
        <div class="field">
            <label for="academic_year_id">Academic year</label>
            <select id="academic_year_id" name="academic_year_id" required>
                <option value="">Select year</option>
                @foreach ($academicYears as $year)
                    <option value="{{ $year->id }}" @selected(old('academic_year_id', $class->academic_year_id ?? '') == $year->id)>{{ $year->name }}</option>
                @endforeach
            </select>
            @error('academic_year_id') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label for="status">Status</label>
            <select id="status" name="status" required>
                @foreach (['active', 'inactive'] as $option)
                    <option value="{{ $option }}" @selected(old('status', $class->status ?? 'active') === $option)>{{ ucfirst($option) }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="actions">
        <button type="submit" class="btn">{{ $editing ? 'Save changes' : 'Create class' }}</button>
        <a href="{{ $editing ? route('admin.classes.show', $class) : route('admin.classes.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
