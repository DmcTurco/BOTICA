<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Series de notas de crédito por sede: la de boleta empieza con B y la de factura con F (regla de SUNAT) */
    private const SERIES = [
        'NOTA_CREDITO_BOLETA'  => ['prefix' => 'BC', 'width' => 2, 'name' => 'Nota de Crédito (Boleta)'],
        'NOTA_CREDITO_FACTURA' => ['prefix' => 'FC', 'width' => 2, 'name' => 'Nota de Crédito (Factura)'],
    ];

    /**
     * Notas de crédito electrónicas (anulación total de una boleta o factura ya aceptada por SUNAT).
     * Cada nota referencia la venta original; sus importes y detalle se toman de ella.
     */
    public function up(): void
    {
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('branch_id')->index();
            $table->unsignedBigInteger('order_id')->unique();       // venta que se anula (una sola nota total por venta)
            $table->unsignedBigInteger('employee_id')->index();     // quien emitió la nota
            $table->string('voucher_number', 30);                   // BC01-00000001 / FC01-00000001
            $table->string('reason_code', 2);                       // catálogo SUNAT 09: 01, 02, 06
            $table->string('reason_text', 250);

            // Importes (copia de la venta anulada)
            $table->decimal('taxable_amount', 12, 2)->default(0);
            $table->decimal('exonerated_amount', 12, 2)->default(0);
            $table->decimal('unaffected_amount', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('igv', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            // Estado del envío a SUNAT (igual que en orders)
            $table->string('sunat_status', 15)->default('pending')->index();
            $table->string('sunat_code', 10)->nullable();
            $table->text('sunat_message')->nullable();
            $table->string('sunat_hash', 64)->nullable();
            $table->string('sunat_xml_path')->nullable();
            $table->string('sunat_cdr_path')->nullable();
            $table->string('sunat_environment', 10)->nullable();
            $table->unsignedSmallInteger('sunat_attempts')->default(0);
            $table->timestamp('sunat_sent_at')->nullable();

            $table->timestamps();

            $table->unique(['company_id', 'voucher_number']);
        });

        // Series de notas de crédito para las sedes existentes
        $now = now();

        foreach (self::SERIES as $type => $format) {
            foreach (DB::table('branches')->orderBy('company_id')->orderBy('id')->get() as $branch) {
                $max = 0;
                foreach (DB::table('document_series')->where('company_id', $branch->company_id)->where('type_code', $type)->pluck('series') as $series) {
                    if (preg_match('/^' . $format['prefix'] . '(\d+)$/', $series, $m)) {
                        $max = max($max, (int) $m[1]);
                    }
                }

                DB::table('document_series')->insert([
                    'company_id'     => $branch->company_id,
                    'branch_id'      => $branch->id,
                    'type_code'      => $type,
                    'name'           => $format['name'],
                    'series'         => $format['prefix'] . str_pad($max + 1, $format['width'], '0', STR_PAD_LEFT),
                    'current_number' => 0,
                    'digits'         => 8,
                    'active'         => true,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('document_series')->whereIn('type_code', array_keys(self::SERIES))->delete();

        Schema::dropIfExists('credit_notes');
    }
};
