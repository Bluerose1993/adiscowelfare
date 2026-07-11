<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class StaffDeletionRequest extends Model {
    protected $fillable = ['staff_id','requested_by','reason','status','reviewed_by','reviewed_at','review_notes'];
    protected function casts(): array { return ['reviewed_at'=>'datetime']; }
    public function staff(): BelongsTo { return $this->belongsTo(Staff::class)->withTrashed(); }
    public function requester(): BelongsTo { return $this->belongsTo(User::class,'requested_by'); }
}
