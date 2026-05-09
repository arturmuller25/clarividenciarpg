        </main>

        <footer class="terminal__rodape">
            <span>// CLARIVID&Ecirc;NCIA_PARANORMAL v0.2</span>
            <span>// SESSÃO #<?= escapar(substr(session_id() ?: '00000000', 0, 8)) ?></span>
            <span>// <?= escapar(date('Y-m-d H:i:s')) ?></span>
        </footer>
    </div>

    <?php /* Microcopy oculto — fora de .terminal para escapar do stacking
              context (z-index:1) que cap'va a visibilidade abaixo do
              body::after (vignette z-index:2). Easter egg site-wide. */ ?>
    <aside class="microcopy-oculto" aria-hidden="true">// nao confiar nos numeros pares</aside>

    <script src="<?= escapar(url('/assets/js/validacao.js')) ?>" defer></script>
    <script src="<?= escapar(url('/assets/js/hero.js')) ?>" defer></script>
    <script src="<?= escapar(url('/assets/js/agente.js')) ?>" defer></script>
    <script src="<?= escapar(url('/assets/js/cropper.js')) ?>" defer></script>
</body>
</html>
