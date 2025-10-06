<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class Imagen extends Model
{
    use HasFactory;

    protected $table = 'imagenes';

    protected $fillable = [
        'imageable_type','imageable_id',
        'disk','path','url','titulo','caption','orden',
        'width','height','metadata','visibilidad','subido_por',
    ];

    protected $casts = [
        'metadata' => 'array',
        'orden'    => 'int',
    ];

    protected $appends = ['public_url'];

    public function imageable(): MorphTo { return $this->morphTo(); }

    public function getPublicUrlAttribute(): ?string
    {
        if ($this->url) return $this->url;
        if ($this->disk && $this->path) {
            try { return Storage::disk($this->disk)->url($this->path); }
            catch (\Throwable) { return null; }
        }
        return null;
    }
}
