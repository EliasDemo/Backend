<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Storage;

trait HasProfilePhoto
{
    public function getProfilePhotoUrlAttribute(): string
    {
        if ($this->profile_photo) {
            // Si tienes un path en storage/app/public/...
            return Storage::url($this->profile_photo);
        }

        // Imagen por defecto si no hay foto
        return 'https://img.freepik.com/vector-premium/no-hay-foto-disponible-icono-vectorial-simbolo-imagen-predeterminado-imagen-proximamente-sitio-web-o-aplicacion-movil_87543-10639.jpg';
    }
}
