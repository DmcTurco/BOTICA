<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ClientImportRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Services\ClientProductImport;
use App\Services\CompanyClientDataPack;
use App\Support\CsvExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Importar los productos y el stock de un cliente que migra de otro sistema:
 *   1. descargar el modelo y llenarlo,
 *   2. subir el archivo (todavía sin elegir compañía),
 *   3. verlo completo en su propia vista (con errores y avisos),
 *   4. elegir la compañía y sede, y registrar.
 * Nada se guarda en los productos hasta registrar; si el archivo tiene errores no se deja registrar.
 */
class ClientImportController extends Controller
{
    /** Carpeta (en el disco local) donde espera el archivo ya leído hasta que se registra */
    private const DIRECTORY = 'client-imports';

    /**
     * Pantalla para descargar el modelo y subir el archivo.
     */
    public function create()
    {
        return view('admin.pages.client-imports.create');
    }

    /**
     * Modelo de Excel (CSV) con los encabezados y una fila de ejemplo que la importación ignora.
     */
    public function template()
    {
        return CsvExport::download(
            'modelo_productos_cliente',
            array_column(ClientProductImport::TEMPLATE, 0),
            [ClientProductImport::exampleRow()],
        );
    }

    /**
     * Lee y valida el archivo sin tocar los productos, lo deja esperando y abre su vista de revisión.
     */
    public function preview(ClientImportRequest $request)
    {
        $file = $request->file('file');

        try {
            $result = ClientProductImport::parse($file->getRealPath(), $file->getClientOriginalExtension());
        } catch (Throwable $e) {
            return back()->with('error', 'No se pudo leer el archivo: ' . $e->getMessage());
        }

        if (!$result['rows'] && !$result['errors']) {
            return back()->with('error', 'El archivo no tiene productos. Llena el modelo (la fila de ejemplo no cuenta) y vuelve a subirlo.');
        }

        $this->purgeOld();
        $token = Str::random(40);
        Storage::disk('local')->put(self::DIRECTORY . "/{$token}.json", json_encode([
            'file_name' => $file->getClientOriginalName(),
            'rows'      => $result['rows'],
            'errors'    => $result['errors'],
            'warnings'  => $result['warnings'],
            'skipped'   => $result['skipped'],
        ]));

        return redirect()->route('admin.client-imports.show', $token);
    }

    /**
     * Vista de revisión: todo lo que trae el archivo. Con ?branch_id= muestra además qué pasaría en esa sede.
     */
    public function show(Request $request, string $token)
    {
        $payload = $this->load($token);
        if (!$payload) {
            return redirect()->route('admin.client-imports.create')->with('error', 'Esa vista previa venció o ya se registró. Sube el archivo otra vez.');
        }

        $rows = $payload['rows'];

        // Destino elegido (opcional): sus productos actuales y los códigos de barras que ya tiene
        $branch = $request->filled('branch_id') ? Branch::with('company')->find($request->integer('branch_id')) : null;
        $company = $branch?->company;

        return view('admin.pages.client-imports.preview', [
            'token'          => $token,
            'fileName'       => $payload['file_name'],
            'rows'           => $rows,
            // No se llama "errors": pisaría el $errors de Laravel que usa el modal de alertas
            'importErrors'   => $payload['errors'],
            'importWarnings' => $payload['warnings'],
            'skipped'        => $payload['skipped'],
            'companies'      => Company::withCount('products')->with(['branches' => fn ($q) => $q->orderBy('name')])->orderBy('name')->get(),
            'branch'         => $branch,
            'company'        => $company,
            'existing'       => $company ? $company->products()->count() : 0,
            'conflicts'      => $company ? ClientProductImport::conflicts($rows, $company->id) : [],
            'withStock'      => count(array_filter($rows, fn ($r) => (float) $r['stock'] > 0)),
            'units'          => array_sum(array_column($rows, 'stock')),
            'cost'           => array_sum(array_map(fn ($r) => (float) $r['stock'] * (float) $r['purchase_price'], $rows)),
        ]);
    }

    /**
     * Registra en la compañía y sede elegidas los productos que se vieron en la revisión.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'token'           => ['required', 'regex:/^[A-Za-z0-9]{40}$/'],
            'branch_id'       => ['required', 'integer', 'exists:branches,id'],
            'accept_existing' => ['nullable', 'boolean'],
        ], [
            'token.required'     => 'La vista previa venció. Sube el archivo otra vez.',
            'token.regex'        => 'La vista previa venció. Sube el archivo otra vez.',
            'branch_id.required' => 'Elige la compañía y sede donde se registran los productos.',
            'branch_id.exists'   => 'La sede elegida no existe.',
        ]);

        $payload = $this->load($data['token']);
        if (!$payload) {
            return redirect()->route('admin.client-imports.create')->with('error', 'La vista previa venció o ya se registró. Sube el archivo otra vez.');
        }

        $back = route('admin.client-imports.show', ['token' => $data['token'], 'branch_id' => $data['branch_id']]);

        if ($payload['errors']) {
            return redirect($back)->with('error', 'El archivo tiene errores: corrígelos y súbelo otra vez.');
        }

        $branch  = Branch::with('company')->findOrFail($data['branch_id']);
        $company = $branch->company;

        // Se vuelve a comprobar aquí: entre la revisión y el registro la compañía pudo cambiar
        if (ClientProductImport::conflicts($payload['rows'], $company->id)) {
            return redirect($back)->with('error', "«{$company->name}» ya tiene códigos de barras de este archivo. No se registró nada.");
        }

        // Si la compañía ya tiene productos, el admin tiene que aceptar que estos se agregan como nuevos
        if ($company->products()->count() > 0 && !$request->boolean('accept_existing')) {
            return redirect($back)->with('error', 'Marca la casilla que confirma que estos productos se agregan a los que la compañía ya tiene.');
        }

        try {
            $loaded = DB::transaction(fn () => CompanyClientDataPack::seedRows($company, $branch, $payload['rows']));
        } catch (Throwable $e) {
            report($e);

            return redirect($back)->with('error', 'No se pudo registrar y no se guardó nada: ' . $e->getMessage());
        }

        Storage::disk('local')->delete(self::DIRECTORY . "/{$data['token']}.json");

        return redirect()->route('admin.companies.edit', $company)->with('success',
            'Se registraron ' . number_format($loaded['products']) . ' productos en «' . $company->name . ' — ' . $branch->name . '» ('
            . number_format($loaded['with_stock']) . ' con stock).');
    }

    /** Archivo ya leído que espera en el disco, o null si venció o no existe */
    private function load(string $token): ?array
    {
        $path = self::DIRECTORY . "/{$token}.json";
        $disk = Storage::disk('local');

        return preg_match('/^[A-Za-z0-9]{40}$/', $token) && $disk->exists($path)
            ? json_decode($disk->get($path), true)
            : null;
    }

    /** Borra los archivos de más de un día que nadie registró */
    private function purgeOld(): void
    {
        $disk = Storage::disk('local');
        foreach ($disk->files(self::DIRECTORY) as $file) {
            if ($disk->lastModified($file) < now()->subDay()->getTimestamp()) {
                $disk->delete($file);
            }
        }
    }
}
