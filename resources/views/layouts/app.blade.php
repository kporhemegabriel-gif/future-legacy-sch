<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Future Legacy School') — SMS</title>
    <style>
        :root {
            --navy: #16283f;
            --gold: #c8a24a;
            --cream: #f7f4ee;
            --ink: #1c1c1c;
            --muted: #6b7280;
            --danger: #a3402c;
            --success: #2f6e4f;
            --border: #e4ddca;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Georgia, 'Times New Roman', serif;
            background: var(--cream);
            color: var(--ink);
        }
        header.topbar {
            background: var(--navy);
            color: #fff;
            padding: 0.9rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        header.topbar .brand {
            font-size: 1.1rem;
            letter-spacing: 0.02em;
        }
        header.topbar .brand strong { color: var(--gold); }
        header.topbar form { margin: 0; }
        header.topbar button {
            background: transparent;
            border: 1px solid rgba(255,255,255,0.4);
            color: #fff;
            padding: 0.4rem 0.9rem;
            border-radius: 4px;
            cursor: pointer;
            font-family: inherit;
        }
        nav.subnav {
            background: #0f1c2e;
            padding: 0.6rem 1.5rem;
            display: flex;
            gap: 1.25rem;
            flex-wrap: wrap;
        }
        nav.subnav a {
            color: #cfd8e3;
            text-decoration: none;
            font-size: 0.9rem;
            padding: 0.2rem 0;
            border-bottom: 2px solid transparent;
        }
        nav.subnav a:hover,
        nav.subnav a.active {
            color: #fff;
            border-bottom-color: var(--gold);
        }
        main {
            max-width: 1100px;
            margin: 0 auto;
            padding: 2rem 1.5rem 4rem;
        }
        .card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 1.25rem 1.5rem;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1.5rem 0;
        }
        .stat-number {
            font-size: 2rem;
            color: var(--navy);
            font-weight: bold;
        }
        .stat-label {
            color: var(--muted);
            font-size: 0.85rem;
        }
        h1 { color: var(--navy); }
        h2 { color: var(--navy); font-size: 1.2rem; }
        a { color: var(--navy); }
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 0.5rem 0.75rem; border-bottom: 1px solid #eee; white-space: nowrap; }
        th { color: var(--muted); font-weight: normal; font-size: 0.85rem; }
        .btn {
            display: inline-block;
            font-family: inherit;
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            border: 1px solid var(--navy);
            background: var(--navy);
            color: #fff;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-secondary { background: #fff; color: var(--navy); }
        .btn-danger { background: #fff; color: var(--danger); border-color: var(--danger); }
        .btn-small { padding: 0.3rem 0.7rem; font-size: 0.8rem; }
        .btn-link { background: none; border: none; color: var(--navy); text-decoration: underline; cursor: pointer; padding: 0; font-family: inherit; font-size: inherit; }
        .actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .alert { padding: 0.75rem 1rem; border-radius: 4px; margin-bottom: 1.25rem; font-size: 0.9rem; }
        .alert-success { background: #e6f2ec; color: var(--success); border: 1px solid #b7dcc7; }
        .alert-error { background: #f6e7e3; color: var(--danger); border: 1px solid #e0bcb0; }
        .badge { display: inline-block; padding: 0.15rem 0.55rem; border-radius: 999px; font-size: 0.75rem; }
        .badge-active { background: #e6f2ec; color: var(--success); }
        .badge-inactive { background: #f0f0f0; color: var(--muted); }
        .badge-graduated { background: #e6ecf6; color: #2b4c8c; }
        .badge-withdrawn { background: #f6e7e3; color: var(--danger); }
        form.filter-bar { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1.25rem; }
        form.filter-bar input, form.filter-bar select {
            font-family: inherit; padding: 0.45rem 0.6rem; border: 1px solid var(--border); border-radius: 4px;
        }
        .field { margin-bottom: 1rem; }
        .field label { display: block; margin-bottom: 0.3rem; font-size: 0.85rem; color: var(--muted); }
        .field input, .field select, .field textarea {
            width: 100%; font-family: inherit; padding: 0.55rem 0.7rem; border: 1px solid var(--border); border-radius: 4px; font-size: 0.95rem;
        }
        .field .error { color: var(--danger); font-size: 0.8rem; margin-top: 0.25rem; }
        .field-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 0 1rem; }
        .link-row { display: grid; grid-template-columns: 2fr 1.3fr auto auto; gap: 0.5rem; align-items: center; margin-bottom: 0.5rem; }
        .link-row label.inline { display: flex; align-items: center; gap: 0.4rem; font-size: 0.85rem; color: var(--muted); margin: 0; }
        .pagination { margin-top: 1.5rem; font-size: 0.85rem; }
        .muted { color: var(--muted); }
        .empty-state { color: var(--muted); font-style: italic; padding: 1rem 0; }
        @media (max-width: 640px) {
            .link-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="brand"><strong>Future Legacy</strong> School — SMS</div>
        @auth
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Log out ({{ auth()->user()->name }})</button>
            </form>
        @endauth
    </header>

    @auth
        @if(auth()->user()->isAdmin())
            <nav class="subnav">
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
                <a href="{{ route('admin.students.index') }}" class="{{ request()->routeIs('admin.students.*') ? 'active' : '' }}">Students</a>
                <a href="{{ route('admin.parents.index') }}" class="{{ request()->routeIs('admin.parents.*') ? 'active' : '' }}">Parents</a>
                <a href="{{ route('admin.classes.index') }}" class="{{ request()->routeIs('admin.classes.*') ? 'active' : '' }}">Classes</a>
                <a href="{{ route('admin.subjects.index') }}" class="{{ request()->routeIs('admin.subjects.*') ? 'active' : '' }}">Subjects</a>
                <a href="{{ route('admin.assessments.index') }}" class="{{ request()->routeIs('admin.assessments.*') ? 'active' : '' }}">Assessments</a>
                <a href="{{ route('admin.term-results.index') }}" class="{{ request()->routeIs('admin.term-results.*') ? 'active' : '' }}">Results</a>
                <a href="{{ route('admin.attendance.index') }}" class="{{ request()->routeIs('admin.attendance.*') ? 'active' : '' }}">Attendance</a>
                <a href="{{ route('admin.terms.index') }}" class="{{ request()->routeIs('admin.terms.*') || request()->routeIs('admin.assessment-types.*') || request()->routeIs('admin.grade-bands.*') || request()->routeIs('admin.settings.*') ? 'active' : '' }}">Grading Setup</a>
            </nav>
        @elseif(auth()->user()->isParent())
            <nav class="subnav">
                <a href="{{ route('parent.dashboard') }}" class="{{ request()->routeIs('parent.dashboard') ? 'active' : '' }}">Dashboard</a>
                <a href="{{ route('parent.children.index') }}" class="{{ request()->routeIs('parent.children.*') ? 'active' : '' }}">My Children</a>
            </nav>
        @elseif(auth()->user()->isStudent())
            <nav class="subnav">
                <a href="{{ route('student.dashboard') }}" class="{{ request()->routeIs('student.dashboard') ? 'active' : '' }}">Dashboard</a>
                <a href="{{ route('student.profile') }}" class="{{ request()->routeIs('student.profile') ? 'active' : '' }}">My Profile</a>
                <a href="{{ route('student.results.index') }}" class="{{ request()->routeIs('student.results.*') ? 'active' : '' }}">My Results</a>
                <a href="{{ route('student.attendance.index') }}" class="{{ request()->routeIs('student.attendance.*') ? 'active' : '' }}">My Attendance</a>
            </nav>
        @endif
    @endauth

    <main>
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-error">
                <strong>Please fix the following:</strong>
                <ul style="margin:0.4rem 0 0 1.1rem; padding:0;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
