<?php
declare(strict_types=1);

require_once __DIR__ . '/CriaturaRepositorio.php';

/**
 * Validador server-side dos dados de Criatura (bestiário).
 */
final class CriaturaValidador
{
    public const NOME_MAX        = 120;
    public const HABILIDADES_MIN = 5;
    public const HABILIDADES_MAX = 5000;
    public const VD_MIN          = 0.0;
    public const VD_MAX          = 30.0;
    public const PV_MIN          = 0;
    public const PV_MAX          = 9999;

    /**
     * @param array<string, mixed> $entrada
     * @return array{
     *     dados: array{nome: string, elemento: string, vd: float, pv_atual: int,
     *                  pv_maximo: int, habilidades: string},
     *     erros: array<string, string>
     * }
     */
    public static function validar(array $entrada): array
    {
        $nome        = trim((string) ($entrada['nome']        ?? ''));
        $elemento    = trim((string) ($entrada['elemento']    ?? ''));
        $habilidades = trim((string) ($entrada['habilidades'] ?? ''));

        $vdBruto       = (string) ($entrada['vd']        ?? '');
        $pvAtualBruto  = (string) ($entrada['pv_atual']  ?? '');
        $pvMaximoBruto = (string) ($entrada['pv_maximo'] ?? '');

        // campanha_id: opcional (NULL = criatura sem campanha vinculada)
        $campanhaIdBruto = $entrada['campanha_id'] ?? '';
        $campanhaId = (is_string($campanhaIdBruto) && ctype_digit($campanhaIdBruto) && (int) $campanhaIdBruto > 0)
            ? (int) $campanhaIdBruto
            : null;

        $vd        = is_numeric($vdBruto)       ? (float) $vdBruto       : -1.0;
        $pvAtual   = is_numeric($pvAtualBruto)  ? (int)   $pvAtualBruto  : -1;
        $pvMaximo  = is_numeric($pvMaximoBruto) ? (int)   $pvMaximoBruto : -1;

        $erros = [];

        if ($nome === '') {
            $erros['nome'] = 'O nome da criatura e obrigatorio.';
        } elseif (mb_strlen($nome) > self::NOME_MAX) {
            $erros['nome'] = sprintf('O nome deve ter no maximo %d caracteres.', self::NOME_MAX);
        }

        if (!in_array($elemento, CriaturaRepositorio::ELEMENTOS, true)) {
            $erros['elemento'] = 'Selecione um elemento valido (Sangue, Morte, Conhecimento, Energia).';
        }

        if ($vd < self::VD_MIN || $vd > self::VD_MAX) {
            $erros['vd'] = sprintf('VD deve estar entre %.1f e %.1f.', self::VD_MIN, self::VD_MAX);
        }

        if ($pvMaximo < self::PV_MIN || $pvMaximo > self::PV_MAX) {
            $erros['pv_maximo'] = sprintf('PV maximo deve estar entre %d e %d.', self::PV_MIN, self::PV_MAX);
        }

        if ($pvAtual < self::PV_MIN || $pvAtual > self::PV_MAX) {
            $erros['pv_atual'] = sprintf('PV atual deve estar entre %d e %d.', self::PV_MIN, self::PV_MAX);
        } elseif ($pvMaximo >= 0 && $pvAtual > $pvMaximo) {
            $erros['pv_atual'] = 'PV atual nao pode exceder o PV maximo.';
        }

        if ($habilidades === '') {
            $erros['habilidades'] = 'Descreva ao menos uma habilidade.';
        } elseif (mb_strlen($habilidades) < self::HABILIDADES_MIN) {
            $erros['habilidades'] = sprintf('Habilidades devem ter ao menos %d caracteres.', self::HABILIDADES_MIN);
        } elseif (mb_strlen($habilidades) > self::HABILIDADES_MAX) {
            $erros['habilidades'] = sprintf('Habilidades devem ter no maximo %d caracteres.', self::HABILIDADES_MAX);
        }

        return [
            'dados' => [
                'campanha_id' => $campanhaId,
                'nome'        => $nome,
                'elemento'    => $elemento,
                'vd'          => max(0.0, $vd),
                'pv_atual'    => max(0, $pvAtual),
                'pv_maximo'   => max(0, $pvMaximo),
                'habilidades' => $habilidades,
            ],
            'erros' => $erros,
        ];
    }
}
