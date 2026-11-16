<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Admn_Cnta_Refa_Mast extends Model
{
    use HasFactory;

    protected $table = 'admn_cnta_refa_mast';
    protected $primaryKey = 'Admn_Cnta_Refa_Mast_UIN';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'Admn_Cnta_Refa_Mast_UIN',
        'Admn_User_Mast_UIN',
        'Refa_Name',
        'Refa_Rsip',
        'Refa_Phon',
        'Refa_Emai',
        'Is_Prmy',
        'CrBy',
        'CrOn',
        'MoBy',
        'MoOn',
        'VfBy',
        'VfOn',
        'Stau',
        'Del_By',
        'Del_On'
    ];

    protected $casts = [
        'Admn_Cnta_Refa_Mast_UIN' => 'integer',
        'Admn_User_Mast_UIN' => 'integer',
        'Is_Prmy' => 'boolean',
        'CrOn' => 'datetime',
        'MoOn' => 'datetime',
        'VfOn' => 'datetime',
        'Del_On' => 'datetime',
        'Stau' => 'integer',
    ];

    /**
     * Get the user that owns the reference.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(Admn_User_Mast::class, 'Admn_User_Mast_UIN', 'Admn_User_Mast_UIN');
    }

    /**
     * Scope a query to only include primary references
     */
    public function scopePrimary($query)
    {
        return $query->where('Is_Prmy', true);
    }
}
