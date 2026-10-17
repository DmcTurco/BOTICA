{{-- Ojito para ver u ocultar la contraseña en todos los campos type="password" del sistema.
     Se incluye en los layouts de admin, empresa y empleado, y en sus pantallas de login.
     Los campos que ya traen su propio botón de ojo (fa-eye a su lado) se dejan como están. --}}
<script>
    (function () {
        function agregarOjito(input) {
            if (input.dataset.ojito) return;
            input.dataset.ojito = '1';

            // Ya tiene su propio botón de ojo
            if (input.parentElement && input.parentElement.querySelector('.fa-eye, .fa-eye-slash')) return;

            // El botón se ubica sobre el borde derecho del campo
            var contenedor = document.createElement('div');
            contenedor.style.position = 'relative';
            input.parentNode.insertBefore(contenedor, input);
            contenedor.appendChild(input);
            input.style.paddingRight = '2.5rem';

            var boton = document.createElement('button');
            boton.type = 'button';
            boton.tabIndex = -1;
            boton.title = 'Mostrar contraseña';
            boton.setAttribute('aria-label', 'Mostrar contraseña');
            boton.className = 'text-slate-400 hover:text-slate-600';
            boton.style.cssText = 'position:absolute;top:0;bottom:0;right:0;display:flex;align-items:center;padding:0 0.75rem;';
            boton.innerHTML = '<i class="fas fa-eye text-sm"></i>';
            boton.addEventListener('click', function () {
                var visible = input.type === 'password';
                input.type = visible ? 'text' : 'password';
                boton.firstChild.className = 'fas ' + (visible ? 'fa-eye-slash' : 'fa-eye') + ' text-sm';
                boton.title = visible ? 'Ocultar contraseña' : 'Mostrar contraseña';
                boton.setAttribute('aria-label', boton.title);
                input.focus();
            });
            contenedor.appendChild(boton);
        }

        function iniciar() {
            document.querySelectorAll('input[type="password"]').forEach(agregarOjito);
        }

        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciar);
        else iniciar();
    })();
</script>
