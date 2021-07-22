<?php
/**
 * Copyright (c) 2021  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Http\Controllers;

use App\Http\Middleware\ClientHeadersMiddleware;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UsersApiController extends Controller
{
    /**
     * Registers fcm token for user found by client id header.
     * Creates new user for client id or updates the old one, incrementing update_count.
     *
     * @throws ValidationException
     */
    public function registerFcmToken(Request $request)
    {
        $this->validate($request, [
            'token' => 'required|max:1000',
        ]);

        $clientId = ClientHeadersMiddleware::getClientId();
        $clientVersion = ClientHeadersMiddleware::getClientVersion();
        $token = $request->input('token');

        $user = User::byClientId($clientId);
        $user->client_version = $clientVersion;
        $user->ip = $request->ip();
        $user->fcm_token = $token;
        $user->increment('update_count');
        $user->save();

        logger()->registerFcmToken('token='.$token, 'updateCount='.$user->update_count);

        return okResponse(['message' => 'Token saved']);
    }
}
