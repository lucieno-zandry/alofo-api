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
use DateInterval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
        $user->permissions = $user->getPermissions();

        return [
            'token' => $user->createToken('device')->plainTextToken,
            'auth' => $user,
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

        $user->permissions = $user->getPermissions();

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
        $token = Str::random(32);

        $token_table = DB::table('password_reset_tokens');

        // delete any token before creating another
        $token_table->where('email', $request->email)->delete();

        $token_table->insert([
            'email' => $request->email,
            'token' => $token,
        ]);

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

        $token_table = DB::table('password_reset_tokens');
        $token = $token_table->where('token', $request->token)->first();

        if (!$token) throw HttpException::fromStatusCode(403, 'Forbidden');

        $now = date_create();
        $created_at = date_create($token->created_at);
        $expired_at = date_add($created_at, DateInterval::createFromDateString('15 minutes'));

        if ($expired_at < $now) throw HttpException::fromStatusCode(403, 'Forbidden');

        $user = User::where('email', $token->email)->first();

        if (!$user)
            throw HttpException::fromStatusCode(403, 'Forbidden');

        $user->password = $request->password;
        $user->save();

        $user->permissions = $user->getPermissions();

        //Delete used token
        $token_table->where('email', $user->email)->delete();

        return [
            'token' => $user->createToken('device')->plainTextToken,
            'auth' => $user,
        ];
    }

    public function update(AuthUserUpdateRequest $request)
    {
        $data = $request->validated();
        $client_code = $request->clientCode();

        $user = User::find(auth()->id());

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

        // Increment client code uses if applicable
        if ($client_code && $user->client_code_id !== $client_code->id)
            $client_code->incrementUses();

        $user->update($data);
        $user->permissions = $user->getPermissions();

        return [
            'user' => $user,
        ];
    }

    public function show()
    {
        $user = User::withRelations()
            ->find(auth()->id());

        $user->permissions = $user->getPermissions();

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

        $user = auth()->user();
        $user->markEmailAsVerified();
        $user->permissions = $user->getPermissions();

        return [
            'user' => auth()->user()
        ];
    }
}
