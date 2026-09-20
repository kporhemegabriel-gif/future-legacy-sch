@php $editing = isset($student); @endphp

<form method="POST" action="{{ $editing ? route('admin.students.update', $student) : route('admin.students.store') }}" enctype="multipart/form-data">
    @csrf
    @if ($editing) @method('PUT') @endif

    <h2>Account</h2>
    <div class="field-row">
        <div class="field">
            <label for="name">Full name (for login)</label>
            <input type="text" id="name" name="name" value="{{ old('name', $editing ? $student->user->name : '') }}" required>
            @error('name') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email', $editing ? $student->user->email : '') }}" required>
            @error('email') <div class="error">{{ $message }}</div> @enderror
        </div>
    </div>
    <div class="field">
        <label for="password">{{ $editing ? 'New password (leave blank to keep current)' : 'Password' }}</label>
        <input type="password" id="password" name="password" {{ $editing ? '' : 'required' }} minlength="8">
        @error('password') <div class="error">{{ $message }}</div> @enderror
    </div>

    <h2>Student profile</h2>
    <div class="field-row">
        <div class="field">
            <label for="admission_number">Admission number</label>
            <input type="text" id="admission_number" name="admission_number" value="{{ old('admission_number', $student->admission_number ?? '') }}" required>
            @error('admission_number') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label for="admission_date">Admission date</label>
            <input type="date" id="admission_date" name="admission_date" value="{{ old('admission_date', optional($student->admission_date ?? null)->format('Y-m-d')) }}" required>
            @error('admission_date') <div class="error">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="field-row">
        <div class="field">
            <label for="first_name">First name</label>
            <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $student->first_name ?? '') }}" required>
            @error('first_name') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label for="middle_name">Middle name</label>
            <input type="text" id="middle_name" name="middle_name" value="{{ old('middle_name', $student->middle_name ?? '') }}">
        </div>
        <div class="field">
            <label for="last_name">Last name</label>
            <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $student->last_name ?? '') }}" required>
            @error('last_name') <div class="error">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="field-row">
        <div class="field">
            <label for="date_of_birth">Date of birth</label>
            <input type="date" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth', optional($student->date_of_birth ?? null)->format('Y-m-d')) }}">
        </div>
        <div class="field">
            <label for="gender">Gender</label>
            <select id="gender" name="gender">
                <option value="">—</option>
                @foreach (['male', 'female', 'other'] as $option)
                    <option value="{{ $option }}" @selected(old('gender', $student->gender ?? '') === $option)>{{ ucfirst($option) }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="phone">Phone</label>
            <input type="text" id="phone" name="phone" value="{{ old('phone', $student->phone ?? '') }}">
        </div>
    </div>

    <div class="field">
        <label for="address">Address</label>
        <textarea id="address" name="address" rows="2">{{ old('address', $student->address ?? '') }}</textarea>
    </div>

    <div class="field-row">
        <div class="field">
            <label for="class_id">Class</label>
            <select id="class_id" name="class_id">
                <option value="">Unassigned</option>
                @foreach ($classes as $class)
                    <option value="{{ $class->id }}" @selected(old('class_id', $student->class_id ?? '') == $class->id)>{{ $class->displayName() }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="academic_year_id">Academic year</label>
            <select id="academic_year_id" name="academic_year_id">
                <option value="">—</option>
                @foreach ($academicYears as $year)
                    <option value="{{ $year->id }}" @selected(old('academic_year_id', $student->academic_year_id ?? '') == $year->id)>{{ $year->name }}</option>
                @endforeach
            </select>
        </div>
        @if ($editing)
            <div class="field">
                <label for="status">Enrollment status</label>
                <select id="status" name="status">
                    @foreach (['active', 'inactive', 'graduated', 'withdrawn'] as $status)
                        <option value="{{ $status }}" @selected(old('status', $student->status) === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
        @endif
    </div>

    <div class="field">
        <label for="profile_photo">Profile photo{{ $editing && $student->profile_photo ? ' (replace)' : '' }}</label>
        <input type="file" id="profile_photo" name="profile_photo" accept="image/*">
        @error('profile_photo') <div class="error">{{ $message }}</div> @enderror
    </div>

    <div class="actions">
        <button type="submit" class="btn">{{ $editing ? 'Save changes' : 'Create student' }}</button>
        <a href="{{ $editing ? route('admin.students.show', $student) : route('admin.students.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
