<?php

namespace App\Http\Controllers;

use App\Helpers\Functions;
use App\Http\Requests\UserUpdateRequest;
use App\Models\ClientCode;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function update(UserUpdateRequest $request, User $user)
    {
        $data = $request->validated();

        // Delete previous image if changed
        if (key_exists("avatar_image", $data) && $user->avatar_image) {
            Storage::delete($user->avatar_image->path);
        }

        if ($request->hasFile('avatar_image')) {
            $file = $request->file('avatar_image');
            $image = Functions::store_uploaded_image(
                $file,
                'avatars/' . Str::uuid()
            );

            $data['avatar_image_id'] = $image->id;
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
