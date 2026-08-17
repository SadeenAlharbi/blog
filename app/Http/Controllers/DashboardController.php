<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $posts = $request->user()
            ->posts()
            ->withCount(['comments', 'tags'])
            ->latest()
            ->paginate(10);

        return view('dashboard.index', compact('posts'));
    }
}
