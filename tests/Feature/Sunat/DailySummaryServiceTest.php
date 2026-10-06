<?php

use App\Models\Company;
use App\Models\CompanySunatSetting;
use App\Models\Order;
use App\Models\SunatSummary;
use App\Services\Sunat\Contracts\SunatGateway;
use App\Services\Sunat\DailySummaryService;
use App\Services\Sunat\SunatResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * Gateway falso: devuelve los resultados que le indica cada prueba, sin llamar a SUNAT.
 */
function fakeGateway(SunatResult $send, SunatResult $status): SunatGateway
{
    return new class($send, $status) implements SunatGateway {
        public function __construct(private SunatResult $send, private SunatResult $status) {}
        public function send(App\Models\Order $order): SunatResult { return $this->send; }
        public function sendCreditNote(App\Models\CreditNote $note): SunatResult { return $this->send; }
        public function sendSummary(SunatSummary $summary, Collection $orders): SunatResult { return $this->send; }
        public function digestOrders(Collection $orders): array { return $orders->mapWithKeys(fn ($o) => [$o->id => 'HASH' . $o->id])->all(); }
        public function summaryStatus(SunatSummary $summary): SunatResult { return $this->status; }
    };
}

function boletaSetup(string $mode = 'summary'): int
{
    static $n = 0;
    $n++;

    $company = Company::create([
        'name' => "Botica {$n}", 'ruc' => '20000000001', 'email' => "botica{$n}@example.com", 'password' => 'secret',
    ]);

    // Se completan los datos fiscales para que la configuración cuente como lista (beta)
    CompanySunatSetting::create([
        'company_id'     => $company->id,
        'legal_name'     => 'BOTICA DE PRUEBA',
        'fiscal_address' => 'AV. PRUEBA 123',
        'ubigeo'         => '150101',
        'environment'    => 'beta',
        'boleta_mode'    => $mode,
        'enabled'        => true,
    ]);

    return $company->id;
}

function makeBoleta(int $companyId, string $number): Order
{
    return Order::create([
        'company_id' => $companyId, 'branch_id' => 1, 'employee_id' => 1,
        'voucher_type' => 1, 'voucher_number' => $number, 'payment_type' => 1, 'status' => 1,
        'sunat_status' => Order::SUNAT_PENDING,
        'subtotal' => 10, 'taxable_amount' => 10, 'igv' => 1.8, 'total' => 11.8,
    ]);
}

beforeEach(fn () => Storage::fake('local'));

it('agrupa las boletas pendientes en un solo resumen y las marca aceptadas', function () {
    $companyId = boletaSetup();
    makeBoleta($companyId, 'B001-00000001');
    makeBoleta($companyId, 'B001-00000002');

    $service = new DailySummaryService(fakeGateway(
        new SunatResult(Order::SUNAT_PENDING, null, 'recibido', null, '<xml/>', null, 'TICKET1'),
        new SunatResult(Order::SUNAT_ACCEPTED, '0', 'aceptado', null, null, 'zip'),
    ));

    $summaries = $service->sendPending($companyId);

    expect($summaries)->toHaveCount(1)
        ->and($summaries->first()->documents_count)->toBe(2)
        ->and($summaries->first()->status)->toBe(SunatSummary::STATUS_ACCEPTED)
        ->and(Order::where('sunat_status', Order::SUNAT_ACCEPTED)->count())->toBe(2)
        ->and(Order::whereNotNull('sunat_hash')->count())->toBe(2);
});

it('deja el resumen en proceso mientras SUNAT no responde', function () {
    $companyId = boletaSetup();
    makeBoleta($companyId, 'B001-00000001');

    $service = new DailySummaryService(fakeGateway(
        new SunatResult(Order::SUNAT_PENDING, null, 'recibido', null, '<xml/>', null, 'TICKET1'),
        new SunatResult(Order::SUNAT_PENDING, '98', 'aún procesando'),
    ));

    $summary = $service->sendPending($companyId)->first();

    expect($summary->status)->toBe(SunatSummary::STATUS_PROCESSING)
        ->and($summary->ticket)->toBe('TICKET1')
        ->and(Order::first()->sunat_status)->toBe(Order::SUNAT_PENDING);
});

it('libera las boletas si falla el envío para incluirlas en el próximo resumen', function () {
    $companyId = boletaSetup();
    makeBoleta($companyId, 'B001-00000001');

    $service = new DailySummaryService(fakeGateway(
        new SunatResult(Order::SUNAT_ERROR, 'HTTP', 'sin conexión'),
        new SunatResult(Order::SUNAT_ERROR, 'HTTP', 'sin conexión'),
    ));

    $summary = $service->sendPending($companyId)->first();

    expect($summary->status)->toBe(SunatSummary::STATUS_ERROR)
        ->and(Order::first()->sunat_summary_id)->toBeNull()
        ->and(Order::first()->sunat_status)->toBe(Order::SUNAT_PENDING);
});

it('marca las boletas como rechazadas si SUNAT rechaza el resumen', function () {
    $companyId = boletaSetup();
    makeBoleta($companyId, 'B001-00000001');

    $service = new DailySummaryService(fakeGateway(
        new SunatResult(Order::SUNAT_REJECTED, '2072', 'rechazado'),
        new SunatResult(Order::SUNAT_REJECTED, '2072', 'rechazado'),
    ));

    $service->sendPending($companyId);

    expect(Order::first()->sunat_status)->toBe(Order::SUNAT_REJECTED);
});

it('no envía nada si la compañía usa el modo individual', function () {
    $companyId = boletaSetup('individual');
    makeBoleta($companyId, 'B001-00000001');

    $service = new DailySummaryService(fakeGateway(
        new SunatResult(Order::SUNAT_PENDING, null, null, null, null, null, 'T'),
        new SunatResult(Order::SUNAT_ACCEPTED, '0', 'ok'),
    ));

    expect($service->sendPending($companyId))->toBeEmpty()
        ->and(SunatSummary::count())->toBe(0);
});
