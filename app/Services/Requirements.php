<?php

namespace App\Services;

use Illuminate\Contracts\View\View;

/**
 * Requisitos previos de una pantalla (p. ej. "para crear un producto debe existir una categoría").
 * Si falta alguno, en vez del formulario se muestra una pantalla de aviso con lo que hay
 * que registrar primero y un enlace para hacerlo, así el sistema nunca falla por datos vacíos.
 *
 * Uso:
 *   $requirements = Requirements::make('Nuevo producto', 'employee.products.index')
 *       ->need($categories->isNotEmpty(), 'Categorías', 'Cada producto pertenece a una categoría.', 'employee.categories.create');
 *
 *   if ($requirements->blocked()) {
 *       return $requirements->view();
 *   }
 */
class Requirements
{
    /** @var array<int, array{ok: bool, label: string, hint: string, url: ?string, action: string}> */
    private array $items = [];

    private function __construct(
        private string $title,
        private ?string $backRoute,
    ) {
    }

    /**
     * Inicia la lista de requisitos de una pantalla.
     *
     * @param string      $title     Nombre de la pantalla bloqueada ("Nuevo producto")
     * @param string|null $backRoute Ruta a la que vuelve el botón "Volver"
     */
    public static function make(string $title, ?string $backRoute = null): self
    {
        return new self($title, $backRoute);
    }

    /**
     * Agrega un requisito.
     *
     * @param bool        $ok     ¿Se cumple?
     * @param string      $label  Qué se necesita ("Categorías")
     * @param string      $hint   Explicación breve de por qué o cómo cumplirlo
     * @param string|null $route  Ruta para registrarlo (null si lo resuelve otro panel)
     * @param string      $action Texto del botón
     */
    public function need(bool $ok, string $label, string $hint, ?string $route = null, string $action = 'Registrar'): self
    {
        $this->items[] = [
            'ok'     => $ok,
            'label'  => $label,
            'hint'   => $hint,
            'url'    => $route ? route($route) : null,
            'action' => $action,
        ];

        return $this;
    }

    /**
     * ¿Falta al menos un requisito?
     */
    public function blocked(): bool
    {
        return collect($this->items)->contains('ok', false);
    }

    /**
     * Pantalla de aviso con la lista de requisitos (cumplidos y pendientes).
     */
    public function view(): View
    {
        return view('employee.pages.requirements', [
            'title'   => $this->title,
            'items'   => $this->items,
            'backUrl' => $this->backRoute ? route($this->backRoute) : null,
        ]);
    }
}
