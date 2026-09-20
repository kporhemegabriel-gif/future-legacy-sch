@extends('layouts.app')

@section('title', 'Result Settings')

@section('content')
    <h1>Result Settings</h1>
    <p class="muted">
        Grading setup: <a href="{{ route('admin.terms.index') }}">Terms</a> &middot;
        <a href="{{ route('admin.assessment-types.index') }}">Assessment Types</a> &middot;
        <a href="{{ route('admin.grade-bands.index') }}">Grading Scale</a> &middot;
        <a href="{{ route('admin.settings.edit') }}">Result Settings</a>
    </p>

    <div class="card">
        <form method="POST" action="{{ route('admin.settings.update') }}">
            @csrf
            @method('PUT')

            <div class="field">
                <label class="inline" style="display:flex; align-items:center; gap:0.4rem;">
                    <input type="checkbox" name="ranking_enabled" value="1" @checked($rankingEnabled)>
                    Show position / ranking on term results
                </label>
                <p class="muted" style="margin-top:0.4rem;">
                    When off, position is not calculated when results are computed, and is not shown to
                    students, parents, or admins anywhere in the app.
                </p>
            </div>

            <div class="actions">
                <button type="submit" class="btn">Save</button>
            </div>
        </form>
    </div>
@endsection
