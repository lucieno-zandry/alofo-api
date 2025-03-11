<?php

namespace App\Http\Controllers;

use App\Helpers\Functions;
use App\Http\Requests\UserUpdateRequest;
use App\Models\ClientCode;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Storage;

class UserController extends Controller
{
    public function update(UserUpdateRequest $request, User $user)
    {
        $data = $request->validated();

        // If the user uses a client code
        if (!empty($data['client_code_id'])) {
            $client_code = ClientCode::where('code', $data['client_code_id'])->first();

            if (!$client_code || $client_code->user_id !== null)
                throw ValidationException::withMessages([
                    'client_code_id' => [
                        'The client code is not valid.'
                    ]
                ]);

            // We store the actual client_code_id, and not the code;
            $data['client_code_id'] = $client_code->id;

            $client_code->update(['user_id' => $user->id]);
        }

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
