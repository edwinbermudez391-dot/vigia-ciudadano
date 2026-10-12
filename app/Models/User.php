<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'department_id',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Defaults del modelo, coherentes con los defaults de la tabla (migración
     * add_fields_to_users_table). Sin esto, un User recién creado en memoria
     * tendría role/is_active nulos hasta refrescarlo desde BD, y authorize()
     * de los FormRequests negaría el acceso a usuarios recién registrados.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => UserRole::Citizen->value,
        'is_active' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'department_id' => 'integer',
        ];
    }

    /**
     * Dependencia a la que pertenece el usuario (nula para administradores e inspectores).
     *
     * @return BelongsTo<Department, User>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Reportes creados por este usuario (si tiene cuenta vinculada).
     *
     * @return HasMany<Report>
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    /**
     * Órdenes de trabajo asignadas cuando el usuario es contratista.
     *
     * @return HasMany<WorkOrder>
     */
    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'contractor_id');
    }

    /**
     * Inspecciones de calidad realizadas cuando el usuario es inspector.
     *
     * @return HasMany<QualityInspection>
     */
    public function qualityInspections(): HasMany
    {
        return $this->hasMany(QualityInspection::class, 'inspector_id');
    }

    /**
     * Reportes a los que el usuario está suscrito.
     *
     * @return BelongsToMany<Report>
     */
    public function subscribedReports(): BelongsToMany
    {
        return $this->belongsToMany(Report::class, 'report_subscribers')
            ->withTimestamps();
    }

    public function isContractor(): bool
    {
        return $this->role === UserRole::Contractor;
    }

    public function isInspector(): bool
    {
        return $this->role === UserRole::QualityInspector;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }
}
