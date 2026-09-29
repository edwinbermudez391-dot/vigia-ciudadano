<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Almacenamiento defensivo de imágenes subidas por clientes.
 *
 * Reglas de seguridad aplicadas:
 *  - La extensión del archivo en disco se deriva del MIME REAL detectado por
 *    finfo sobre el contenido (guessExtension()), nunca del nombre que envía
 *    el cliente: mitiga path traversal (../../) y doble extensión (shell.php.jpg).
 *  - El nombre base lo aporta la capa llamante (p. ej. el ticket code, único
 *    garantizado), por lo que no se reutilizan nombres hostiles.
 *  - Nunca se guarda fuera del disco configurado (`public`).
 */
final class ImageUploader
{
    public function __construct(
        private readonly string $disk = 'public',
    ) {}

    /**
     * Almacena la imagen y devuelve la ruta relativa dentro del disco.
     */
    public function upload(UploadedFile $image, string $folder, string $baseName): string
    {
        $extension = $image->guessExtension();

        if ($extension === null) {
            throw new RuntimeException(
                'La imagen no tiene un tipo MIME reconocido y no puede almacenarse de forma segura.'
            );
        }

        $path = $image->storeAs($folder, $baseName.'.'.$extension, $this->disk);

        if ($path === false) {
            throw new RuntimeException('No se pudo escribir la imagen en el disco de almacenamiento.');
        }

        return $path;
    }

    /**
     * Compensación: retira el archivo si la transacción de BD falló después
     * de subir la imagen (el filesystem no es transaccional).
     */
    public function delete(string $relativePath): void
    {
        Storage::disk($this->disk)->delete($relativePath);
    }
}
