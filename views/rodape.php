        </main>

        <footer class="terminal__rodape">
            <span>// CLARIVID&Ecirc;NCIA_PARANORMAL v0.2</span>
            <span>// SESSAO #<?= escapar(substr(session_id() ?: '00000000', 0, 8)) ?></span>
            <span>// <?= escapar(date('Y-m-d H:i:s')) ?></span>
        </footer>
    </div>

    <script src="<?= escapar(url('/assets/js/validacao.js')) ?>" defer></script>
    <script src="<?= escapar(url('/assets/js/hero.js')) ?>" defer></script>
    <script src="<?= escapar(url('/assets/js/agente.js')) ?>" defer></script>
</body>
</html>
