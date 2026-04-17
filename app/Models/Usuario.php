<?php

namespace App\Models;

use App\Enums\FuncaoUsuario;
use Database\Factories\UsuarioFactory;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UsuarioFactory> */
    use HasApiTokens, HasFactory, HasUuids, MustVerifyEmailTrait, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    protected $table = 'usuarios';

    protected $keyType = 'string';

    public $incrementing = false;

    public const CREATED_AT = 'criado_em';

    public const UPDATED_AT = 'atualizado_em';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nome',
        'email',
        'email_verified_at',
        'cpf',
        'senha_hash',
        'funcao',
        'brigada_id',
        'bloqueado',
        'remember_token',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'senha_hash',
        'cpf',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'senha_hash' => 'hashed',
            'funcao' => FuncaoUsuario::class,
            'bloqueado' => 'boolean',
            'email_verified_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->senha_hash;
    }

    public function getEmailForVerification(): string
    {
        return $this->email;
    }

    /**
     * @return BelongsTo<Brigada, $this>
     */
    public function brigada(): BelongsTo
    {
        return $this->belongsTo(Brigada::class, 'brigada_id');
    }

    /**
     * @return HasMany<TokenRecuperacaoSenha, $this>
     */
    public function tokensRecuperacaoSenha(): HasMany
    {
        return $this->hasMany(TokenRecuperacaoSenha::class, 'usuario_id');
    }

    /**
     * @return HasMany<Incendio, $this>
     */
    public function incendios(): HasMany
    {
        return $this->hasMany(Incendio::class, 'usuario_id');
    }

    /**
     * @return HasMany<LogAuditoria, $this>
     */
    public function logsAuditoria(): HasMany
    {
        return $this->hasMany(LogAuditoria::class, 'usuario_id');
    }
}
