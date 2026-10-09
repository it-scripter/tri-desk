<?php

namespace App\Domain\Access\Models;

use App\Models\User;
use App\Support\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Permission\Models\Role;

/**
 * Assigns a role to a user, either globally or within a scope (an edition or
 * an org unit). An empty scope_type means the assignment is global.
 *
 * @property int $id
 * @property int $user_id
 * @property int $role_id
 * @property string $scope_type
 * @property int $scope_id
 */
class RoleAssignment extends Model
{
    use RecordsActivity;

    protected $fillable = [
        'user_id',
        'role_id',
        'scope_type',
        'scope_id',
    ];

    /**
     * Who may do what is security relevant, so every grant and revocation is
     * recorded in full.
     *
     * @return array<int, string>
     */
    protected function auditedAttributes(): array
    {
        return ['user_id', 'role_id', 'scope_type', 'scope_id'];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scope_id' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * The edition or org unit this assignment is limited to.
     *
     * @return MorphTo<Model, $this>
     */
    public function scope(): MorphTo
    {
        return $this->morphTo(type: 'scope_type', id: 'scope_id');
    }

    public function isGlobal(): bool
    {
        return $this->scope_type === '';
    }

    /**
     * Restrict to assignments that apply to the given scope. Passing null
     * matches global assignments only.
     *
     * @param  Builder<RoleAssignment>  $query
     */
    public function scopeForScope(Builder $query, ?Model $scope): void
    {
        if ($scope === null) {
            $query->where('scope_type', '')->where('scope_id', 0);

            return;
        }

        $query->where('scope_type', $scope->getMorphClass())
            ->where('scope_id', $scope->getKey());
    }
}
