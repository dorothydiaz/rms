<?php

namespace App\Models\Hr;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternalNotification extends Model
{
    use HasFactory;

    protected $table = 'internal_notifications';
    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'role_target',
        'branch_id',
        'title',
        'message',
        'type',
        'link',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    /**
     * Scope notifications visible to the given user.
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhere(function (Builder $broadcast) use ($user) {
                  $broadcast->whereNull('user_id')
                      ->where(function (Builder $roleQ) use ($user) {
                          $roleQ->whereNull('role_target');
                          if ($user->isSuperAdmin()) {
                              $roleQ->orWhereNotNull('role_target');
                          } elseif (!empty($user->role)) {
                              $roleQ->orWhereRaw('LOWER(role_target) = ?', [strtolower((string) $user->role)]);
                          }
                      });

                  if (!$user->isSuperAdmin() && !$user->isHrAdmin()) {
                      $branchIds = $user->branches()->pluck('branches.branch_id')->map(fn ($id) => (int) $id)->all();
                      if ($user->branch_id) {
                          $branchIds[] = (int) $user->branch_id;
                      }
                      $branchIds = array_values(array_unique(array_filter($branchIds)));

                      $broadcast->where(function (Builder $branchQ) use ($branchIds) {
                          $branchQ->whereNull('branch_id');
                          if (!empty($branchIds)) {
                              $branchQ->orWhereIn('branch_id', $branchIds);
                          }
                      });
                  }
              });
        });
    }
}
