<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with all monitors for the authenticated user.
     *
     * @param  Request  $request
     * @return View
     */
    public function index(Request $request): View
    {
        // Get authenticated user
        $user = $request->user();

        // Fetch all monitors for the user, ordered by status priority
        // (down first, then up, then pending)
        $monitors = $user->monitors()
            ->orderByRaw("CASE 
                WHEN status = 'down' THEN 1 
                WHEN status = 'up' THEN 2 
                WHEN status = 'pending' THEN 3 
                ELSE 4 
            END")
            ->orderBy('name')
            ->get();

        return view('dashboard', [
            'monitors' => $monitors,
        ]);
    }
}
