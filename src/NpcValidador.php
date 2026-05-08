<?php
declare(strict_types=1);

require_once __DIR__ . '/NpcRepositorio.php';

/**
 * Validador server-side dos dados de NPC.
 *
 * Espelha (e fortalece) as regras aplicadas no cliente em `validacao.js`.
 * Nunca confie apenas no JS — o navegador pode ser desabilitado ou alterado.
 */
final class NpcValidador
{
    public const NOME_MAX        = 120;
    public const OCUPACAO_MAX    = 120;
    public const LOCALIZACAO_MAX = 120;
    public const HISTORIA_MIN    = 10;
    public const HISTORIA_MAX    = 5000;

    /**
     * Valida e normaliza um payload de NPC.
     *
     * @param array<string, mixed> $entrada Dados crus vindos de $_POST.
     * @return array{
     *     dados: array{nome: string, ocupacao: string, localizacao: string, atitude: string, historia: string},
     *     erros: array<string, string>
     * }
     */
    public static function validar(array $entrada): array
    {
        $nome        = trim((string) ($entrada['nome'] ?? ''));
        $ocupacao    = trim((string) ($entrada['ocupacao'] ?? ''));
        $localizacao = trim((string) ($entrada['localizacao'] ?? ''));
        $atitude     = trim((string) ($entrada['atitude'] ?? ''));
        $historia    = trim((string) ($entrada['historia'] ?? ''));

        // campanha_id: opcional (NULL = NPC sem campanha vinculada)
        $campanhaIdBruto = $entrada['campanha_id'] ?? '';
        $campanhaId = (is_string($campanhaIdBruto) && ctype_digit($campanhaIdBruto) && (int) $campanhaIdBruto > 0)
            ? (int) $campanhaIdBruto
            : null;

        $erros = [];

        if ($nome === '') {
            $erros['nome'] = 'O nome do NPC e obrigatorio.';
        } elseif (mb_strlen($nome) > self::NOME_MAX) {
            $erros['nome'] = sprintf('O nome deve ter no maximo %d caracteres.', self::NOME_MAX);
        }

        if ($ocupacao === '') {
            $erros['ocupacao'] = 'A ocupacao e obrigatoria.';
        } elseif (mb_strlen($ocupacao) > self::OCUPACAO_MAX) {
            $erros['ocupacao'] = sprintf('A ocupacao deve ter no maximo %d caracteres.', self::OCUPACAO_MAX);
        }

        if ($localizacao === '') {
            $erros['localizacao'] = 'A localizacao e obrigatoria.';
        } elseif (mb_strlen($localizacao) > self::LOCALIZACAO_MAX) {
            $erros['localizacao'] = sprintf('A localizacao deve ter no maximo %d caracteres.', self::LOCALIZACAO_MAX);
        }

        if (!in_array($atitude, NpcRepositorio::ATITUDES, true)) {
            $erros['atitude'] = 'Atitude invalida. Selecione Amigavel, Neutro ou Hostil.';
        }

        if ($historia === '') {
            $erros['historia'] = 'A historia e obrigatoria.';
        } elseif (mb_strlen($historia) < self::HISTORIA_MIN) {
            $erros['historia'] = sprintf('A historia deve ter ao menos %d caracteres.', self::HISTORIA_MIN);
        } elseif (mb_strlen($historia) > self::HISTORIA_MAX) {
            $erros['historia'] = sprintf('A historia deve ter no maximo %d caracteres.', self::HISTORIA_MAX);
        }

        return [
            'dados' => [
                'campanha_id' => $campanhaId,
                'nome'        => $nome,
                'ocupacao'    => $ocupacao,
                'localizacao' => $localizacao,
                'atitude'     => $atitude,
                'historia'    => $historia,
            ],
            'erros' => $erros,
        ];
    }
}
