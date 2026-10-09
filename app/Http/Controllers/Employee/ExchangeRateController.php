<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\ExchangeRateRequest;
use App\Models\ExchangeRate;

class ExchangeRateController extends Controller
{
    /**
     * Historial del tipo de cambio y formulario para registrar el del día.
     */
    public function index()
    {
        $employee = auth()->guard('employee')->user();

        $rates = ExchangeRate::with('employee:id,name')
            ->where('company_id', $employee->company_id)
            ->orderByDesc('rate_date')
            ->paginate(15);

        $today = ExchangeRate::where('company_id', $employee->company_id)->whereDate('rate_date', today())->first();

        return view('employee.pages.exchange-rates.index', compact('rates', 'today'));
    }

    /**
     * Registra el tipo de cambio de una fecha; si ya existía, lo actualiza.
     */
    public function store(ExchangeRateRequest $request)
    {
        $employee = auth()->guard('employee')->user();

        // Se busca por fecha (whereDate) para no depender del formato en que la BD guarda el día
        $rate = ExchangeRate::where('company_id', $employee->company_id)->whereDate('rate_date', $request->rate_date)->first()
            ?? new ExchangeRate(['company_id' => $employee->company_id, 'rate_date' => $request->rate_date]);

        $rate->fill(['buy_rate' => $request->buy_rate, 'sell_rate' => $request->sell_rate, 'employee_id' => $employee->id])->save();

        return redirect()->route('employee.exchange-rates.index')->with('success', 'Tipo de cambio guardado.');
    }
}
