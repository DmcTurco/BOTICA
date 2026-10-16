<?php

namespace App\Services;

use App\Models\Company;

/**
 * Catálogo base con el que nace toda compañía: unidades de medida, categorías y laboratorios comunes.
 * Cada compañía recibe su propia copia, así que puede editarla o borrarla sin afectar a las demás.
 * Lo usan el alta de compañías (Admin\CompanyController) y el DemoSeeder.
 */
class CompanyStarterCatalog
{
    /** Unidades: [nombre, abreviatura, código SUNAT del catálogo 03] */
    private const UNITS = [
        ['Unidad',    'UND', 'NIU'],
        ['Tableta',   'TAB', 'NIU'],
        ['Cápsula',   'CAP', 'NIU'],
        ['Blíster',   'BLI', 'NIU'],
        ['Caja',      'CJA', 'BX'],
        ['Frasco',    'FRC', 'BO'],
        ['Ampolla',   'AMP', 'NIU'],
        ['Sobre',     'SOB', 'NIU'],
        ['Tira',      'TIR', 'NIU'],
        ['Tubo',      'TUB', 'TU'],
        ['Mililitro', 'ML',  'MLT'],
        ['Gramo',     'GR',  'GRM'],
    ];

    /** Categorías: [nombre, descripción, icono Font Awesome] */
    private const CATEGORIES = [
        ['Analgésicos y Antipiréticos', 'Medicamentos para el dolor y la fiebre',            'fa-pills'],
        ['Antibióticos',                'Tratamiento de infecciones bacterianas',            'fa-bacterium'],
        ['Antiinflamatorios',           'Reducción de inflamación y dolor articular',        'fa-fire-flame-simple'],
        ['Antihipertensivos',           'Control de la presión arterial',                    'fa-heart-pulse'],
        ['Vitaminas y Suplementos',     'Vitaminas, minerales y suplementos nutricionales',  'fa-leaf'],
        ['Antiácidos y Digestivos',     'Tratamiento de problemas gastrointestinales',       'fa-shield-halved'],
        ['Antidiabéticos',              'Control de la glucosa en sangre',                   'fa-syringe'],
        ['Dermatológicos',              'Cremas, pomadas y tratamientos para la piel',       'fa-hand-dots'],
        ['Respiratorios',               'Tratamiento de tos, asma y vías respiratorias',     'fa-lungs'],
        ['Antiparasitarios',            'Tratamiento de parásitos intestinales y externos',  'fa-bug'],
        ['Antihistamínicos',            'Tratamiento de alergias y reacciones alérgicas',    'fa-eye'],
        ['Oftalmológicos',              'Gotas, colirios y medicamentos para los ojos',      'fa-eye-dropper'],
    ];

    /** Laboratorios: [nombre, país] (sin contacto: cada botica completa el suyo) */
    private const LABORATORIES = [
        ['Bayer',           'Alemania'],
        ['Pfizer',          'EE.UU.'],
        ['Roche',           'Suiza'],
        ['Abbott',          'EE.UU.'],
        ['Farmex',          'Perú'],
        ['Medifarma',       'Perú'],
        ['Roemmers',        'Argentina'],
        ['Bagó',            'Argentina'],
        ['GlaxoSmithKline', 'Reino Unido'],
        ['Novartis',        'Suiza'],
    ];

    /**
     * Carga el catálogo base en la compañía. No duplica: omite lo que ya tenga con el mismo nombre.
     */
    public static function seed(Company $company): void
    {
        foreach (self::UNITS as [$name, $abbreviation, $sunatCode]) {
            $company->units()->firstOrCreate(
                ['name' => $name],
                ['abbreviation' => $abbreviation, 'sunat_code' => $sunatCode, 'status' => 1],
            );
        }

        foreach (self::CATEGORIES as [$name, $description, $icon]) {
            $company->categories()->firstOrCreate(
                ['name' => $name],
                ['description' => $description, 'icon' => $icon, 'status' => 1],
            );
        }

        foreach (self::LABORATORIES as [$name, $country]) {
            $company->laboratories()->firstOrCreate(
                ['name' => $name],
                ['country' => $country, 'status' => 1],
            );
        }
    }
}
