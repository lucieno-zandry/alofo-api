<?php

namespace App\Helpers;

use App\Mail\Auth\Email\ConfirmationMail;
use App\Mail\Auth\EmailConfirmation;
use App\Models\ConfirmationCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class EmailConfirmationHelpers
{
    protected $user;
    protected $code;

    public function __construct()
    {
        $this->user = Auth::user();
    }

    protected function get_valable_code(): ConfirmationCode|Model|null
    {
        $now_date = date_create();
        $now_date_iso = Functions::get_iso_string($now_date);

        return ConfirmationCode::where('user_id', $this->user->id)
            ->where('created_at', '<=', $now_date_iso)
            ->where('expires_at', '>', $now_date_iso)
            ->first();
    }

    protected function create_code(): ConfirmationCode|Model
    {
        $code = random_int(100000, 999999);
        $expires_at = date_add(date_create(), date_interval_create_from_date_string('15 minutes'));

        return ConfirmationCode::create([
            'content' => $code,
            'user_id' => $this->user->id,
            'expires_at' => Functions::get_iso_string($expires_at)
        ]);
    }

    protected function send_email()
    {
        $sent = Mail::to($this->user)
            ->send(new ConfirmationMail($this->code->content));

        return $sent;
    }

    public function handle_confirmation()
    {
        if ($this->user->email_verified_at)
            abort(403, "Email already confirmed.");

        $this->code = $this->get_valable_code() ?? $this->create_code();
        $sent = boolval($this->send_email());

        return $sent;
    }

    public function match(int $request_code)
    {
        $this->code = $this->get_valable_code();

        if ($this->code && $request_code !== $this->code->content) {
            throw ValidationException::withMessages([
                'code' => "The code is not valid.",
            ]);
        }
    }

    public function __get(string $name)
    {
        return $this->$name;
    }
}