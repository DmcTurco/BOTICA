<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Tipos de comprobante cuya serie y correlativo son propios de cada sede */
    private const PER_BRANCH = [
        'BOLETA'     => ['prefix' => 'B',  'width' => 3],
        'FACTURA'    => ['prefix' => 'F',  'width' => 3],
        'NOTA_VENTA' => ['prefix' => 'NV', 'width' => 2],
    ];

    /**
     * Series por sede (SUNAT exige series por RUC y por establecimiento):
     *  - document_series: company_id y branch_id (null = correlativo global interno).
     *  - branches: código de establecimiento SUNAT (0000 = domicilio fiscal).
     *  - orders: el número de comprobante pasa a ser único por compañía (cada RUC numera aparte).
     */
    public function up(): void
    {
        Schema::table('document_series', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->index()->after('id');
            $table->unsignedBigInteger('branch_id')->nullable()->index()->after('company_id');

            $table->dropUnique(['type_code', 'series']);
            $table->unique(['company_id', 'type_code', 'series']);
        });

        // NULL no cuenta como igual en un índice único: los correlativos globales
        // (company_id NULL) conservan su unicidad con un índice parcial.
        DB::statement('CREATE UNIQUE INDEX document_series_global_unique ON document_series (type_code, series) WHERE company_id IS NULL');

        Schema::table('branches', function (Blueprint $table) {
            $table->string('sunat_establishment_code', 4)->default('0000')->after('email'); // código de establecimiento anexo
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['voucher_number']);
            $table->unique(['company_id', 'voucher_number']);
        });

        $this->assignExistingSeries();
    }

    /**
     * Reparte las series globales ya existentes entre las sedes:
     * la primera sede de la primera compañía hereda la serie actual (con su correlativo)
     * y las demás sedes reciben series nuevas (B002, F002, NV02...).
     */
    private function assignExistingSeries(): void
    {
        $now = now();

        foreach (self::PER_BRANCH as $type => $format) {
            $global = DB::table('document_series')->whereNull('company_id')->where('type_code', $type)->first();

            foreach (DB::table('branches')->orderBy('company_id')->orderBy('id')->get() as $branch) {
                // Hereda la serie global (una sola vez)
                if ($global) {
                    DB::table('document_series')->where('id', $global->id)->update([
                        'company_id' => $branch->company_id,
                        'branch_id'  => $branch->id,
                    ]);
                    $global = null;
                    continue;
                }

                $reference = DB::table('document_series')->where('type_code', $type)->first();
                $used      = DB::table('document_series')
                    ->where('company_id', $branch->company_id)
                    ->where('type_code', $type)
                    ->pluck('series');

                $max = 0;
                foreach ($used as $series) {
                    if (preg_match('/^' . $format['prefix'] . '(\d+)$/', $series, $m)) {
                        $max = max($max, (int) $m[1]);
                    }
                }

                DB::table('document_series')->insert([
                    'company_id'     => $branch->company_id,
                    'branch_id'      => $branch->id,
                    'type_code'      => $type,
                    'name'           => $reference->name ?? $type,
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
        // Vuelve a series globales: se conserva una fila por (tipo, serie) y se borran las repetidas
        $keep = [];
        foreach (DB::table('document_series')->whereNotNull('company_id')->orderBy('id')->get() as $row) {
            $key = $row->type_code . '|' . $row->series;

            if (isset($keep[$key])) {
                DB::table('document_series')->where('id', $row->id)->delete();
            } else {
                $keep[$key] = true;
            }
        }

        DB::statement('DROP INDEX IF EXISTS document_series_global_unique');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'voucher_number']);
            $table->unique('voucher_number');
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('sunat_establishment_code');
        });

        Schema::table('document_series', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'type_code', 'series']);
            $table->dropIndex(['company_id']);
            $table->dropIndex(['branch_id']);
            $table->dropColumn(['company_id', 'branch_id']);
            $table->unique(['type_code', 'series']);
        });
    }
};
