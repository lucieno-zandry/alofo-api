<?php

namespace App\Http\Controllers;

use App\Helpers\EmailConfirmationHelpers;
use App\Helpers\Functions;
use App\Http\Requests\AuthUserUpdateRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Mail\Auth\Password\ResetMail;
use App\Models\ClientCode;
use App\Models\User;
use DB;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Mail;
use Password;
use Storage;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $data = $request->validated();

        $client_code = !empty($data['client_code_id']) ? ClientCode::where('code', $data['client_code_id'])->first() : null;

        if (!empty($data['client_code_id'])) {
            if (!$client_code || $client_code->user_id !== null)
                throw ValidationException::withMessages([
                    'client_code_id' => [
                        'The client code is not valid.'
                    ]
                ]);

            $data['client_code_id'] = $client_code->id;
        }

        if (!empty($data['image'])) {
            $path = Functions::store_uploaded_file($data['image']);

            if (!$path)
                throw ValidationException::withMessages(['image' => 'Failed to store image']);

            $data['image'] = $path;
        }

        $user = User::create($data);

        if ($client_code)
            $client_code->update(['user_id' => $user->id]);

        return [
            'token' => $user->createToken('device')->plainTextToken,
            'auth' => $user
        ];
    }

    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Wrong email or password'
            ]);
        }

        return [
            'token' => $user->createToken('device')->plainTextToken,
            'auth' => $user
        ];
    }

    public function email_info(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email']
        ]);

        $user = User::where('email', $data['email'])->first();
        $is_taken = !!$user;

        return [
            'is_taken' => $is_taken
        ];
    }

    public function password_forgot(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users']
        ]);

        $user = User::where('email', $request->email)->first();

        $token = Password::createToken($user);

        $link_sent = Mail::to($user)
            ->send(new ResetMail($token));

        return [
            'link_sent' => $link_sent
        ];
    }

    public function password_reset(Request $request)
    {
        $request->validate([
            'token' => ['required', 'exists:password_reset_tokens'],
            'password' => ['required', 'min:6', 'confirmed']
        ]);

        $Token = DB::table('password_reset_tokens');

        $token = $Token->where('token', $request->token)->first();
        $user = User::where('email', $token->email)->first();

        if (!$user)
            throw ValidationException::withMessages(['token' => 'User not found!']);

        $user->password = $request->password;
        $user->save();

        //Delete used token
        $Token->where('email', $user->email)->delete();

        return [
            'token' => $user->createToken('device')->plainTextToken,
            'auth' => $user,
        ];
    }

    public function update(AuthUserUpdateRequest $request)
    {
        $data = $request->validated();

        $user = User::find(auth()->id());

        // If the user uses a client code
        if (key_exists('client_code_id', $data)) {
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
            } else if ($user->client_code_id) {
                $client_code = ClientCode::find($user->client_code_id);

                if ($client_code)
                    $client_code->update(['user_id' => null]);
            }
        }

        // Delete previous image if changed
        if (key_exists("image", $data) && $user->image)
            Storage::delete($user->image);

        if (!empty($data['image'])) {
            $path = Functions::store_uploaded_file($data['image']);

            if (!$path)
                throw ValidationException::withMessages(['image' => 'Failed to store image']);

            $data['image'] = $path;
        }

        // Make the user verify it's email again if changed
        if ($request->has('email'))
            $user->email_verified_at = null;

        $user->update($data);

        return [
            'user' => $user,
        ];
    }

    public function show()
    {
        $user = User::withRelations()
            ->find(auth()->id());

        return [
            'user' => $user,
        ];
    }

    public function send_validation_code(EmailConfirmationHelpers $helpers, Request $request)
    {
        return [
            'link_sent' => $helpers->handle_confirmation()
        ];
    }

    public function email_verify(Request $request, EmailConfirmationHelpers $helpers)
    {
        $request->validate(['code' => 'required|min:6']);

        $helpers->match($request->code);

        /**
         * @var \App\Models\ConfirmationCode
         * Get the ConfirmationCode instance and then delete it
         */
        $code = $helpers->__get('code');
        $code->delete();

        auth()->user()->markEmailAsVerified();

        return [
            'user' => auth()->user()
        ];
    }
}
