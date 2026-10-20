// Menú hamburguesa (tablet y celular) y cierre del menú de usuario al tocar afuera
(function () {
    var nav = document.getElementById('navbar');
    var btn = document.getElementById('navToggle');
    if (!nav || !btn) return;

    btn.addEventListener('click', function () {
        var abierto = nav.classList.toggle('abierto');
        btn.setAttribute('aria-expanded', abierto ? 'true' : 'false');
        btn.setAttribute('aria-label', abierto ? 'Cerrar menú' : 'Abrir menú');
    });

    document.addEventListener('click', function (e) {
        var det = nav.querySelector('details.nav-user');
        if (det && det.open && !det.contains(e.target)) det.open = false;
        if (nav.classList.contains('abierto') && !nav.contains(e.target)) {
            nav.classList.remove('abierto');
            btn.setAttribute('aria-expanded', 'false');
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        var det = nav.querySelector('details.nav-user');
        if (det) det.open = false;
        nav.classList.remove('abierto');
        btn.setAttribute('aria-expanded', 'false');
    });
})();
