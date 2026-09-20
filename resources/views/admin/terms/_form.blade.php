@php $editing = isset($term); @endphp

<form method="POST" action="{{ $editing ? route('admin.terms.update', $term) : route('admin.terms.store') }}">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="field-row">
        <div class="field">
            <label for="academic_year_id">Academic year</label>
            <select id="academic_year_id" name="academic_year_id" required>
                <option value="">Select year</option>
                @foreach ($academicYears as $year)
                    <option value="{{ $year->id }}" @selected(old('academic_year_id', $term->academic_year_id ?? '') == $year->id)>{{ $year->name }}</option>
                @endforeach
            </select>
            @error('academic_year_id') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label for="name">Term name</label>
            <select id="name" name="name" required>
                @foreach (['First Term', 'Second Term', 'Third Term'] as $option)
                    <option value="{{ $option }}" @selected(old('name', $term->name ?? '') === $option)>{{ $option }}</option>
                @endforeach
            </select>
            @error('name') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label for="sequence">Sequence (1-3, for ordering)</label>
            <input type="number" id="sequence" name="sequence" min="1" max="3" value="{{ old('sequence', $term->sequence ?? 1) }}" required>
        </div>
    </div>

    <div class="field">
        <label class="inline" style="display:flex; align-items:center; gap:0.4rem;">
            <input type="checkbox" name="is_current" value="1" @checked(old('is_current', $term->is_current ?? false))>
            Set as the current term (only one term can be current at a time)
        </label>
    </div>

    <div class="actions">
        <button type="submit" class="btn">{{ $editing ? 'Save changes' : 'Create term' }}</button>
        <a href="{{ route('admin.terms.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
