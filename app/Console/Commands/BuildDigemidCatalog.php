<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use RuntimeException;
use XMLReader;
use ZipArchive;

/**
 * Genera el catálogo de medicamentos que se precarga en las compañías nuevas, a partir del Excel
 * "Indicador de Precios de Productos Farmacéuticos" que DIGEMID publica cada mes:
 * https://www.digemid.minsa.gob.pe/webDigemid/reportes/2025/indicador-de-precios-de-productos-farmaceuticos/
 * (descargar el ZIP desde el navegador y pasar el .xlsx que trae).
 *
 * Se quedan los productos que reportan muchas boticas (los más comunes) y se escribe
 * database/data/catalogo_medicamentos.csv, que usa CompanyProductCatalog al crear una compañía.
 */
class BuildDigemidCatalog extends Command
{
    protected $signature = 'catalogo:digemid
                            {archivo : Ruta del Excel MTC de DIGEMID (.xlsx)}
                            {--min=1000 : Mínimo de boticas que reportan el producto para incluirlo}';

    protected $description = 'Genera el catálogo de medicamentos precargable a partir del Excel de precios de DIGEMID';

    /** Destino del catálogo (lo lee CompanyProductCatalog) */
    public const OUTPUT = 'database/data/catalogo_medicamentos.csv';

    /** Columnas del CSV generado */
    public const COLUMNS = ['digemid_code', 'name', 'active_ingredient', 'laboratory', 'country', 'sanitary_registry',
                            'generic', 'unit', 'unit_price', 'pack_amount', 'category', 'reporters'];

    /**
     * Categorías por principio activo (primera que coincida). Las de forma (oftálmico, piel)
     * se resuelven antes. Lo que no coincide va a "Otros medicamentos".
     */
    private const CATEGORY_KEYWORDS = [
        'Antibióticos' => ['amoxicilina', 'ampicilina', 'azitromicina', 'claritromicina', 'eritromicina', 'cefalexina', 'cefadroxilo',
            'cefuroxima', 'ceftriaxona', 'cefixima', 'cefazolina', 'cefaclor', 'ciprofloxacino', 'levofloxacino', 'moxifloxacino',
            'norfloxacino', 'doxiciclina', 'tetraciclina', 'minociclina', 'clindamicina', 'lincomicina', 'gentamicina', 'amikacina',
            'sulfametoxazol', 'trimetoprima', 'nitrofurantoina', 'fosfomicina', 'dicloxacilina', 'oxacilina', 'penicilina', 'linezolid',
            'vancomicina', 'rifampicina', 'metronidazol', 'furazolidona'],
        'Antimicóticos y antivirales' => ['fluconazol', 'itraconazol', 'ketoconazol', 'clotrimazol', 'miconazol', 'terbinafina', 'nistatina',
            'griseofulvina', 'aciclovir', 'valaciclovir', 'oseltamivir'],
        'Sistema nervioso' => ['pregabalina', 'gabapentina', 'clonazepam', 'alprazolam', 'diazepam', 'lorazepam', 'bromazepam',
            'sertralina', 'escitalopram', 'citalopram', 'fluoxetina', 'paroxetina', 'venlafaxina', 'mirtazapina', 'amitriptilina',
            'quetiapina', 'risperidona', 'olanzapina', 'aripiprazol', 'haloperidol', 'levosulpirida', 'sulpirida', 'levetiracetam',
            'lamotrigina', 'carbamazepina', 'topiramato', 'valproico', 'fenitoina', 'metilfenidato', 'memantina', 'citicolina',
            'betahistina', 'ciclobenzaprina', 'zolpidem', 'flunarizina', 'cinarizina'],
        'Cardiovascular y colesterol' => ['atorvastatina', 'rosuvastatina', 'simvastatina', 'pravastatina', 'ezetimiba', 'gemfibrozilo',
            'ciprofibrato', 'fenofibrato', 'clopidogrel', 'rivaroxaban', 'apixaban', 'warfarina', 'cilostazol', 'digoxina', 'amiodarona',
            'isosorbide', 'trimetazidina', 'furosemida'],
        'Hormonales y anticonceptivos' => ['levotiroxina', 'tiamazol', 'progesterona', 'levonorgestrel', 'estradiol', 'etinilestradiol',
            'medroxiprogesterona', 'noretisterona', 'desogestrel', 'drospirenona', 'dienogest', 'cabergolina', 'tibolona'],
        'Urológicos y salud sexual' => ['sildenafilo', 'tadalafilo', 'vardenafilo', 'tamsulosina', 'finasterida', 'dutasterida',
            'fenazopiridina', 'flavoxato', 'oxibutinina', 'solifenacina'],
        'Antidiabéticos' => ['metformina', 'glibenclamida', 'glimepirida', 'gliclazida', 'sitagliptina', 'vildagliptina', 'linagliptina',
            'saxagliptina', 'empagliflozina', 'dapagliflozina', 'pioglitazona', 'insulina'],
        'Antihipertensivos' => ['losartan', 'valsartan', 'irbesartan', 'telmisartan', 'candesartan', 'olmesartan', 'enalapril', 'captopril',
            'lisinopril', 'amlodipino', 'nifedipino', 'atenolol', 'metoprolol', 'bisoprolol', 'carvedilol', 'propranolol', 'nebivolol',
            'hidroclorotiazida', 'clortalidona', 'espironolactona', 'indapamida'],
        'Antiparasitarios' => ['albendazol', 'mebendazol', 'ivermectina', 'nitazoxanida', 'praziquantel', 'secnidazol', 'tinidazol',
            'permetrina', 'pirantel'],
        'Antihistamínicos' => ['loratadina', 'desloratadina', 'cetirizina', 'levocetirizina', 'fexofenadina', 'clorfenamina',
            'clorfeniramina', 'difenhidramina', 'hidroxizina', 'bilastina', 'ebastina', 'rupatadina', 'ketotifeno'],
        'Respiratorios' => ['ambroxol', 'bromhexina', 'acetilcisteina', 'carbocisteina', 'dextrometorfano', 'salbutamol', 'budesonida',
            'beclometasona', 'fluticasona', 'montelukast', 'guaifenesina', 'teofilina', 'ipratropio', 'tiotropio', 'formoterol',
            'salmeterol', 'oximetazolina', 'nafazolina'],
        'Antiácidos y Digestivos' => ['omeprazol', 'esomeprazol', 'pantoprazol', 'lansoprazol', 'rabeprazol', 'ranitidina', 'famotidina',
            'sucralfato', 'aluminio', 'simeticona', 'dimeticona', 'domperidona', 'metoclopramida', 'hioscina', 'loperamida', 'lactulosa',
            'bisacodilo', 'trimebutina', 'mebeverina', 'pinaverio', 'dimenhidrinato', 'ondansetron', 'senosido', 'racecadotrilo',
            'otilonio', 'cinitaprida', 'bismuto', 'picosulfato', 'fosfato de sodio', 'magaldrato', 'pancreatina', 'saccharomyces'],
        'Analgésicos y Antipiréticos' => ['paracetamol', 'metamizol', 'dipirona', 'tramadol', 'codeina', 'acido acetilsalicilico'],
        'Antiinflamatorios' => ['ibuprofeno', 'naproxeno', 'diclofenaco', 'ketorolaco', 'meloxicam', 'celecoxib', 'etoricoxib', 'piroxicam',
            'nimesulida', 'ketoprofeno', 'aceclofenaco', 'indometacina', 'prednisona', 'prednisolona', 'dexametasona', 'betametasona',
            'metilprednisolona', 'deflazacort', 'orfenadrina', 'clonixinato', 'triamcinolona', 'mometasona', 'alopurinol', 'colchicina'],
        'Vitaminas y Suplementos' => ['vitamina', 'acido folico', 'ferroso', 'hierro', 'calcio', 'zinc', 'cianocobalamina', 'tiamina',
            'piridoxina', 'colecalciferol', 'acido ascorbico', 'retinol', 'tocoferol', 'magnesio', 'omega'],
    ];

    public function handle(): int
    {
        $file = $this->argument('archivo');
        $min  = (int) $this->option('min');

        if (!is_file($file)) {
            $this->error("No existe el archivo: {$file}");
            return self::FAILURE;
        }

        $rows    = $this->readSheet($file);
        $header  = null;
        $catalog = [];

        foreach ($rows as $row) {
            // El encabezado está unas filas abajo (antes hay títulos): es la fila con "Nombre Comercial"
            if ($header === null) {
                if (in_array('Nombre Comercial', array_map('trim', $row), true)) {
                    $header = array_flip(array_map('trim', $row));
                }
                continue;
            }

            $get = fn (string $col) => trim((string) ($row[$header[$col] ?? -1] ?? ''));

            if ($get('Código de Producto') === '' || (int) $get('Cantidad de establecimientos reportantes por producto') < $min) {
                continue;
            }

            $catalog[] = $this->product($get);
        }

        if ($header === null) {
            $this->error('No se encontró el encabezado del Excel (columna "Nombre Comercial"). ¿Es el archivo MTC de DIGEMID?');
            return self::FAILURE;
        }

        // Los más comunes primero
        usort($catalog, fn ($a, $b) => $b['reporters'] <=> $a['reporters'] ?: strcmp($a['name'], $b['name']));

        $out = fopen(base_path(self::OUTPUT), 'w');
        fputcsv($out, self::COLUMNS);
        foreach ($catalog as $item) {
            fputcsv($out, array_map(fn ($c) => $item[$c], self::COLUMNS));
        }
        fclose($out);

        $byCategory = array_count_values(array_column($catalog, 'category'));
        arsort($byCategory);
        $this->info(count($catalog) . " medicamentos (reportados por {$min} boticas o más) → " . self::OUTPUT);
        $this->table(['Categoría', 'Productos'], collect($byCategory)->map(fn ($n, $c) => [$c, $n])->values()->all());

        return self::SUCCESS;
    }

    /** Arma la fila del catálogo a partir de una fila del Excel */
    private function product(callable $get): array
    {
        $form      = $this->upper($get('Forma Farmacéutica'));
        $fractions = max(1, (int) round((float) $get('Fracciones')));
        $unitPrice = round((float) $get('mediana'), 2);   // precio de venta al público por unidad (ficha técnica DIGEMID)
        $ifa       = $get('IFA');

        $name = trim(implode(' ', array_filter([
            $this->title(str_replace(['®', '™', '©'], '', $get('Nombre Comercial'))),
            mb_strtolower($this->concentration($get('Concentración'))),
            $this->title($this->shortForm($form)),
        ])));

        return [
            'digemid_code'      => $get('Código de Producto'),
            'name'              => mb_substr($name, 0, 150),
            'active_ingredient' => mb_substr($this->title($ifa), 0, 100),
            'laboratory'        => $this->laboratory($get('Titular de Registro Sanitario')),
            'country'           => $this->title($get('País')),
            'sanitary_registry' => mb_substr($get('Registro Sanitario'), 0, 30),
            'generic'           => $this->upper($get('Clasificación')) === 'GENERICO' ? 1 : 0,
            'unit'              => $this->baseUnit($form),
            'unit_price'        => number_format($unitPrice, 2, '.', ''),
            'pack_amount'       => $fractions > 1 ? $fractions : '',
            'category'          => $this->category($form, $ifa),
            'reporters'         => (int) $get('Cantidad de establecimientos reportantes por producto'),
        ];
    }

    /** Unidad base (la más pequeña que se vende) según la forma farmacéutica */
    private function baseUnit(string $form): string
    {
        return match (true) {
            str_contains($form, 'TABLETA'), str_contains($form, 'COMPRIMIDO'), str_contains($form, 'GRAGEA') => 'Tableta',
            str_contains($form, 'CÁPSULA'), str_contains($form, 'CAPSULA') => 'Cápsula',
            str_contains($form, 'INYECTABLE'), str_contains($form, 'PERFUSIÓN'), str_contains($form, 'PERFUSION') => 'Ampolla',
            str_contains($form, 'CREMA'), str_contains($form, 'UNGÜENTO'), str_contains($form, 'GEL'), str_contains($form, 'POMADA') => 'Tubo',
            str_contains($form, 'SOBRE'), str_contains($form, 'GRANULADO'), str_contains($form, 'POLVO PARA SOLUCIÓN ORAL') => 'Sobre',
            str_contains($form, 'JARABE'), str_contains($form, 'SOLUCIÓN'), str_contains($form, 'SUSPENSIÓN'), str_contains($form, 'GOTAS'),
            str_contains($form, 'EMULSIÓN'), str_contains($form, 'ELIXIR'), str_contains($form, 'LOCIÓN'), str_contains($form, 'AEROSOL'),
            str_contains($form, 'SPRAY'), str_contains($form, 'POLVO') => 'Frasco',
            default => 'Unidad',
        };
    }

    /** Categoría: primero por forma (ojos, piel) y luego por principio activo */
    private function category(string $form, string $ifa): string
    {
        if (str_contains($form, 'OFTÁLMIC') || str_contains($form, 'OFTALMIC')) {
            return 'Oftalmológicos';
        }

        $ingredient = $this->plain($ifa);

        foreach (self::CATEGORY_KEYWORDS as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($ingredient, $keyword)) {
                    return $category;
                }
            }
        }

        foreach (['CREMA', 'UNGÜENTO', 'GEL', 'POMADA', 'LOCIÓN', 'CHAMPÚ'] as $skinForm) {
            if (str_contains($form, $skinForm)) {
                return 'Dermatológicos';
            }
        }

        return 'Otros medicamentos';
    }

    /** "MEDIFARMA S.A." → "Medifarma" (sin forma societaria, para coincidir con el catálogo base) */
    private function laboratory(string $holder): string
    {
        $holder = preg_replace('/\s+SOCIEDAD\s+AN[OÓ]NIMA(\s+CERRADA|\s+ABIERTA)?\s*$/iu', '', trim($holder));
        $name = preg_replace('/[\s,]+(S\.?\s?A\.?\s?C\.?|S\.?\s?A\.?\s?A\.?|S\.?\s?R\.?\s?L\.?|E\.?\s?I\.?\s?R\.?\s?L\.?|S\.?\s?A\.?|LTDA\.?|LTD\.?|INC\.?|GMBH|S\.?\s?L\.?|C\.?\s?A\.?)\s*$/iu', '', trim($holder));
        $name = preg_replace('/\s*\((PERU|PERÚ)\)\s*/iu', ' ', $name);

        return mb_substr($this->title(trim($name, " .,-")), 0, 100);
    }

    /** Forma corta para el nombre: "TABLETA RECUBIERTA" → "TABLETA" */
    private function shortForm(string $form): string
    {
        foreach (['TABLETA', 'COMPRIMIDO', 'CÁPSULA', 'JARABE', 'CREMA', 'UNGÜENTO', 'GEL', 'ÓVULO', 'SUPOSITORIO', 'GOTAS'] as $short) {
            if (str_contains($form, $short)) {
                return $short;
            }
        }

        return $form;
    }

    /** Concentración legible: 0.2 (número suelto de Excel) se deja tal cual; "500 MG" → "500 mg" */
    private function concentration(string $value): string
    {
        return is_numeric($value) ? '' : $value;
    }

    private function title(string $text): string
    {
        return mb_convert_case(mb_strtolower(trim(preg_replace('/\s+/', ' ', $text))), MB_CASE_TITLE, 'UTF-8');
    }

    private function upper(string $text): string
    {
        return mb_strtoupper(trim($text), 'UTF-8');
    }

    /** Minúsculas sin tildes, para comparar principios activos */
    private function plain(string $text): string
    {
        return strtr(mb_strtolower($text, 'UTF-8'), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
    }

    /**
     * Lee la primera hoja de un .xlsx con ZipArchive + XMLReader (sin librerías externas).
     * Devuelve las filas como arreglos indexados por número de columna (A=0, B=1...).
     */
    private function readSheet(string $file): \Generator
    {
        $zip = new ZipArchive();
        if ($zip->open($file) !== true) {
            throw new RuntimeException('No se pudo abrir el Excel.');
        }

        // Textos compartidos (las celdas de texto guardan solo su índice)
        $shared = [];
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml !== false) {
            $reader = new XMLReader();
            $reader->XML($xml);
            while ($reader->read()) {
                if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'si') {
                    $node = simplexml_load_string($reader->readOuterXml());
                    $shared[] = $node->t !== null && (string) $node->t !== '' ? (string) $node->t : implode('', array_map('strval', $node->xpath('//*[local-name()="t"]')));
                }
            }
        }

        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        $reader = new XMLReader();
        $reader->XML($sheet);
        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->name !== 'row') {
                continue;
            }
            $row = [];
            foreach (simplexml_load_string($reader->readOuterXml())->c as $cell) {
                $col = 0;
                foreach (str_split(preg_replace('/\d+/', '', (string) $cell['r'])) as $letter) {
                    $col = $col * 26 + (ord($letter) - 64);
                }
                $value = (string) $cell->v;
                if ((string) $cell['t'] === 's') {
                    $value = $shared[(int) $value] ?? '';
                } elseif ((string) $cell['t'] === 'inlineStr') {
                    $value = (string) $cell->is->t;
                }
                $row[$col - 1] = $value;
            }
            yield $row;
        }
    }
}
