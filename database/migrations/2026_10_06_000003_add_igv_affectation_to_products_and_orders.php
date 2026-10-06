<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Código SUNAT de unidad de medida (catálogo 03) por abreviatura; el resto usa NIU */
    private const UNIT_CODES = [
        'CJA' => 'BX',   // caja
        'FRC' => 'BO',   // frasco (botella)
        'TUB' => 'TU',   // tubo
        'ML'  => 'MLT',  // mililitro
        'GR'  => 'GRM',  // gramo
    ];

    /**
     * Afectación del IGV por producto y desglose de la venta (catálogo SUNAT 07):
     *   10 = gravado (IGV 18%) · 20 = exonerado · 30 = inafecto
     *  - products: igv_affectation (reemplaza al booleano taxed_product, que se mantiene sincronizado).
     *  - units: código SUNAT de la unidad de medida.
     *  - order_detail: afectación, IGV y unidad de cada ítem vendido.
     *  - orders: importes gravado, exonerado e inafecto.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('igv_affectation', 2)->default('20')->after('taxed_product'); // 10 gravado, 20 exonerado, 30 inafecto
        });
        DB::table('products')->where('taxed_product', true)->update(['igv_affectation' => '10']);

        Schema::table('units', function (Blueprint $table) {
            $table->string('sunat_code', 3)->default('NIU')->after('abbreviation');
        });
        foreach (self::UNIT_CODES as $abbreviation => $code) {
            DB::table('units')->where('abbreviation', $abbreviation)->update(['sunat_code' => $code]);
        }

        Schema::table('order_detail', function (Blueprint $table) {
            $table->string('igv_affectation', 2)->default('20')->after('subtotal');
            $table->decimal('igv_amount', 12, 2)->default(0)->after('igv_affectation');
            $table->string('unit_code', 3)->default('NIU')->after('igv_amount');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('taxable_amount', 12, 2)->default(0)->after('subtotal');     // operaciones gravadas
            $table->decimal('exonerated_amount', 12, 2)->default(0)->after('taxable_amount');
            $table->decimal('unaffected_amount', 12, 2)->default(0)->after('exonerated_amount');
        });

        // Las ventas anteriores aplicaban 18% a todo: se registran como gravadas
        DB::table('order_detail')->update([
            'igv_affectation' => '10',
            'igv_amount'      => DB::raw('ROUND(subtotal * 0.18, 2)'),
        ]);
        DB::table('orders')->update(['taxable_amount' => DB::raw('subtotal')]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['taxable_amount', 'exonerated_amount', 'unaffected_amount']);
        });

        Schema::table('order_detail', function (Blueprint $table) {
            $table->dropColumn(['igv_affectation', 'igv_amount', 'unit_code']);
        });

        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn('sunat_code');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('igv_affectation');
        });
    }
};
