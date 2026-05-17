<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'saml_nameid',
        'saml_attributes',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'saml_attributes',
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
            'saml_attributes' => 'array',
        ];
    }

    /**
     * Get the SAML NameID for the user.
     *
     * @return string|null
     */
    public function getSamlNameId(): ?string
    {
        return $this->saml_nameid;
    }

    /**
     * Set the SAML NameID for the user.
     *
     * @param string|null $nameId
     * @return $this
     */
    public function setSamlNameId(?string $nameId): self
    {
        $this->saml_nameid = $nameId;
        return $this;
    }

    /**
     * Get the SAML attributes for the user.
     *
     * @return array|null
     */
    public function getSamlAttributes(): ?array
    {
        return $this->saml_attributes;
    }

    /**
     * Set the SAML attributes for the user.
     *
     * @param array|null $attributes
     * @return $this
     */
    public function setSamlAttributes(?array $attributes): self
    {
        $this->saml_attributes = $attributes;
        return $this;
    }

    /**
     * Get a specific SAML attribute.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getSamlAttribute(string $key, $default = null)
    {
        $attributes = $this->getSamlAttributes();
        return $attributes[$key] ?? $default;
    }

    /**
     * Get the message history for the user.
     *
     * @return HasMany
     */
    public function messageHistory(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }
}
