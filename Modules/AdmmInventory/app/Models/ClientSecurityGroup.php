<?php

namespace Modules\AdmmInventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientSecurityGroup extends Model
{
    protected $table    = 'client_security_groups';
    protected $fillable = ['client_id', 'navixy_security_group_id', 'navixy_instance', 'label'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}