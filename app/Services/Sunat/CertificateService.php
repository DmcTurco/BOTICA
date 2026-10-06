<?php

namespace App\Services\Sunat;

use App\Models\CompanySunatSetting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

/**
 * Valida y guarda el certificado digital (.pfx / .p12) de una compañía.
 * El archivo se guarda cifrado en el disco privado (storage/app/private).
 */
class CertificateService
{
    private const DISK = 'local';

    /**
     * Valida que el contenido sea un PKCS#12 válido y que la clave sea correcta.
     * Devuelve el titular y la fecha de vencimiento del certificado.
     *
     * @return array{subject: string, expires_at: \Illuminate\Support\Carbon}
     *
     * @throws InvalidArgumentException si el archivo o la clave no son válidos
     */
    public function inspect(string $contents, string $password): array
    {
        $certs = [];

        if (!@openssl_pkcs12_read($contents, $certs, $password) || empty($certs['cert'])) {
            throw new InvalidArgumentException('No se pudo leer el certificado. Revisa el archivo y la clave.');
        }

        $info = openssl_x509_parse($certs['cert']);

        if (!$info || empty($info['validTo_time_t'])) {
            throw new InvalidArgumentException('El certificado no tiene una fecha de vencimiento válida.');
        }

        return [
            'subject'    => $info['subject']['CN'] ?? ($info['name'] ?? 'Sin titular'),
            'expires_at' => \Illuminate\Support\Carbon::createFromTimestamp($info['validTo_time_t']),
        ];
    }

    /**
     * Valida y guarda el certificado de la compañía (reemplaza el anterior).
     * Devuelve los campos listos para guardar en company_sunat_settings.
     *
     * @return array<string, mixed>
     */
    public function store(int $companyId, string $contents, string $password): array
    {
        $data = $this->inspect($contents, $password);

        $path = "sunat/{$companyId}/certificate.enc";

        // Cifrado en reposo: el archivo no queda en claro en el disco
        Storage::disk(self::DISK)->put($path, Crypt::encryptString(base64_encode($contents)));

        return [
            'certificate_path'       => $path,
            'certificate_password'   => $password,
            'certificate_subject'    => $data['subject'],
            'certificate_expires_at' => $data['expires_at']->toDateString(),
        ];
    }

    /**
     * Devuelve el contenido original (.pfx) del certificado guardado.
     */
    public function read(CompanySunatSetting $setting): string
    {
        if (!$setting->hasCertificate() || !Storage::disk(self::DISK)->exists($setting->certificate_path)) {
            throw new InvalidArgumentException('La compañía no tiene un certificado cargado.');
        }

        return base64_decode(Crypt::decryptString(Storage::disk(self::DISK)->get($setting->certificate_path)));
    }
}
