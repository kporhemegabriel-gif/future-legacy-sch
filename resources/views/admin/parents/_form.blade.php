@php
    $editing = isset($parentGuardian);
    $existingLinks = $editing
        ? $parentGuardian->students->map(fn ($s) => [
            'student_id' => $s->id,
            'relationship' => $s->pivot->relationship,
            'is_primary' => (bool) $s->pivot->is_primary,
        ])->values()
        : collect(old('links', []));
@endphp

<form method="POST" action="{{ $editing ? route('admin.parents.update', $parentGuardian) : route('admin.parents.store') }}">
    @csrf
    @if ($editing) @method('PUT') @endif

    <h2>Account</h2>
    <div class="field-row">
        <div class="field">
            <label for="name">Full name (for login)</label>
            <input type="text" id="name" name="name" value="{{ old('name', $editing ? $parentGuardian->user->name : '') }}" required>
            @error('name') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email', $editing ? $parentGuardian->user->email : '') }}" required>
            @error('email') <div class="error">{{ $message }}</div> @enderror
        </div>
    </div>
    <div class="field">
        <label for="password">{{ $editing ? 'New password (leave blank to keep current)' : 'Password' }}</label>
        <input type="password" id="password" name="password" {{ $editing ? '' : 'required' }} minlength="8">
        @error('password') <div class="error">{{ $message }}</div> @enderror
    </div>

    <h2>Parent / guardian profile</h2>
    <div class="field-row">
        <div class="field">
            <label for="first_name">First name</label>
            <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $parentGuardian->first_name ?? '') }}" required>
            @error('first_name') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label for="last_name">Last name</label>
            <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $parentGuardian->last_name ?? '') }}" required>
            @error('last_name') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label for="phone">Phone</label>
            <input type="text" id="phone" name="phone" value="{{ old('phone', $parentGuardian->phone ?? '') }}">
        </div>
    </div>
    <div class="field">
        <label for="address">Address</label>
        <textarea id="address" name="address" rows="2">{{ old('address', $parentGuardian->address ?? '') }}</textarea>
    </div>

    <h2>Linked children</h2>
    <p class="muted">A parent account can be linked to more than one student. Mark one link "primary" per child if this guardian is the main contact.</p>

    <div id="link-rows"></div>
    <button type="button" id="add-link-row" class="btn btn-secondary btn-small">+ Add child</button>

    <template id="link-row-template">
        <div class="link-row">
            <select class="link-student" required>
                <option value="">Select student</option>
                @foreach ($students as $option)
                    <option value="{{ $option->id }}">{{ $option->fullName() }} ({{ $option->admission_number }})</option>
                @endforeach
            </select>
            <input type="text" class="link-relationship" placeholder="Relationship (e.g. Mother)">
            <label class="inline"><input type="checkbox" class="link-primary"> Primary</label>
            <button type="button" class="btn-link remove-link-row">Remove</button>
        </div>
    </template>

    <div class="actions" style="margin-top:1.5rem;">
        <button type="submit" class="btn">{{ $editing ? 'Save changes' : 'Create parent' }}</button>
        <a href="{{ $editing ? route('admin.parents.show', $parentGuardian) : route('admin.parents.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>

<script>
(function () {
    const container = document.getElementById('link-rows');
    const template = document.getElementById('link-row-template');
    const addButton = document.getElementById('add-link-row');
    let rowIndex = 0;

    function addRow(prefill) {
        const fragment = template.content.cloneNode(true);
        const row = fragment.querySelector('.link-row');
        const index = rowIndex++;

        const studentSelect = row.querySelector('.link-student');
        const relationshipInput = row.querySelector('.link-relationship');
        const primaryCheckbox = row.querySelector('.link-primary');

        studentSelect.name = `links[${index}][student_id]`;
        relationshipInput.name = `links[${index}][relationship]`;
        primaryCheckbox.name = `links[${index}][is_primary]`;
        primaryCheckbox.value = '1';

        if (prefill) {
            studentSelect.value = prefill.student_id ?? '';
            relationshipInput.value = prefill.relationship ?? '';
            primaryCheckbox.checked = !!prefill.is_primary;
        }

        row.querySelector('.remove-link-row').addEventListener('click', function () {
            row.remove();
        });

        container.appendChild(row);
    }

    addButton.addEventListener('click', function () { addRow(null); });

    const existing = @json($existingLinks);
    if (existing.length > 0) {
        existing.forEach(addRow);
    } else {
        addRow(null);
    }
})();
</script>
