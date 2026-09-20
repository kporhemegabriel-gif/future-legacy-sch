<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        // Authorization boundary for every parent-facing feature: a
        // parent's visible student set is always derived from their own
        // parent_student rows, never from a submitted ID. Any future
        // parent route (results, fees, attendance) must filter through
        // this same relation server-side.
        $parentProfile = $request->user()->parentProfile()->with('students')->first();
        $children = $parentProfile?->students ?? collect();

        return view('parent.dashboard', compact('children'));
    }
}
