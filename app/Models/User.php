<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Domain\Access\Concerns\HasRoleAssignments;
use App\Domain\Access\Enums\RoleName;
use App\Support\Concerns\RecordsActivity;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['first_name', 'last_name', 'email', 'phone', 'birth_date', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasName
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoleAssignments, Notifiable, RecordsActivity;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'birth_date' => 'date',
            'password' => 'hashed',
        ];
    }

    /**
     * Password, remember token and verification timestamps are deliberately
     * absent: the log must never carry credentials.
     *
     * @return array<int, string>
     */
    protected function auditedAttributes(): array
    {
        return ['first_name', 'last_name', 'email', 'phone', 'birth_date'];
    }

    /**
     * Full name for lists and salutations. The name is stored split because
     * mails address people by first name (placeholder {vorname}, H-41).
     */
    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getFilamentName(): string
    {
        return $this->fullName();
    }

    /**
     * Which of the three panels this user may open. The panels are separated by
     * role, not only by navigation: an orga member must not reach /admin.
     * Permissions inside a panel are checked by the policies on top of this.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return match ($panel->getId()) {
            'orga' => $this->holdsAnyRole([RoleName::Orga, RoleName::Redaktion]),
            'team' => $this->holdsAnyRole([RoleName::Teamleitung]),
            default => false,
        };
    }
}
