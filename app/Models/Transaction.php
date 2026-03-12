<?php

namespace App\Models;

use App\Traits\ApplyFilters;
use App\Traits\CustomerFilterable;
use App\Traits\DynamicConditionApplicable;
use App\Traits\WithOrdering;
use App\Traits\WithPagination;
use App\Traits\WithRelationships;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use WithRelationships, WithPagination, WithOrdering, ApplyFilters, DynamicConditionApplicable, CustomerFilterable;

    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    public static $STATUS_SUCCESS = 'SUCCESS';
    public static $STATUS_FAILED = 'FAILED';
    public static $STATUS_PENDING = 'PENDING';

    protected $fillable = [
        'status',
        'informations',
        'user_id',
        'order_uuid',
        'amount',
        'payment_url',
        'uuid',
        'method',
        'payment_reference',
        'reviewed_at',
        'reviewed_by',
        'notes',
        'dispute_status',
        'dispute_opened_at',
        'dispute_resolved_at',
        'dispute_reason',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_uuid');
    }

    public function webhook_logs()
    {
        return $this->hasMany(PaymentWebhookLog::class, 'transaction_uuid');
    }

    public function audit_logs()
    {
        return $this->hasMany(TransactionAuditLog::class, 'transaction_uuid');
    }

    public function parent_transaction()
    {
        return $this->belongsTo(Transaction::class, 'parent_transaction_uuid');
    }

    public function child_transactions()
    {
        return $this->hasMany(Transaction::class, 'parent_transaction_uuid');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function refund_requests()
    {
        return $this->hasMany(RefundRequest::class, 'transaction_uuid', 'uuid');
    }

    public function preformed_by_user()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
