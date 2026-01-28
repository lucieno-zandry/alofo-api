<?php

namespace App\Http\Controllers;

use App\Helpers\Functions;
use App\Http\Requests\UserUpdateRequest;
use App\Models\ClientCode;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function update(UserUpdateRequest $request, User $user)
    {
        $data = $request->validated();

        // Remove existing image if changed
        if (key_exists("image", $data) && $user->image)
            Storage::delete($user->image);

        // Store the uploaded image
        if (!empty($data['image'])) {
            $path = Functions::store_uploaded_file($data['image']);

            if (!$path)
                throw ValidationException::withMessages(['image' => 'Failed to store image']);

            $data['image'] = $path;
        }

        $user->update($data);

        return [
            'user' => $user,
        ];
    }

    public function show(int $user_id)
    {
        $user = User::withRelations()
            ->withPagination()
            ->find($user_id);

        return [
            'user' => $user,
        ];
    }

    public function index()
    {
        $users = User::applyFilters()->roleFilterable()->get();

        return [
            'users' => $users
        ];
    }
}
