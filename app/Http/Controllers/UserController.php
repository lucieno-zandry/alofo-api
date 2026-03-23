<?php

namespace App\Http\Controllers;

use App\Events\UserStatusUpdatedEvent;
use App\Helpers\Functions;
use App\Http\Requests\UserStatusStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

        if ($request->has('email') && $request->email !== $user->email)
            $user->email_verified_at = null;

        $user->update($data);

        return [
            'user' => $user,
        ];
    }

    public function show(int $user_id)
    {
        $user = User::withRelations()
            ->find($user_id);

        return [
            'user' => $user,
        ];
    }


    public function index(Request $request)
    {
        // Allowed sort columns to prevent SQL injection
        $allowedSorts = ['name', 'email', 'created_at', 'updated_at'];
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        // Validate sort column
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }

        // Validate sort order
        $sortOrder = in_array(strtolower($sortOrder), ['asc', 'desc']) ? $sortOrder : 'desc';

        // Start query with optional relations
        $query = User::query()->withRelations();

        // Case-insensitive search on name and email
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%'])
                    ->orWhereRaw('LOWER(email) LIKE ?', ['%' . strtolower($search) . '%']);
            });
        }

        // Filter by role
        if ($role = $request->get('role')) {
            if ($role !== 'all') {
                $query->where('role', $role);
            }
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortOrder);

        // Paginate (per_page defaults to 15)
        $perPage = $request->get('per_page', 15);
        $users = $query->paginate($perPage);

        return response()->json($users);
    }

    public function storeStatus(UserStatusStoreRequest $request, User $user)
    {
        $data = $request->validated();

        $status = UserStatus::create($data);

        UserStatusUpdatedEvent::dispatch($user, $status);

        return [
            'user' => $user->fresh()
        ];
    }
}
