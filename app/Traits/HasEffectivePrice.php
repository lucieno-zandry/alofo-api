<?php

namespace App\Traits;

use App\Models\User;

trait HasEffectivePrice
{
    public function setEffectivePriceForUser(?User $user)
    {
        $this->effective_price = $this->getEffectivePrice($user);
        $this->applied_promotions = $this->getAppliedPromotions($user);
        return $this;
    }
}
