<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Traits\ApplyFilters;
use App\Traits\DynamicConditionApplicable;
use App\Traits\WithOrdering;
use App\Traits\WithPagination;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, SoftDeletes, Notifiable, HasApiTokens, CanResetPassword, WithPagination, WithOrdering, DynamicConditionApplicable, ApplyFilters;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'client_code_id',
        'avatar_image_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'status'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function hasBeenApproved(): bool
    {
        return $this->status?->status === 'approved';
    }

    public function canUseSpecialPrices()
    {
        return $this->client_code?->isUsable() ?? false;
    }

    public function getPermissions(): ?array
    {
        $permissions = [];

        if ($this->canUseSpecialPrices()) {
            $permissions['can_use_special_prices'] = true;
        }

        if (count($permissions) === 0)
            return null;

        return $permissions;
    }

    public function roleIsAdmin()
    {
        return  $this->role === UserRole::ADMIN->value;
    }

    public function roleIsCustomer()
    {
        return $this->role === UserRole::CLIENT->value;
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function client_code()
    {
        return $this->belongsTo(ClientCode::class);
    }

    public function scopeRoleFilterable(Builder $query)
    {
        if (request()->has('role')) {
            $query->where('role', request('role'));
        }

        return $query;
    }

    public function cart_items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function avatar_image()
    {
        return $this->belongsTo(Image::class, 'avatar_image_id');
    }

    public function refund_requests()
    {
        return $this->hasMany(RefundRequest::class);
    }

    public function reviewed_refund_requests()
    {
        return $this->hasMany(RefundRequest::class, 'reviewed_by');
    }

    public function performed_transaction_audit_logs()
    {
        return $this->hasMany(TransactionAuditLog::class, 'performed_by');
    }

    public function reviewed_transactions()
    {
        return $this->hasMany(Transaction::class, 'reviewed_by');
    }

    public function scopeWithRelations(Builder $query)
    {
        $request = request();

        // Dynamically include relations if requested
        if ($request->has('with')) {
            $relations = explode(',', $request->get('with'));
            // Filter out invalid relation names for security

            $validRelations = [
                'avatar_image', //
                'client_code', //
                'cart_items', //
                'addresses', //
                'orders', //
                'transactions', //
                'refund_requests', //
                'reviewed_refund_requests',
                'performed_transaction_audit_logs',
                'reviewed_transactions',
                'statuses.set_by_user',
                'set_statuses.user',
                'preferences'
            ];

            $relations = array_intersect($relations, $validRelations);

            if (!empty($relations)) {
                $query->with($relations);
            }
        } else {
            // Default relations if none requested
            $query->with(['avatar_image', 'client_code']);
        }

        return $query;
    }

    public function statuses()
    {
        return $this->hasMany(UserStatus::class, 'user_id');
    }

    public function currentStatus(): ?UserStatus
    {
        return $this->statuses()
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest('created_at')
            ->first();
    }

    public function getStatusAttribute()
    {
        return $this->currentStatus();
    }

    public function set_statuses()
    {
        return $this->hasMany(UserStatus::class, 'set_by');
    }

    public function preferences()
    {
        return $this->hasOne(UserPreference::class, 'user_id');
    }

    /**
     * Scope a query to only include users whose current status is in the given list.
     *
     * @param Builder $query
     * @param array $statuses
     * @return Builder
     */
    public function scopeWhereCurrentStatusIn(Builder $query, array $statuses): Builder
    {
        $statuses = array_intersect($statuses, ['approved', 'blocked', 'suspended', 'pending']);
        if (empty($statuses)) {
            return $query;
        }

        return $query->whereExists(function ($sub) use ($statuses) {
            $sub->select(DB::raw(1))
                ->from('user_statuses as us')
                ->whereColumn('us.user_id', 'users.id')
                ->whereIn('us.status', $statuses)
                ->where(function ($exp) {
                    $exp->whereNull('us.expires_at')
                        ->orWhere('us.expires_at', '>', now());
                })
                ->orderByDesc('us.created_at')
                ->limit(1);
        });
    }
}
