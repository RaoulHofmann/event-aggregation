<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        SupportTicket::create($data + [
            'user_id' => $request->user()->id,
            'team_id' => $request->user()->currentTeam->id,
        ]);

        return to_route('dashboard')->with('success', 'Support ticket submitted.');
    }
}
