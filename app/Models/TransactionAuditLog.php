<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionAuditLog extends Model
{
    protected $fillable = [
        'transaction_uuid',
        'performed_by',
        'action',
        'old_value',
        'new_value',
        'reason',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    public function performed_by_user()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
