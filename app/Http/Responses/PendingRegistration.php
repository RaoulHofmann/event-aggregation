<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\RegisterResponse;

class PendingRegistration implements RegisterResponse
{
    public function toResponse($request)
    {
        return $request->wantsJson()
            ? response()->json(['status' => 'pending'], 201)
            : to_route('account.status');
    }
}
