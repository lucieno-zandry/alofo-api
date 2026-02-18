<?php

namespace App\Http\Controllers;

use App\Events\ClientCodeUsed;
use App\Helpers\EmailConfirmationHelpers;
use App\Helpers\Functions;
use App\Http\Requests\AuthUserUpdateRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Mail\Auth\Password\ResetMail;
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
        $client_code = $request->clientCode();

        // Increment client code uses if applicable
        if ($client_code)
            ClientCodeUsed::dispatch($client_code);

        if ($request->hasFile('avatar_image')) {
            $file = $request->file('avatar_image');

            $image = Functions::store_uploaded_file(
                $file,
                'avatars/'
            );

            $data['avatar_image_id'] = $image->id;
        }

        $user = User::create($data);

        $user->permissions = $user->getPermissions();

        $user->load('avatar_image'); // Load avatar image relation

        return [
            'token' => $user->createToken('device')->plainTextToken,
            'auth' => $user,
        ];
    }

    public function login(LoginRequest $request)
    {
        $user = User::with('avatar_image')->where('email', $request->email)->first();

        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Wrong email or password'
            ]);
        }

        if ($user->role !== $request->role) {
            throw ValidationException::withMessages([
                'email' => 'This account is not a ' . $request->role
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

        $user = User::with('avatar_image')->where('email', $token->email)->first();

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

        if ($request->hasFile('avatar_image') && $user->avatar_image)
            $user->avatar_image->delete();

        if ($request->hasFile('avatar_image')) {
            $file = $request->file('avatar_image');

            $image = Functions::store_uploaded_file(
                $file,
                'avatars/'
            );

            $data['avatar_image_id'] = $image->id;
        }

        // Make the user verify it's email again if changed
        if ($request->has('email'))
            $user->email_verified_at = null;

        // Increment client code uses if applicable
        if ($client_code && $user->client_code_id !== $client_code->id)
            ClientCodeUsed::dispatch($client_code);

        $user->update($data);
        $user->permissions = $user->getPermissions();

        $user->load('avatar_image'); // Load avatar image relation

        return [
            'user' => $user,
        ];
    }

    public function show()
    {
        $user = User::with('avatar_image')
            ->withRelations()
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
        $code?->delete();

        $user = auth()->user();
        $user->markEmailAsVerified();
        $user->permissions = $user->getPermissions();
        $user->avatar_image; // Load avatar image relation

        return [
            'user' => auth()->user()
        ];
    }
}
