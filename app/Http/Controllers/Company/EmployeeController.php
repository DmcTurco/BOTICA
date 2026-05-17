<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    /**
     * Lista todos los empleados de la compañía.
     */
    public function index(Request $request)
    {
        $company = auth()->guard('company')->user();

        $query = Employee::with(['branch', 'role'])
            ->where('company_id', $company->id)
            ->orderBy('name');

        if ($request->filled('sede')) {
            $query->where('branch_id', $request->sede);
        }

        if ($request->filled('rol')) {
            $query->where('role_id', $request->rol);
        }

        if ($request->filled('buscar')) {
            $search = $request->buscar;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $employees = $query->paginate(15)->withQueryString();

        $branches = Branch::where('company_id', $company->id)
            ->orderBy('name')->get();

        // Solo branch_admin y employee son asignables (company_admin es la cuenta raíz)
        $roles = Role::whereIn('id', [Role::BRANCH_ADMIN, Role::EMPLOYEE])->get();

        return view('company.pages.employees.index', compact('employees', 'branches', 'roles'));
    }

    /**
     * Muestra el formulario para crear un empleado.
     */
    public function create()
    {
        $company = auth()->guard('company')->user();

        $branches = Branch::where('company_id', $company->id)
            ->activas()->orderBy('name')->get();

        $roles = Role::whereIn('id', [Role::BRANCH_ADMIN, Role::EMPLOYEE])->get();

        return view('company.pages.employees.form', compact('branches', 'roles'));
    }

    /**
     * Guarda un nuevo empleado.
     */
    public function store(Request $request)
    {
        $company = auth()->guard('company')->user();

        $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|max:255|unique:employees,email',
            'password'  => 'required|string|min:8|confirmed',
            'branch_id' => ['required', Rule::exists('branches', 'id')->where('company_id', $company->id)],
            'role_id'   => ['required', Rule::in([Role::BRANCH_ADMIN, Role::EMPLOYEE])],
        ]);

        Employee::create([
            'company_id' => $company->id,
            'branch_id'  => $request->branch_id,
            'role_id'    => $request->role_id,
            'name'       => $request->name,
            'email'      => $request->email,
            'password'   => $request->password,
        ]);

        return redirect()->route('company.employees.index')
            ->with('success', 'Empleado creado correctamente.');
    }

    /**
     * Muestra el formulario para editar un empleado.
     */
    public function edit(Employee $employee)
    {
        $company = auth()->guard('company')->user();
        abort_if($employee->company_id !== $company->id, 403);

        $branches = Branch::where('company_id', $company->id)
            ->activas()->orderBy('name')->get();

        $roles = Role::whereIn('id', [Role::BRANCH_ADMIN, Role::EMPLOYEE])->get();

        return view('company.pages.employees.form', compact('employee', 'branches', 'roles'));
    }

    /**
     * Actualiza un empleado existente.
     */
    public function update(Request $request, Employee $employee)
    {
        $company = auth()->guard('company')->user();
        abort_if($employee->company_id !== $company->id, 403);

        $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => ['required', 'email', 'max:255', Rule::unique('employees', 'email')->ignore($employee->id)],
            'password'  => 'nullable|string|min:8|confirmed',
            'branch_id' => ['required', Rule::exists('branches', 'id')->where('company_id', $company->id)],
            'role_id'   => ['required', Rule::in([Role::BRANCH_ADMIN, Role::EMPLOYEE])],
        ]);

        $data = [
            'name'      => $request->name,
            'email'     => $request->email,
            'branch_id' => $request->branch_id,
            'role_id'   => $request->role_id,
        ];

        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }

        $employee->update($data);

        return redirect()->route('company.employees.index')
            ->with('success', 'Empleado actualizado correctamente.');
    }

    /**
     * Elimina un empleado.
     */
    public function destroy(Employee $employee)
    {
        $company = auth()->guard('company')->user();
        abort_if($employee->company_id !== $company->id, 403);

        if ($employee->orders()->exists() || $employee->purchases()->exists()) {
            return back()->with('error', 'No se puede eliminar un empleado con transacciones registradas.');
        }

        $employee->delete();

        return redirect()->route('company.employees.index')
            ->with('success', 'Empleado eliminado correctamente.');
    }
}
