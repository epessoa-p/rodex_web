<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientDocument;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    public function index()
    {
        $user  = auth()->user();
        $query = Client::with(['company'])
            ->withCount(['sales', 'workOrders'])
            // Saldo pendiente del cliente (ventas a crédito por cobrar)
            ->addSelect(['credit_due' => \App\Models\Sales\Sale::selectRaw('COALESCE(SUM(total - paid_amount), 0)')
                ->whereColumn('client_id', 'clients.id')
                ->where('sale_type', 'credit')
                ->whereIn('payment_status', ['pending', 'partial'])
                ->where('status', 'completed')])
            ->latest();

        if (!$user->is_super_admin) {
            $query->where('company_id', $user->getCurrentCompany()?->id);
        }

        $clients = $query->paginate(15);

        return view('admin.clients.index', compact('clients'));
    }

    public function create()
    {
        return view('admin.clients.create', $this->formData());
    }

    public function store()
    {
        $user      = auth()->user();
        $companyId = $user->is_super_admin ? request('company_id') : $user->getCurrentCompany()?->id;

        if (!$companyId) {
            return back()->withInput()->withErrors(['error' => 'No hay una empresa activa seleccionada.']);
        }

        $validated = request()->validate([
            'full_name'      => 'required|string|max:255',
            'id_number'      => 'nullable|string|max:50',
            'phone'          => 'nullable|string|max:20',
            'email'          => 'nullable|email|max:255',
            'address'        => 'nullable|string|max:500',
            'location_link'  => 'nullable|url|max:1000',
            'notes'          => 'nullable|string',
            'active'         => 'sometimes|boolean',
            'photo'          => 'nullable|image|max:2048',
            'doc_type.*'     => ['nullable', Rule::in(array_keys(ClientDocument::TYPES))],
            'doc_label.*'    => 'nullable|string|max:255',
            'doc_file.*'     => 'nullable|file|mimes:jpg,jpeg,png,pdf,webp|max:5120',
        ]);

        try {
            DB::transaction(function () use ($validated, $companyId) {
                $photoPath = null;
                if (request()->hasFile('photo')) {
                    $photoPath = request()->file('photo')
                        ->store("company/{$companyId}/clients/photos", 'public');
                }

                $client = Client::create([
                    'company_id'    => $companyId,
                    'full_name'     => $validated['full_name'],
                    'id_number'     => $validated['id_number'] ?? null,
                    'phone'         => $validated['phone'] ?? null,
                    'email'         => $validated['email'] ?? null,
                    'address'       => $validated['address'] ?? null,
                    'location_link' => $validated['location_link'] ?? null,
                    'photo'         => $photoPath,
                    'notes'         => $validated['notes'] ?? null,
                    'active'        => request()->boolean('active', true),
                    'created_by'    => auth()->id(),
                ]);

                $this->saveDocuments($client, $companyId);
            });

            return redirect()->route('clients.index')->with('success', 'Cliente registrado exitosamente.');
        } catch (\Throwable $e) {
            Log::error('Error al crear cliente', ['message' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }

    /** Alta rápida de cliente (AJAX, devuelve JSON) */
    public function quickStore()
    {
        $user      = auth()->user();
        $companyId = $user->is_super_admin ? request('company_id') : $user->getCurrentCompany()?->id;

        if (!$companyId) {
            return response()->json(['ok' => false, 'message' => 'No hay una empresa activa.'], 422);
        }

        $validated = request()->validate([
            'full_name' => 'required|string|max:255',
            'id_number' => 'nullable|string|max:50',
            'phone'     => 'nullable|string|max:20',
            'email'     => 'nullable|email|max:255',
            'address'   => 'nullable|string|max:500',
        ]);

        try {
            $client = Client::create([
                'company_id' => $companyId,
                'full_name'  => $validated['full_name'],
                'id_number'  => $validated['id_number'] ?? null,
                'phone'      => $validated['phone'] ?? null,
                'email'      => $validated['email'] ?? null,
                'address'    => $validated['address'] ?? null,
                'active'     => true,
                'created_by' => $user->id,
            ]);

            return response()->json([
                'ok'     => true,
                'client' => [
                    'id'        => $client->id,
                    'full_name' => $client->full_name,
                    'id_number' => $client->id_number,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Error en alta rápida de cliente', ['msg' => $e->getMessage()]);
            return response()->json(['ok' => false, 'message' => 'Error al guardar el cliente.'], 500);
        }
    }

    public function show(Client $client)
    {
        $this->authorizeClient($client);

        $client->load([
            'documents', 'createdBy', 'company',
            'sales.branch',
            'workOrders',
            'vehicles',
            'rentalContracts.motoUnit.model.brand',
            'quotes',
            'warranties.motoUnit.model.brand',
        ]);
        $client->loadCount(['sales', 'workOrders', 'vehicles', 'rentalContracts', 'quotes', 'warranties']);

        return view('admin.clients.show', compact('client'));
    }

    public function edit(Client $client)
    {
        $this->authorizeClient($client);
        return view('admin.clients.edit', array_merge($this->formData(), compact('client')));
    }

    public function update(Client $client)
    {
        $this->authorizeClient($client);

        $validated = request()->validate([
            'full_name'      => 'required|string|max:255',
            'id_number'      => 'nullable|string|max:50',
            'phone'          => 'nullable|string|max:20',
            'email'          => 'nullable|email|max:255',
            'address'        => 'nullable|string|max:500',
            'location_link'  => 'nullable|url|max:1000',
            'notes'          => 'nullable|string',
            'active'         => 'sometimes|boolean',
            'photo'          => 'nullable|image|max:2048',
            'doc_type.*'     => ['nullable', Rule::in(array_keys(ClientDocument::TYPES))],
            'doc_label.*'    => 'nullable|string|max:255',
            'doc_file.*'     => 'nullable|file|mimes:jpg,jpeg,png,pdf,webp|max:5120',
        ]);

        try {
            DB::transaction(function () use ($client, $validated) {
                $photoPath = $client->photo;

                if (request()->hasFile('photo')) {
                    if ($client->photo) {
                        Storage::disk('public')->delete($client->photo);
                    }
                    $photoPath = request()->file('photo')
                        ->store("company/{$client->company_id}/clients/photos", 'public');
                }

                $client->update([
                    'full_name'     => $validated['full_name'],
                    'id_number'     => $validated['id_number'] ?? null,
                    'phone'         => $validated['phone'] ?? null,
                    'email'         => $validated['email'] ?? null,
                    'address'       => $validated['address'] ?? null,
                    'location_link' => $validated['location_link'] ?? null,
                    'photo'         => $photoPath,
                    'notes'         => $validated['notes'] ?? null,
                    'active'        => request()->boolean('active', false),
                ]);

                $this->saveDocuments($client, $client->company_id);
            });

            return redirect()->route('clients.show', $client)->with('success', 'Cliente actualizado exitosamente.');
        } catch (\Throwable $e) {
            Log::error('Error al actualizar cliente', ['id' => $client->id, 'message' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }

    public function destroy(Client $client)
    {
        $this->authorizeClient($client);

        try {
            // Eliminar archivos físicos
            if ($client->photo) {
                Storage::disk('public')->delete($client->photo);
            }
            foreach ($client->documents as $doc) {
                Storage::disk($doc->resolveDisk())->delete($doc->file_path);
            }

            $client->delete();

            return redirect()->route('clients.index')->with('success', 'Cliente eliminado exitosamente.');
        } catch (\Throwable $e) {
            Log::error('Error al eliminar cliente', ['id' => $client->id, 'message' => $e->getMessage()]);
            return back()->withErrors(['error' => 'No fue posible eliminar el cliente.']);
        }
    }

    /**
     * Descarga autorizada de un documento de cliente.
     *
     * Los documentos ya no son accesibles por URL pública: solo se entregan
     * tras comprobar que el documento pertenece a la empresa activa.
     */
    public function downloadDocument(ClientDocument $document)
    {
        $this->authorizeClient($document->client);

        $disk = $document->resolveDisk();

        abort_unless(Storage::disk($disk)->exists($document->file_path), 404);

        return Storage::disk($disk)->response(
            $document->file_path,
            $document->file_name
        );
    }

    public function destroyDocument(ClientDocument $document)
    {
        $this->authorizeClient($document->client);

        try {
            Storage::disk($document->resolveDisk())->delete($document->file_path);
            $document->delete();
            return back()->with('success', 'Documento eliminado.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'No fue posible eliminar el documento.']);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function saveDocuments(Client $client, int $companyId): void
    {
        $types  = request()->input('doc_type', []);
        $labels = request()->input('doc_label', []);
        $files  = request()->file('doc_file', []);

        foreach ($files as $index => $file) {
            if (!$file) continue;

            $type  = $types[$index] ?? 'other';
            $label = $labels[$index] ?? null;

            // Documentos sensibles (CI, facturas): disco PRIVADO, fuera de public/,
            // segmentados por empresa. Se sirven por clients.documents.download.
            $path  = $file->store("company/{$companyId}/clients/{$client->id}/documents", 'local');

            ClientDocument::create([
                'client_id'  => $client->id,
                'company_id' => $companyId,
                'type'       => $type,
                'label'      => $label,
                'file_path'  => $path,
                'file_name'  => $file->getClientOriginalName(),
            ]);
        }
    }

    private function authorizeClient(Client $client): void
    {
        if (!auth()->user()->is_super_admin && $client->company_id !== auth()->user()->getCurrentCompany()?->id) {
            abort(403);
        }
    }

    private function formData(): array
    {
        $user      = auth()->user();
        $companies = $user->is_super_admin
            ? Company::orderBy('name')->get()
            : collect([$user->getCurrentCompany()])->filter();

        return compact('companies');
    }
}
