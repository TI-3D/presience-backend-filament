<?php

namespace App\Http\Controllers;

use App\Contracts\ProfileContract;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\StorePhotoRequest;
use App\Http\Resources\ApiResource;
use App\Models\Photo;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    //
    protected ProfileContract $profileContract;

    public function __construct(ProfileContract $profileContract)
    {
        $this->profileContract = $profileContract;
    }


    public function getProfile(Request $request)
    {
            $result = $this->profileContract->getProfile($request);
            return $result;
    }

    public function storePhotos(StorePhotoRequest $request)
    {
        $result = $this->profileContract->storePhoto($request);
        return  $result;
    }

    public function faceRecognition(StorePhotoRequest $request)
    {
        $result = $this->profileContract->faceRecognition($request);
        return  $result;
    }

    public function validatePassword(Request $request)
    {
        $result = $this->profileContract->validatePassword($request->input('password'));
        return  $result;
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $student = Auth::user();
        $result = $this->profileContract->changePassword($student, $request);
        return $result;
    }

    public function updateFcmId(Request $request)
    {
        $result = $this->profileContract->updateFcmId($request->input('fcm_id'));
        return $result;
    }
}
