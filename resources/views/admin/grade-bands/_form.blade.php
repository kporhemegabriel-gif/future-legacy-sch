@php $editing = isset($gradeBand); @endphp

<form method="POST" action="{{ $editing ? route('admin.grade-bands.update', $gradeBand) : route('admin.grade-bands.store') }}">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="field">
        <label for="academic_year_id">Academic year</label>
        <select id="academic_year_id" name="academic_year_id" required>
            <option value="">Select year</option>
            @foreach ($academicYears as $year)
                <option value="{{ $year->id }}" @selected(old('academic_year_id', $gradeBand->academic_year_id ?? $preselectedYearId ?? '') == $year->id)>{{ $year->name }}</option>
            @endforeach
        </select>
        @error('academic_year_id') <div class="error">{{ $message }}</div> @enderror
    </div>

    <div class="field-row">
        <div class="field">
            <label for="min_score">Minimum score (%)</label>
            <input type="number" id="min_score" name="min_score" min="0" max="100" value="{{ old('min_score', $gradeBand->min_score ?? '') }}" required>
        </div>
        <div class="field">
            <label for="max_score">Maximum score (%)</label>
            <input type="number" id="max_score" name="max_score" min="0" max="100" value="{{ old('max_score', $gradeBand->max_score ?? '') }}" required>
        </div>
    </div>
    @error('min_score') <div class="error" style="margin-bottom:1rem;">{{ $message }}</div> @enderror

    <div class="field-row">
        <div class="field">
            <label for="grade">Grade</label>
            <input type="text" id="grade" name="grade" value="{{ old('grade', $gradeBand->grade ?? '') }}" placeholder="e.g. A" required>
        </div>
        <div class="field">
            <label for="remark">Remark</label>
            <input type="text" id="remark" name="remark" value="{{ old('remark', $gradeBand->remark ?? '') }}" placeholder="e.g. Excellent" required>
        </div>
        <div class="field">
            <label for="status">Status</label>
            <select id="status" name="status" required>
                @foreach (['active', 'inactive'] as $option)
                    <option value="{{ $option }}" @selected(old('status', $gradeBand->status ?? 'active') === $option)>{{ ucfirst($option) }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="actions">
        <button type="submit" class="btn">{{ $editing ? 'Save changes' : 'Create grade band' }}</button>
        <a href="{{ route('admin.grade-bands.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
