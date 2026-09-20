@php $editing = isset($assessment); @endphp

<form method="POST" action="{{ $editing ? route('admin.assessments.update', $assessment) : route('admin.assessments.store') }}" id="assessment-form">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="field">
        <label for="name">Assessment name</label>
        <input type="text" id="name" name="name" value="{{ old('name', $assessment->name ?? '') }}" placeholder="e.g. Mid-Term Test 1" required>
        @error('name') <div class="error">{{ $message }}</div> @enderror
    </div>

    <div class="field-row">
        <div class="field">
            <label for="assessment_type_id">Type</label>
            <select id="assessment_type_id" name="assessment_type_id" required>
                <option value="">Select type</option>
                @foreach ($assessmentTypes as $type)
                    <option value="{{ $type->id }}" @selected(old('assessment_type_id', $assessment->assessment_type_id ?? '') == $type->id)>{{ $type->name }}</option>
                @endforeach
            </select>
            @error('assessment_type_id') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label for="term_id">Term</label>
            <select id="term_id" name="term_id" required>
                <option value="">Select term</option>
                @foreach ($terms as $term)
                    <option value="{{ $term->id }}" data-year="{{ $term->academic_year_id }}" @selected(old('term_id', $assessment->term_id ?? '') == $term->id)>{{ $term->name }} ({{ $term->academicYear->name }})</option>
                @endforeach
            </select>
            @error('term_id') <div class="error">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="field-row">
        <div class="field">
            <label for="class_id">Class (only classes in the selected term's year are shown)</label>
            <select id="class_id" name="class_id" required>
                <option value="">Select term first</option>
                @foreach ($classes as $class)
                    <option value="{{ $class->id }}" data-year="{{ $class->academic_year_id }}" @selected(old('class_id', $assessment->class_id ?? '') == $class->id)>{{ $class->displayName() }}</option>
                @endforeach
            </select>
            @error('class_id') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label for="subject_id">Subject (only subjects assigned to the selected class are shown)</label>
            <select id="subject_id" name="subject_id" required>
                <option value="">Select class first</option>
                @foreach ($subjects as $subject)
                    <option value="{{ $subject->id }}" @selected(old('subject_id', $assessment->subject_id ?? '') == $subject->id)>{{ $subject->name }} ({{ $subject->code }})</option>
                @endforeach
            </select>
            @error('subject_id') <div class="error">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="field-row">
        <div class="field">
            <label for="max_score">Maximum score</label>
            <input type="number" id="max_score" name="max_score" min="1" max="1000" value="{{ old('max_score', $assessment->max_score ?? '') }}" required>
            @error('max_score') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label for="assessment_date">Date (optional)</label>
            <input type="date" id="assessment_date" name="assessment_date" value="{{ old('assessment_date', optional($assessment->assessment_date ?? null)->format('Y-m-d')) }}">
        </div>
        <div class="field">
            <label for="status">Status</label>
            <select id="status" name="status" required>
                @foreach (['active', 'inactive'] as $option)
                    <option value="{{ $option }}" @selected(old('status', $assessment->status ?? 'active') === $option)>{{ ucfirst($option) }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="actions">
        <button type="submit" class="btn">{{ $editing ? 'Save changes' : 'Create assessment' }}</button>
        <a href="{{ $editing ? route('admin.assessments.show', $assessment) : route('admin.assessments.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>

<script>
(function () {
    // Client-side convenience filtering only — StoreAssessmentRequest /
    // UpdateAssessmentRequest enforce the real rule server-side regardless.
    var classSubjectMap = @json($classSubjectMap ?? []);
    var termYearMap = @json($termYearMap ?? []);

    var termSelect = document.getElementById('term_id');
    var classSelect = document.getElementById('class_id');
    var subjectSelect = document.getElementById('subject_id');

    function filterClassesByTerm() {
        var year = termYearMap[termSelect.value];
        Array.from(classSelect.options).forEach(function (opt) {
            if (!opt.value) return;
            opt.hidden = !!year && opt.dataset.year != year;
        });
    }

    function filterSubjectsByClass() {
        var allowed = classSubjectMap[classSelect.value] || [];
        Array.from(subjectSelect.options).forEach(function (opt) {
            if (!opt.value) return;
            opt.hidden = allowed.indexOf(parseInt(opt.value, 10)) === -1;
        });
    }

    termSelect.addEventListener('change', filterClassesByTerm);
    classSelect.addEventListener('change', filterSubjectsByClass);

    filterClassesByTerm();
    filterSubjectsByClass();
})();
</script>
