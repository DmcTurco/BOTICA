<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Employee extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // ── Constantes de privilegios (solo aplican a role_id = 3) ──

    // Ventas
    const PRIV_VER_VENTAS               = 'ver_ventas';
    const PRIV_VER_HISTORIAL            = 'ver_historial';
    const PRIV_ANULAR_VENTA             = 'anular_venta';
    const PRIV_APLICAR_DESCUENTO        = 'aplicar_descuento';
    const PRIV_TIPO_CAMBIO              = 'tipo_cambio';
    const PRIV_ADMIN_CORRELATIVOS       = 'admin_correlativos';
    const PRIV_PRODUCTOS_SIN_STOCK_VENTA= 'productos_sin_stock_venta';
    const PRIV_VENTA_PERDIDA_SIN_STOCK  = 'venta_perdida_sin_stock';
    const PRIV_OTROS_INGRESOS           = 'otros_ingresos';
    const PRIV_GASTOS_DIA               = 'gastos_dia';
    const PRIV_VER_CIERRES_CAJA         = 'ver_cierres_caja';

    // Caja
    const PRIV_ABRIR_CAJA           = 'abrir_caja';
    const PRIV_CERRAR_CAJA          = 'cerrar_caja';
    const PRIV_EDITAR_APERTURA      = 'editar_apertura';
    const PRIV_VER_MOVIMIENTOS_CAJA = 'ver_movimientos_caja';

    // Catálogo
    const PRIV_VER_INVENTARIO         = 'ver_inventario';
    const PRIV_GESTIONAR_CLIENTES     = 'gestionar_clientes';
    const PRIV_CREAR_PRODUCTOS        = 'crear_productos';
    const PRIV_MANTENIMIENTO_FAMILIAS = 'mantenimiento_familias';
    const PRIV_PRODUCTOS_SIN_ROTACION = 'productos_sin_rotacion';
    const PRIV_PRODUCTOS_REPOSICION   = 'productos_reposicion';

    // Inventario / Compras
    const PRIV_VER_COMPRAS              = 'ver_compras';
    const PRIV_VER_KARDEX               = 'ver_kardex';
    const PRIV_AJUSTE_INVENTARIO        = 'ajuste_inventario';
    const PRIV_TRASPASO_SALIDA          = 'traspaso_salida';
    const PRIV_INVENTARIO_TOTAL_STOCK   = 'inventario_total_stock';
    const PRIV_INVENTARIO_TOTAL         = 'inventario_total';
    const PRIV_INV_LAB_STOCK            = 'inv_por_laboratorio_stock';
    const PRIV_INV_LAB_TOTAL            = 'inv_por_laboratorio_total';
    const PRIV_REPORTE_INV_VALORIZADO   = 'reporte_inv_valorizado';

    // Reportes
    const PRIV_RECORD_MENSUAL_VENTAS = 'record_mensual_ventas';
    const PRIV_RECORD_GENERAL_VENTAS = 'record_general_ventas';
    const PRIV_VER_DOCS_MES          = 'ver_docs_mes';
    const PRIV_REPORTES_POR_FECHA    = 'reportes_por_fecha';
    const PRIV_REPORTE_COMISIONES    = 'reporte_comisiones';
    const PRIV_REPORTE_MAS_VENDIDOS  = 'reporte_mas_vendidos';
    const PRIV_REPORTE_UTILIDAD      = 'reporte_utilidad';
    const PRIV_IMPRIMIR_MOV_CAJA     = 'imprimir_mov_caja';

    // Seguridad
    const PRIV_ADMIN_USUARIOS       = 'admin_usuarios';
    const PRIV_ACTUALIZACION_PRECIO = 'actualizacion_precio';
    const PRIV_ADMIN_COMISION       = 'admin_comision';
    const PRIV_CAMBIAR_NOTA_VENTA   = 'cambiar_nota_venta';
    const PRIV_ELIMINAR_GUIA_ING    = 'eliminar_guia_ingreso';
    const PRIV_ELIMINAR_GUIA_SAL    = 'eliminar_guia_salida';
    const PRIV_ASIGNAR_PRIVILEGIOS  = 'asignar_privilegios';

    // SUNAT
    const PRIV_ENVIAR_FE_SUNAT         = 'enviar_fe_sunat';
    const PRIV_ENVIAR_RESUMEN_BOLETAS  = 'enviar_resumen_boletas';
    const PRIV_CREAR_BAJA_FE           = 'crear_baja_fe';
    const PRIV_ENVIAR_NCE_SUNAT        = 'enviar_nce_sunat';
    const PRIV_CREAR_NOTA_CREDITO      = 'crear_nota_credito';
    const PRIV_VER_GUIAS_REGISTRADAS   = 'ver_guias_registradas';

    // Herramientas
    const PRIV_EDITAR_DATOS_LOCAL    = 'editar_datos_local';
    const PRIV_EXPORTAR_BD           = 'exportar_bd';
    const PRIV_IMPORTAR_BD           = 'importar_bd';
    const PRIV_TRANSFERENCIA_ARCHIVOS= 'transferencia_archivos';
    const PRIV_AJUSTE_INV_VALORIZADO = 'ajuste_inv_valorizado';

    /**
     * Privilegios agrupados por categoría.
     * Cada ítem: ['label' => '...', 'ready' => bool]
     *   ready=true  → privilegio implementado, el checkbox está activo
     *   ready=false → pendiente de implementar, se muestra como disabled
     */
    const PRIVILEGES_GROUPS = [
        'Ventas' => [
            'icon'  => 'fa-cash-register',
            'color' => 'sky',
            'items' => [
                self::PRIV_VER_VENTAS                => ['label' => 'Punto de Venta',                     'ready' => true],
                self::PRIV_VER_HISTORIAL             => ['label' => 'Ver Comprobantes Emitidos',           'ready' => true],
                self::PRIV_ANULAR_VENTA              => ['label' => 'Anular Comprobante de Venta',         'ready' => true],
                self::PRIV_APLICAR_DESCUENTO         => ['label' => 'Aplicar Descuento en Venta',          'ready' => false],
                self::PRIV_TIPO_CAMBIO               => ['label' => 'Tipo de Cambio',                      'ready' => false],
                self::PRIV_ADMIN_CORRELATIVOS        => ['label' => 'Administrador de Correlativos',       'ready' => false],
                self::PRIV_PRODUCTOS_SIN_STOCK_VENTA => ['label' => 'Productos sin Stock por Venta',       'ready' => false],
                self::PRIV_VENTA_PERDIDA_SIN_STOCK   => ['label' => 'Venta Perdida sin Stock',             'ready' => false],
                self::PRIV_OTROS_INGRESOS            => ['label' => 'Otros Ingresos',                      'ready' => false],
                self::PRIV_GASTOS_DIA                => ['label' => 'Gastos del Día',                      'ready' => false],
                self::PRIV_VER_CIERRES_CAJA          => ['label' => 'Ver Cierres de Caja',                 'ready' => false],
            ],
        ],
        'Caja' => [
            'icon'  => 'fa-vault',
            'color' => 'emerald',
            'items' => [
                self::PRIV_ABRIR_CAJA           => ['label' => 'Abrir Caja',                  'ready' => true],
                self::PRIV_CERRAR_CAJA          => ['label' => 'Cerrar Caja',                 'ready' => true],
                self::PRIV_EDITAR_APERTURA      => ['label' => 'Editar Apertura de Caja',     'ready' => true],
                self::PRIV_VER_MOVIMIENTOS_CAJA => ['label' => 'Ver Movimientos de Caja',     'ready' => true],
            ],
        ],
        'Catálogo' => [
            'icon'  => 'fa-box-open',
            'color' => 'violet',
            'items' => [
                self::PRIV_VER_INVENTARIO         => ['label' => 'Ver Productos / Catálogo',              'ready' => true],
                self::PRIV_GESTIONAR_CLIENTES     => ['label' => 'Crear y Editar Clientes',               'ready' => true],
                self::PRIV_CREAR_PRODUCTOS        => ['label' => 'Crear Nuevo Producto',                  'ready' => false],
                self::PRIV_MANTENIMIENTO_FAMILIAS => ['label' => 'Mantenimiento de Familias',             'ready' => false],
                self::PRIV_PRODUCTOS_SIN_ROTACION => ['label' => 'Productos sin Rotación',                'ready' => false],
                self::PRIV_PRODUCTOS_REPOSICION   => ['label' => 'Productos para Reposición',             'ready' => false],
            ],
        ],
        'Inventario' => [
            'icon'  => 'fa-boxes-stacked',
            'color' => 'amber',
            'items' => [
                self::PRIV_VER_COMPRAS            => ['label' => 'Registro de Compras',                   'ready' => true],
                self::PRIV_VER_KARDEX             => ['label' => 'Ver Kardex de Productos',               'ready' => true],
                self::PRIV_AJUSTE_INVENTARIO      => ['label' => 'Ajuste de Inventario',                  'ready' => true],
                self::PRIV_TRASPASO_SALIDA        => ['label' => 'Traspaso de Salida',                    'ready' => false],
                self::PRIV_INVENTARIO_TOTAL_STOCK => ['label' => 'Inventario Total con Stock',            'ready' => false],
                self::PRIV_INVENTARIO_TOTAL       => ['label' => 'Inventario Total',                      'ready' => false],
                self::PRIV_INV_LAB_STOCK          => ['label' => 'Inventario por Laboratorio con Stock',  'ready' => false],
                self::PRIV_INV_LAB_TOTAL          => ['label' => 'Inventario por Laboratorio Total',      'ready' => false],
                self::PRIV_REPORTE_INV_VALORIZADO => ['label' => 'Reporte de Inventario Valorizado',      'ready' => false],
            ],
        ],
        'Reportes' => [
            'icon'  => 'fa-chart-bar',
            'color' => 'indigo',
            'items' => [
                self::PRIV_RECORD_MENSUAL_VENTAS => ['label' => 'Record Mensual de Ventas',               'ready' => false],
                self::PRIV_RECORD_GENERAL_VENTAS => ['label' => 'Record General de Ventas',               'ready' => false],
                self::PRIV_VER_DOCS_MES          => ['label' => 'Ver Documentos por Mes',                 'ready' => false],
                self::PRIV_REPORTES_POR_FECHA    => ['label' => 'Por Rangos de Fecha',                    'ready' => false],
                self::PRIV_REPORTE_COMISIONES    => ['label' => 'Reporte de Comisiones por Vendedor',     'ready' => false],
                self::PRIV_REPORTE_MAS_VENDIDOS  => ['label' => 'Reporte de Productos más Vendidos',      'ready' => false],
                self::PRIV_REPORTE_UTILIDAD      => ['label' => 'Reporte de Utilidad',                    'ready' => false],
                self::PRIV_IMPRIMIR_MOV_CAJA     => ['label' => 'Imprimir Movimiento de Caja por Día',    'ready' => false],
            ],
        ],
        'Seguridad' => [
            'icon'  => 'fa-shield-halved',
            'color' => 'rose',
            'items' => [
                self::PRIV_ADMIN_USUARIOS       => ['label' => 'Administrador de Usuarios',               'ready' => false],
                self::PRIV_ACTUALIZACION_PRECIO => ['label' => 'Actualización de Precio',                 'ready' => false],
                self::PRIV_ADMIN_COMISION       => ['label' => 'Administrador de Comisión',               'ready' => false],
                self::PRIV_CAMBIAR_NOTA_VENTA   => ['label' => 'Cambiar Nota de Venta',                   'ready' => false],
                self::PRIV_ELIMINAR_GUIA_ING    => ['label' => 'Eliminar Guía de Ingreso',                'ready' => false],
                self::PRIV_ELIMINAR_GUIA_SAL    => ['label' => 'Eliminar Guía de Salida',                 'ready' => false],
                self::PRIV_ASIGNAR_PRIVILEGIOS  => ['label' => 'Asignar Privilegios',                     'ready' => false],
            ],
        ],
        'SUNAT' => [
            'icon'  => 'fa-file-invoice',
            'color' => 'orange',
            'items' => [
                self::PRIV_ENVIAR_FE_SUNAT        => ['label' => 'Enviar FE a SUNAT',                     'ready' => false],
                self::PRIV_ENVIAR_RESUMEN_BOLETAS => ['label' => 'Enviar Resumen de Boletas',             'ready' => false],
                self::PRIV_CREAR_BAJA_FE          => ['label' => 'Crear Baja de FE',                      'ready' => false],
                self::PRIV_ENVIAR_NCE_SUNAT       => ['label' => 'Enviar NCE a SUNAT',                    'ready' => false],
                self::PRIV_CREAR_NOTA_CREDITO     => ['label' => 'Crear Nota de Crédito',                 'ready' => false],
                self::PRIV_VER_GUIAS_REGISTRADAS  => ['label' => 'Ver Guías Registradas',                 'ready' => false],
            ],
        ],
        'Herramientas' => [
            'icon'  => 'fa-screwdriver-wrench',
            'color' => 'slate',
            'items' => [
                self::PRIV_EDITAR_DATOS_LOCAL     => ['label' => 'Editar Datos Local',                    'ready' => false],
                self::PRIV_EXPORTAR_BD            => ['label' => 'Exportar Base de Datos',                'ready' => false],
                self::PRIV_IMPORTAR_BD            => ['label' => 'Importar Base de Datos',                'ready' => false],
                self::PRIV_TRANSFERENCIA_ARCHIVOS => ['label' => 'Transferencia de Archivos',             'ready' => false],
                self::PRIV_AJUSTE_INV_VALORIZADO  => ['label' => 'Ajuste de Inventario Valorizado',       'ready' => false],
            ],
        ],
    ];

    /**
     * Lista plana de todos los privilegios implementados (ready=true).
     * Usada por el middleware EnsureEmployeeHasPrivilege y hasPrivilege().
     */
    const PRIVILEGES_LIST = [
        // — Ventas —
        self::PRIV_VER_VENTAS    => 'Punto de Venta',
        self::PRIV_VER_HISTORIAL => 'Ver Comprobantes Emitidos',
        self::PRIV_ANULAR_VENTA  => 'Anular Comprobante de Venta',
        // — Caja —
        self::PRIV_ABRIR_CAJA           => 'Abrir Caja',
        self::PRIV_CERRAR_CAJA          => 'Cerrar Caja',
        self::PRIV_EDITAR_APERTURA      => 'Editar Apertura de Caja',
        self::PRIV_VER_MOVIMIENTOS_CAJA => 'Ver Movimientos de Caja',
        // — Catálogo —
        self::PRIV_VER_INVENTARIO     => 'Ver Productos / Catálogo',
        self::PRIV_GESTIONAR_CLIENTES => 'Crear y Editar Clientes',
        // — Inventario —
        self::PRIV_VER_COMPRAS       => 'Registro de Compras',
        self::PRIV_VER_KARDEX        => 'Ver Kardex de Productos',
        self::PRIV_AJUSTE_INVENTARIO => 'Ajuste de Inventario',
    ];

    protected $fillable = [
        'company_id',
        'branch_id',
        'role_id',
        'name',
        'email',
        'password',
        'privileges',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'privileges'        => 'array',
    ];

    // ── Relaciones ──────────────────────────────────────────────

    /** Compañía a la que pertenece */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /** Sede principal del empleado */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /** Rol asignado */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /** Historial de sedes y roles del empleado */
    public function histories(): HasMany
    {
        return $this->hasMany(EmployeeHistory::class, 'employee_id');
    }

    /** Registro activo en el historial (sin ended_at) */
    public function currentHistory()
    {
        return $this->hasOne(EmployeeHistory::class, 'employee_id')->whereNull('ended_at');
    }

    /** Cajas que ha abierto este empleado */
    public function cashRegisters(): HasMany
    {
        return $this->hasMany(CashRegister::class, 'employee_id');
    }

    /** Órdenes/ventas registradas por este empleado */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'employee_id');
    }

    /** Compras registradas por este empleado */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class, 'employee_id');
    }

    /** Empleados a los que autorizó cambios de sede/rol */
    public function authorizedHistories(): HasMany
    {
        return $this->hasMany(EmployeeHistory::class, 'authorized_by');
    }

    // ── Helpers ─────────────────────────────────────────────────

    /** ¿Es administrador de compañía? */
    public function isCompanyAdmin(): bool
    {
        return $this->role_id === Role::COMPANY_ADMIN;
    }

    /** ¿Es administrador de sede? */
    public function isBranchAdmin(): bool
    {
        return $this->role_id === Role::BRANCH_ADMIN;
    }

    /** ¿Es empleado regular? */
    public function isEmployee(): bool
    {
        return $this->role_id === Role::EMPLOYEE;
    }

    /**
     * Verifica si el empleado tiene un privilegio específico.
     * Los branch_admin (role_id=2) siempre tienen acceso total.
     * Los empleados regulares (role_id=3) solo tienen acceso a los privilegios asignados.
     */
    public function hasPrivilege(string $privilege): bool
    {
        // branch_admin tiene acceso a todo sin restricción
        if ($this->isBranchAdmin()) {
            return true;
        }

        return in_array($privilege, $this->privileges ?? [], true);
    }

    /**
     * Verifica si el empleado tiene al menos un privilegio asignado.
     * Los branch_admin siempre retornan true.
     * Empleados sin ningún privilegio solo pueden ver el dashboard.
     */
    public function hasAnyPrivilege(): bool
    {
        if ($this->isBranchAdmin()) {
            return true;
        }

        return !empty($this->privileges);
    }
}
