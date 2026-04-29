<?php

namespace App\Http\Controllers;

use App\Events\ClientCodeUsed;
use App\Helpers\CartItemHelpers;
use App\Helpers\EmailConfirmationHelpers;
use App\Helpers\Functions;
use App\Http\Requests\AuthUserUpdateRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Mail\Auth\Password\ResetMail;
use App\Models\User;
use App\Services\CurrencyService;
use DateInterval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthController extends Controller
{

    /**
     * Merge guest user data into the given real user and delete the guest.
     *
     * Called during registration or login when a `guest_token` cookie exists.
     */
    protected function mergeGuestIfNeeded(Request $request, User $realUser): void
    {
        if (!$request->hasCookie('guest_token')) {
            return;
        }

        $token = $request->cookie('guest_token');
        $tokenModel = PersonalAccessToken::findToken($token);

        if (!$tokenModel) {
            return;
        }

        $guestUser = $tokenModel->tokenable;

        // Safety check: only merge actual guest accounts
        if (!$guestUser || $guestUser->role !== 'guest') {
            return;
        }

        DB::transaction(function () use ($guestUser, $realUser) {
            // 1. Transfer cart items, merging duplicates
            foreach ($guestUser->cart_items as $item) {
                $existing = $realUser->cart_items()
                    ->where('variant_id', $item->variant_id)
                    ->first();

                if ($existing) {
                    CartItemHelpers::make_item(
                        $existing,
                        ['count' => $item->count],
                    );

                    $item->delete();
                } else {
                    // Reassign to real user
                    $item->update(['user_id' => $realUser->id]);
                }
            }

            // 2. Transfer addresses
            $realHasDefault = $realUser->addresses()
                ->where('is_default', true)
                ->exists();

            foreach ($guestUser->addresses as $address) {
                // Avoid duplicate default flags
                if ($realHasDefault) {
                    $address->is_default = false;
                }
                $address->update(['user_id' => $realUser->id]);
            }

            // 3. Remove guest tokens and the guest user itself
            $guestUser->tokens()->delete();
            $guestUser->delete();
        });
    }

    public function register(RegisterRequest $request)
    {
        // --- Resolve guest user if one is already authenticated via the guest_token cookie ---
        /** @var \App\Models\User | null */
        $user = null;

        if ($request->hasCookie('guest_token')) {
            $tokenModel = PersonalAccessToken::findToken($request->cookie('guest_token'));
            if ($tokenModel && $tokenModel->tokenable?->role === 'guest') {
                $user = $tokenModel->tokenable;
            }
        }

        $data = $request->only('email', 'password', 'name', 'role', 'client_code_id');
        $client_code = $request->clientCode();

        // Avatar handling (unchanged)
        if ($request->hasFile('avatar_image')) {
            $image = Functions::store_uploaded_image($request->file('avatar_image'), 'avatars/');
            $data['avatar_image_id'] = $image->id;
        }

        if ($user) {
            $user->tokens()->delete();
            $user->update($data);
        } else {
            $user = User::create($data);
        }

        // --- Common post‑registration steps ---

        // Preferences: create if not existing (guest users don't have them)
        if (!$user->preferences) {
            $user->preferences()->create([
                'theme'    => $request->input('preferred_theme', 'system'),
                'language' => $request->input('preferred_language', 'en'),
                'timezone' => $request->input('preferred_timezone', 'UTC'),
                'currency' => $request->input('preferred_currency', app(CurrencyService::class)->getFrom()),
            ]);
        }

        // Client code event (if relevant)
        if ($client_code) {
            ClientCodeUsed::dispatch($client_code, $user, 'attach');
        }

        $user->permissions = $user->getPermissions();
        $user->load('avatar_image');

        $token = $user->createToken('device')->plainTextToken;

        return response()->json(['auth' => $user])
            ->cookie('auth_token', $token, 60)
            ->withoutCookie('guest_token');
    }

    public function login(LoginRequest $request)
    {
        $user = User::with('avatar_image')->where('email', $request->email)->first();
        $this->mergeGuestIfNeeded($request, $user);

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
        $token = $user->createToken('device')->plainTextToken;

        $response = response()->json([
            'auth' => $user
        ])
            ->cookie('auth_token', $token, 60)
            ->withoutCookie('guest_token');

        return $response;
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
            'token' => $token,
            'email' => $request->email,
        ]);

        $reset_url = Functions::get_frontend_url('PASSWORD_RESET_PATHNAME', $user->role) . $token;

        $link_sent = Mail::to($user)
            ->send(new ResetMail($reset_url));

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

        $this->mergeGuestIfNeeded($request, $user);

        $user->password = $request->password;
        $user->save();

        $user->permissions = $user->getPermissions();

        //Delete used token
        $token_table->where('email', $user->email)->delete();

        $token = $user->createToken('device')->plainTextToken;
        $response = response()->json([
            'auth' => $user
        ])
            ->cookie('auth_token', $token, 60)
            ->withoutCookie('guest_token');

        return $response;
    }

    public function update(AuthUserUpdateRequest $request)
    {
        $data = $request->validated();
        $client_code = $request->clientCode();

        /** @var \App\Models\User */
        $user = auth('sanctum')->user();

        if ($request->hasFile('avatar_image') && $user->avatar_image)
            $user->avatar_image->delete();

        if ($request->hasFile('avatar_image')) {
            $file = $request->file('avatar_image');

            $image = Functions::store_uploaded_image(
                $file,
                'avatars/'
            );

            $data['avatar_image_id'] = $image->id;
        }

        // Make the user verify it's email again if changed
        if ($request->has('email') && $request->email !== $user->email) {
            $user->email_verified_at = null;

            if ($user->roleIsGuest()) {
                $user->role = 'client';
            }
        }

        // Increment client code uses if applicable
        if ($client_code && $user->client_code_id !== $client_code->id)
            ClientCodeUsed::dispatch($client_code, $user, 'attach');

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
            ->find(auth('sanctum')->id());

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

        /** @var \App\Models\User */
        $user = auth('sanctum')->user();
        $user->markEmailAsVerified();
        $user->permissions = $user->getPermissions();
        $user->avatar_image; // Load avatar image relation

        if ($user->roleIsCustomer() && !$user->hasBeenApproved()) {
            $user->approve(set_by: null);
        }

        return [
            'user' => $user
        ];
    }

    public function logout()
    {
        /** @var \App\Models\User */
        $user = Auth::user();

        $user->currentAccessToken()->delete();

        return response()
            ->json(['message' => 'Logged out.'])
            ->withoutCookie('auth_token');
    }
}
