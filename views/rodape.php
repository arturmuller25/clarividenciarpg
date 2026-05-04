        </main>

        <footer class="terminal__rodape">
            <span>// TERMINAL DA ORDEM v0.1</span>
            <span>// SESSAO #<?= escapar(substr(session_id() ?: '00000000', 0, 8)) ?></span>
            <span>// <?= escapar(date('Y-m-d H:i:s')) ?></span>
        </footer>
    </div>

    <script src="/assets/js/validacao.js" defer></script>
</body>
</html>
