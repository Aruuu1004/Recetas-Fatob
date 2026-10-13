// Carousel genérico: funciona con cualquier <div class="carousel"> que tenga un .carousel-track
// con elementos .carousel-item, y (opcional) botones .carousel-prev / .carousel-next y un .carousel-dots.
// Se desliza con el dedo (scroll nativo), con las flechas y con los puntitos.
(function () {
    document.querySelectorAll('.carousel').forEach(function (c) {
        var track = c.querySelector('.carousel-track');
        var items = c.querySelectorAll('.carousel-item');
        var prev = c.querySelector('.carousel-prev');
        var next = c.querySelector('.carousel-next');
        var dots = c.querySelector('.carousel-dots');
        if (!track || !items.length) return;

        function paso() {
            var gap = parseFloat(getComputedStyle(track).columnGap) || 0;
            return items[0].getBoundingClientRect().width + gap;
        }
        function actual() { return Math.round(track.scrollLeft / paso()); }

        if (dots) {
            items.forEach(function (_, i) {
                var d = document.createElement('span');
                d.addEventListener('click', function () { track.scrollTo({ left: i * paso() }); });
                dots.appendChild(d);
            });
        }
        function actualizar() {
            c.classList.toggle('carousel-sin-scroll', track.scrollWidth <= track.clientWidth + 1);
            if (dots) {
                var a = actual();
                Array.prototype.forEach.call(dots.children, function (d, i) { d.classList.toggle('activo', i === a); });
            }
        }

        // Al llegar al final, la flecha siguiente vuelve al principio (y al revés)
        if (next) next.addEventListener('click', function () {
            var alFinal = track.scrollLeft + track.clientWidth >= track.scrollWidth - 2;
            track.scrollTo({ left: alFinal ? 0 : track.scrollLeft + paso() });
        });
        if (prev) prev.addEventListener('click', function () {
            track.scrollTo({ left: track.scrollLeft <= 2 ? track.scrollWidth : track.scrollLeft - paso() });
        });

        track.addEventListener('scroll', actualizar);
        window.addEventListener('resize', actualizar);
        window.addEventListener('load', actualizar);
        actualizar();
    });
})();
