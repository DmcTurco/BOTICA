<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\EmployeeRequest;
use App\Models\Employee;
use App\Models\Role;
use Illuminate\Http\Request;

class EmployeeManagementController extends Controller
{
    /**
     * Lista los empleados de la sede del branch_admin autenticado.
     */
    public function index()
    {
        $admin = auth()->guard('employee')->user();

        $employees = Employee::with('role')
            ->where('company_id', $admin->company_id)
            ->where('branch_id', $admin->branch_id)
            ->where('id', '!=', $admin->id) // no se lista a sí mismo
            ->orderBy('name')
            ->paginate(15);

        $roles = Role::whereIn('id', [Role::BRANCH_ADMIN, Role::EMPLOYEE])->get();

        return view('employee.pages.employees.index', compact('employees', 'roles'));
    }

    /**
     * Formulario para crear un nuevo empleado.
     */
    public function create()
    {
        $roles            = Role::whereIn('id', [Role::BRANCH_ADMIN, Role::EMPLOYEE])->get();
        $privilegesGroups = Employee::PRIVILEGES_GROUPS;

        return view('employee.pages.employees.form', compact('roles', 'privilegesGroups'));
    }

    /**
     * Guarda un nuevo empleado en la misma sede del branch_admin.
     */
    public function store(EmployeeRequest $request)
    {
        $admin = auth()->guard('employee')->user();

        // Los privilegios solo aplican a empleados regulares (role_id=3)
        $privileges = null;
        if ((int) $request->role_id === Role::EMPLOYEE) {
            $privileges = $request->input('privileges', []);
        }

        Employee::create([
            'company_id' => $admin->company_id,
            'branch_id'  => $admin->branch_id,
            'role_id'    => $request->role_id,
            'name'       => $request->name,
            'email'      => $request->email,
            'password'   => $request->password,
            'privileges' => $privileges,
        ]);

        return redirect()->route('employee.employees.index')
            ->with('success', 'Empleado creado correctamente.');
    }

    /**
     * Formulario para editar un empleado de la misma sede.
     */
    public function edit(Employee $employee)
    {
        $admin = auth()->guard('employee')->user();

        abort_if(
            $employee->branch_id  !== $admin->branch_id ||
            $employee->company_id !== $admin->company_id,
            403
        );

        $roles            = Role::whereIn('id', [Role::BRANCH_ADMIN, Role::EMPLOYEE])->get();
        $privilegesGroups = Employee::PRIVILEGES_GROUPS;

        return view('employee.pages.employees.form', compact('employee', 'roles', 'privilegesGroups'));
    }

    /**
     * Actualiza los datos de un empleado.
     */
    public function update(EmployeeRequest $request, Employee $employee)
    {
        $admin = auth()->guard('employee')->user();

        abort_if(
            $employee->branch_id  !== $admin->branch_id ||
            $employee->company_id !== $admin->company_id,
            403
        );

        $data = [
            'name'    => $request->name,
            'email'   => $request->email,
            'role_id' => $request->role_id,
        ];

        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }

        // Recalcular privilegios según el rol
        if ((int) $request->role_id === Role::EMPLOYEE) {
            $data['privileges'] = $request->input('privileges', []);
        } else {
            // branch_admin no necesita privileges (acceso total)
            $data['privileges'] = null;
        }

        $employee->update($data);

        return redirect()->route('employee.employees.index')
            ->with('success', 'Empleado actualizado correctamente.');
    }

    /**
     * Elimina un empleado de la sede.
     */
    public function destroy(Employee $employee)
    {
        $admin = auth()->guard('employee')->user();

        abort_if(
            $employee->branch_id  !== $admin->branch_id ||
            $employee->company_id !== $admin->company_id,
            403
        );

        // No puede eliminarse a sí mismo
        abort_if($employee->id === $admin->id, 403);

        if ($employee->orders()->exists() || $employee->purchases()->exists()) {
            return back()->with('error', 'No se puede eliminar un empleado con transacciones registradas.');
        }

        $employee->delete();

        return redirect()->route('employee.employees.index')
            ->with('success', 'Empleado eliminado correctamente.');
    }
}
