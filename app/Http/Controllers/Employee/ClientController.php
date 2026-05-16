<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\DocumentSeries;
use App\Models\DocumentType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClientController extends Controller
{
    /**
     * Lista de clientes con búsqueda.
     */
    public function index(Request $request)
    {
        $query = Client::with('documentType')->orderBy('name');

        if ($request->filled('buscar')) {
            $search = $request->buscar;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('document_number', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $clients = $query->paginate(15)->withQueryString();

        return view('company.pages.clients.index', compact('clients'));
    }

    /**
     * Formulario de creación.
     */
    public function create()
    {
        $documentTypes = DocumentType::activos()->get();
        return view('company.pages.clients.form', compact('documentTypes'));
    }

    /**
     * Guarda un nuevo cliente con código automático.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'             => 'required|string|max:150',
            'document_type_id' => 'nullable|exists:document_types,id',
            'document_number'  => 'nullable|string|max:20',
            'phone'            => 'nullable|string|max:20',
            'email'            => 'nullable|email|max:100',
            'address'          => 'nullable|string|max:200',
        ], [
            'name.required' => 'El nombre del cliente es obligatorio.',
            'email.email'   => 'El correo electrónico no tiene un formato válido.',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $code = DocumentSeries::siguiente(DocumentSeries::CLIENTE);

                Client::create([
                    'code'             => $code,
                    'name'             => $request->name,
                    'document_type_id' => $request->document_type_id ?: null,
                    'document_number'  => $request->document_number ?: null,
                    'phone'            => $request->phone ?: null,
                    'email'            => $request->email ?: null,
                    'address'          => $request->address ?: null,
                    'status'           => 1,
                ]);
            });

            return redirect()->route('company.clients.index')
                ->with('success', 'Cliente registrado correctamente.');

        } catch (\Exception $e) {
            Log::error('Error al crear cliente: ' . $e->getMessage());
            return back()->withInput()
                ->with('error', 'No se pudo registrar el cliente. Intenta de nuevo.');
        }
    }

    /**
     * Formulario de edición.
     */
    public function edit(Client $client)
    {
        $documentTypes = DocumentType::activos()->get();
        return view('company.pages.clients.form', compact('client', 'documentTypes'));
    }

    /**
     * Actualiza los datos del cliente.
     */
    public function update(Request $request, Client $client)
    {
        $request->validate([
            'name'             => 'required|string|max:150',
            'document_type_id' => 'nullable|exists:document_types,id',
            'document_number'  => 'nullable|string|max:20',
            'phone'            => 'nullable|string|max:20',
            'email'            => 'nullable|email|max:100',
            'address'          => 'nullable|string|max:200',
            'status'           => 'required|in:0,1',
        ]);

        try {
            $client->update([
                'name'             => $request->name,
                'document_type_id' => $request->document_type_id ?: null,
                'document_number'  => $request->document_number ?: null,
                'phone'            => $request->phone ?: null,
                'email'            => $request->email ?: null,
                'address'          => $request->address ?: null,
                'status'           => $request->status,
            ]);

            return redirect()->route('company.clients.index')
                ->with('success', "Cliente {$client->code} actualizado correctamente.");

        } catch (\Exception $e) {
            Log::error('Error al actualizar cliente: ' . $e->getMessage());
            return back()->withInput()
                ->with('error', 'No se pudo actualizar el cliente. Intenta de nuevo.');
        }
    }

    /**
     * Búsqueda de clientes para el autocompletado del POS.
     * Devuelve JSON con los primeros 8 resultados que coincidan.
     */
    public function search(Request $request)
    {
        $term = trim($request->query('q', ''));

        if (strlen($term) < 2) {
            return response()->json([]);
        }

        $clients = Client::with('documentType')
            ->activos()
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('document_number', 'like', "%{$term}%")
                  ->orWhere('code', 'like', "%{$term}%");
            })
            ->orderBy('name')
            ->limit(8)
            ->get()
            ->map(fn($c) => [
                'id'                => $c->id,
                'code'              => $c->code,
                'name'              => $c->name,
                'document_type'     => $c->documentType?->name,
                'document_type_id'  => $c->document_type_id,
                'document_number'   => $c->document_number,
                'phone'             => $c->phone,
            ]);

        return response()->json($clients);
    }
}
