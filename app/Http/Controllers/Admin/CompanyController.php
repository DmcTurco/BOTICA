<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CompanyRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Models\DocumentSeries;
use App\Models\DocumentType;
use App\Services\CompanyStarterCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompanyController extends Controller
{
    /**
     * Lista las compañías (boticas clientes) con sus sedes y empleados.
     */
    public function index(Request $request)
    {
        $query = Company::withCount(['branches', 'employees', 'products'])->orderBy('name');

        $query->search($request->buscar, ['name', 'email', 'ruc']);

        $companies = $query->paginate(15)->withQueryString();

        return view('admin.pages.companies.index', compact('companies'));
    }

    /**
     * Formulario para registrar una compañía nueva.
     */
    public function create()
    {
        return view('admin.pages.companies.form');
    }

    /**
     * Registra la compañía lista para trabajar: su usuario de acceso, su primera sede
     * con las series de comprobantes (B001, F001, NV01...), los datos fijos del sistema
     * y el catálogo base (unidades, categorías y laboratorios).
     * Nace GRATIS (sin cobro); el cobro se configura después en Suscripciones.
     * El resto (empleados, productos...) lo registra la propia empresa desde su panel.
     */
    public function store(CompanyRequest $request)
    {
        $company = DB::transaction(function () use ($request) {
            $company = Company::create($request->safe()->only(['name', 'ruc', 'address', 'phone', 'email', 'password']));

            $branch = Branch::create([
                'company_id' => $company->id,
                'name'       => $request->branch_name,
                'address'    => $request->branch_address,
                'status'     => 1,
            ]);

            // Datos fijos del sistema (idempotente) y series de la primera sede
            DocumentType::crearTiposBase();
            DocumentSeries::crearSeriesGlobales();
            DocumentSeries::crearSeriesParaSede($branch);

            // Catálogo base: unidades, categorías y laboratorios comunes
            CompanyStarterCatalog::seed($company);

            return $company;
        });

        return redirect()->route('admin.companies.index')
            ->with('success', "Empresa «{$company->name}» creada (gratis por ahora). Ya puede entrar con {$company->email} en /company/login. Para cobrarle, configúrala en Suscripciones.");
    }

    /**
     * Formulario para editar los datos de acceso y generales de una compañía.
     */
    public function edit(Company $company)
    {
        $company->loadCount(['branches', 'employees', 'products']);

        return view('admin.pages.companies.form', compact('company'));
    }

    /**
     * Actualiza la compañía. La contraseña solo cambia si se escribe una nueva.
     */
    public function update(CompanyRequest $request, Company $company)
    {
        $data = $request->safe()->only(['name', 'ruc', 'address', 'phone', 'email']);

        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }

        $company->update($data);

        return redirect()->route('admin.companies.index')
            ->with('success', "Empresa «{$company->name}» actualizada.");
    }
}
