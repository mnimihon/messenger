<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\DeleteAccountRequest;
use App\Http\Requests\Profile\UpdateNameRequest;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Services\UserProfileService;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function show(UserProfileService $userProfileService)
    {
        $userID = (int) Auth::id();

        return response()->json([
            'success' => true,
            'data' => $userProfileService->publicProfilePayload($userID),
        ]);
    }

    public function updateName(UpdateNameRequest $request, UserProfileService $userProfileService)
    {
        $userID = (int) Auth::id();

        return $this->jsonFromService($userProfileService->updateNameResult($userID, $request->name));
    }

    public function updatePassword(UpdatePasswordRequest $request, UserProfileService $userProfileService)
    {
        $userID = (int) Auth::id();

        return $this->jsonFromService($userProfileService->updatePasswordResult(
            $userID,
            $request->current_password,
            $request->new_password
        ));
    }

    public function deleteAccount(DeleteAccountRequest $request, UserProfileService $userProfileService)
    {
        $userID = (int) Auth::id();

        return $this->jsonFromService($userProfileService->deleteAccountResult(
            $userID,
            $request->password
        ));
    }
}
