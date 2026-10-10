<?php

namespace App\Services\Workshop;

use App\Models\Client;

/**
 * Cliente "rápido" de una cita (nombre + teléfono, sin elegirlo de la lista).
 * Se registra como cliente real para que la cita y su OT queden a su nombre.
 *
 * El teléfono identifica al cliente: si ya existe uno con ese número se
 * reutiliza, salvo que el usuario pida registrar uno nuevo ($forceNew). La
 * web y la app preguntan antes de guardar cuando el número es de otro nombre.
 */
class QuickClientResolver
{
    /** Cliente de la empresa con ese teléfono (compara solo los dígitos). */
    public function findByPhone(int $companyId, string $phone): ?Client
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if ($digits === '') {
            return null;
        }

        // Candidatos por los últimos dígitos, quitando los separadores comunes
        // ("7 001-2345" → "70012345"; portable, sin REGEXP_REPLACE), y luego
        // comparación exacta de dígitos en PHP.
        $clean = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '.', ''), '(', ''), ')', ''), '+', '')";

        return Client::where('company_id', $companyId)
            ->whereRaw("$clean LIKE ?", ['%' . substr($digits, -6) . '%'])
            ->get()
            ->first(fn (Client $c) => preg_replace('/\D+/', '', (string) $c->phone) === $digits);
    }

    /** Busca por teléfono o crea el cliente. Con $forceNew siempre crea uno nuevo. */
    public function resolve(int $companyId, string $name, string $phone, bool $forceNew = false): Client
    {
        $existing = $forceNew ? null : $this->findByPhone($companyId, $phone);

        return $existing ?? Client::create([
            'company_id' => $companyId,
            'full_name'  => $name,
            'phone'      => $phone,
            'active'     => true,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Normaliza los datos validados de una cita: con nombre + teléfono y sin
     * cliente elegido, registra el cliente y deja la cita a su nombre.
     */
    public function apply(int $companyId, array $data, bool $forceNew = false): array
    {
        if (empty($data['client_id']) && ! empty($data['customer_name']) && ! empty($data['customer_phone'])) {
            $client = $this->resolve($companyId, trim($data['customer_name']), trim($data['customer_phone']), $forceNew);
            $data['client_id']      = $client->id;
            $data['customer_name']  = null;
            $data['customer_phone'] = null;
        }

        return $data;
    }
}
