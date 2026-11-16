<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class AdmnBankAttachment extends Model
{
    use HasFactory;

    protected $table = 'admn_bank_attachments';
    protected $primaryKey = 'Admn_Bank_Attachment_UIN';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'Admn_Bank_Attachment_UIN', 'Admn_User_Bank_Mast_UIN',
        'Atch_Path', 'Atch_Path_2', 'Atch_Type_1', 'Atch_Type_2',
        'Orgn_Name', 'CrBy', 'CrOn', 'MoBy', 'MoOn', 'VfOn', 'VfBy'
    ];

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(Admn_User_Bank_Mast::class, 'Admn_User_Bank_Mast_UIN', 'Admn_User_Bank_Mast_UIN');
    }
}
