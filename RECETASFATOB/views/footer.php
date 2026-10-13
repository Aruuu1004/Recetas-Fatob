<?php
// Datos de contacto del pie de página (se cambian acá, en un solo lugar)
$contactoMail      = 'estudios.ariadna@gmail.com';
$contactoGithub    = 'https://github.com/Aruuu1004/Recetas-Fatob';
$contactoInstagram = 'https://www.instagram.com/'; // Por ahora va a la página principal de Instagram; cuando tengan cuenta, poner el enlace del perfil
?>
<footer class="footer">
    <div class="footer-contenido">
        <span></span>

        <div class="footer-texto">
            <p>¿Te interesa conocer o contactar a los creadores?</p>
            <p>
                <a class="footer-mail" href="https://mail.google.com/mail/?view=cm&fs=1&to=<?= urlencode($contactoMail) ?>" target="_blank" rel="noopener">
                    <svg class="ico" viewBox="0 0 24 24" aria-hidden="true">
                        <rect x="3" y="5" width="18" height="14" rx="2" fill="none" stroke="currentColor" stroke-width="2"/>
                        <path d="M3 7l9 6 9-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                    </svg>
                    <?= htmlspecialchars($contactoMail) ?>
                </a>
            </p>
            <p class="chico">&copy; <?= date('Y') ?> RECETAS FATOB - Fatob Studios. Todos los derechos reservados.</p>
        </div>

        <div class="footer-redes">
            <a href="<?= htmlspecialchars($contactoGithub) ?>" target="_blank" rel="noopener" aria-label="GitHub" title="GitHub">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path fill="currentColor" d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/>
                </svg>
            </a>
            <?php if ($contactoInstagram !== ''): ?>
                <a href="<?= htmlspecialchars($contactoInstagram) ?>" target="_blank" rel="noopener" aria-label="Instagram" title="Instagram">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <rect x="3" y="3" width="18" height="18" rx="5" fill="none" stroke="currentColor" stroke-width="2"/>
                        <circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/>
                        <circle cx="17.5" cy="6.5" r="1.2" fill="currentColor"/>
                    </svg>
                </a>
            <?php endif; ?>
        </div>
    </div>
</footer>
<button id="btn-top" title="Volver arriba" aria-label="Volver arriba">↑</button>
<script src="js/backtotop.js"></script>
<script src="js/carousel.js"></script>
