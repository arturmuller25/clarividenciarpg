<?php
declare(strict_types=1);

require_once __DIR__ . '/AgenteRepositorio.php';
require_once __DIR__ . '/PericiaCatalog.php';

/**
 * Validação server-side da ficha completa de Agente.
 *
 * Trata: Identidade, Barras (PV/SAN/PE), Atributos (5), Defesa, Narrativa.
 * As listas-filhas (perícias, ataques, inventário, rituais) também são
 * normalizadas aqui — o repositório espera dados já saneados.
 */
final class AgenteValidador
{
    public const NOME_MAX     = 120;
    public const JOGADOR_MAX  = 120;
    public const ORIGEM_MAX   = 60;
    public const NEX_MIN      = 1;
    public const NEX_MAX      = 99;
    public const ATRIB_MIN    = 0;
    public const ATRIB_MAX    = 6;
    public const PV_MAX       = 999;
    public const NARRATIVA_MAX = 5000;

    /**
     * @param array<string, mixed> $entrada $_POST inteiro
     * @return array{
     *     dados: array<string, mixed>,
     *     erros: array<string, string>
     * }
     */
    public static function validar(array $entrada): array
    {
        $erros = [];

        // ---------------------------------------------------------------- Identidade
        $nome    = trim((string) ($entrada['nome']    ?? ''));
        $jogador = trim((string) ($entrada['jogador'] ?? ''));
        $origem  = trim((string) ($entrada['origem']  ?? ''));
        $classe  = trim((string) ($entrada['classe']  ?? 'Combatente'));
        if (!in_array($classe, AgenteRepositorio::CLASSES, true)) $classe = 'Combatente';

        $nex = (int) ($entrada['nex'] ?? self::NEX_MIN);

        if ($nome === '')                             $erros['nome'] = 'O nome do agente é obrigatório.';
        elseif (mb_strlen($nome) > self::NOME_MAX)    $erros['nome'] = sprintf('Máximo %d caracteres.', self::NOME_MAX);

        if (mb_strlen($jogador) > self::JOGADOR_MAX) $erros['jogador'] = 'Nome do jogador muito longo.';
        if (mb_strlen($origem)  > self::ORIGEM_MAX)  $erros['origem']  = 'Origem muito longa.';

        if ($nex < self::NEX_MIN || $nex > self::NEX_MAX) {
            $erros['nex'] = sprintf('NEX deve estar entre %d e %d.', self::NEX_MIN, self::NEX_MAX);
            $nex = max(self::NEX_MIN, min(self::NEX_MAX, $nex));
        }

        // campanha_id opcional
        $campanhaIdBruto = $entrada['campanha_id'] ?? '';
        $campanhaId = (is_string($campanhaIdBruto) && ctype_digit($campanhaIdBruto) && (int) $campanhaIdBruto > 0)
            ? (int) $campanhaIdBruto : null;

        // ---------------------------------------------------------------- Barras
        $pvAt = max(0, min(self::PV_MAX, (int) ($entrada['pv_atual']   ?? 0)));
        $pvMx = max(0, min(self::PV_MAX, (int) ($entrada['pv_maximo']  ?? 0)));
        $sAt  = max(0, min(self::PV_MAX, (int) ($entrada['san_atual']  ?? 0)));
        $sMx  = max(0, min(self::PV_MAX, (int) ($entrada['san_maximo'] ?? 0)));
        $peAt = max(0, min(self::PV_MAX, (int) ($entrada['pe_atual']   ?? 0)));
        $peMx = max(0, min(self::PV_MAX, (int) ($entrada['pe_maximo']  ?? 0)));

        if ($pvAt > $pvMx) $erros['pv_atual']  = 'PV atual não pode exceder o máximo.';
        if ($sAt  > $sMx)  $erros['san_atual'] = 'SAN atual não pode exceder o máximo.';
        if ($peAt > $peMx) $erros['pe_atual']  = 'PE atual não pode exceder o máximo.';

        // ---------------------------------------------------------------- Atributos
        $forca     = self::clampAtrib($entrada['forca']     ?? 1);
        $agilidade = self::clampAtrib($entrada['agilidade'] ?? 1);
        $intelecto = self::clampAtrib($entrada['intelecto'] ?? 1);
        $vigor     = self::clampAtrib($entrada['vigor']     ?? 1);
        $presenca  = self::clampAtrib($entrada['presenca']  ?? 1);

        // ---------------------------------------------------------------- Defesa
        $peTurno      = max(0, (int) ($entrada['pe_por_turno'] ?? 2));
        $deslocamento = max(0, (int) ($entrada['deslocamento'] ?? 9));
        $defesa       = max(0, (int) ($entrada['defesa']       ?? 10));
        $resistencias = self::trimMax($entrada['resistencias']  ?? '', self::NARRATIVA_MAX);
        $proficiencias= self::trimMax($entrada['proficiencias'] ?? '', self::NARRATIVA_MAX);

        // ---------------------------------------------------------------- Narrativa
        $aparencia     = self::trimMax($entrada['aparencia']     ?? '', self::NARRATIVA_MAX);
        $personalidade = self::trimMax($entrada['personalidade'] ?? '', self::NARRATIVA_MAX);
        $historico     = self::trimMax($entrada['historico']     ?? '', self::NARRATIVA_MAX);
        $objetivos     = self::trimMax($entrada['objetivos']     ?? '', self::NARRATIVA_MAX);

        // ---------------------------------------------------------------- Perícias
        $periciasIn = $entrada['pericias'] ?? [];
        $pericias = [];
        if (is_array($periciasIn)) {
            foreach ($periciasIn as $nomeP => $info) {
                if (!is_string($nomeP) || !PericiaCatalog::existe($nomeP)) continue;
                $grau  = (string) ($info['grau'] ?? 'Destreinado');
                if (!PericiaCatalog::grauValido($grau)) $grau = 'Destreinado';
                $bonus = (int) ($info['bonus_extra'] ?? 0);
                $bonus = max(-20, min(50, $bonus));
                $pericias[$nomeP] = [
                    'grau'        => $grau,
                    'bonus_extra' => $bonus,
                ];
            }
        }

        // ---------------------------------------------------------------- Ataques (lista)
        $ataquesIn = $entrada['ataques'] ?? [];
        $ataques = is_array($ataquesIn) ? array_values(array_filter(
            array_map(fn($a) => self::normalizarAtaque(is_array($a) ? $a : []), $ataquesIn),
            fn($a) => $a['nome'] !== ''
        )) : [];

        // ---------------------------------------------------------------- Inventário (lista)
        $invIn = $entrada['inventario'] ?? [];
        $inventario = is_array($invIn) ? array_values(array_filter(
            array_map(fn($i) => self::normalizarItem(is_array($i) ? $i : []), $invIn),
            fn($i) => $i['nome'] !== ''
        )) : [];

        // ---------------------------------------------------------------- Rituais (lista)
        $ritIn = $entrada['rituais'] ?? [];
        $rituais = is_array($ritIn) ? array_values(array_filter(
            array_map(fn($r) => self::normalizarRitual(is_array($r) ? $r : []), $ritIn),
            fn($r) => $r['nome'] !== ''
        )) : [];

        return [
            'dados' => [
                'agente' => [
                    'campanha_id'   => $campanhaId,
                    'nome'          => $nome,
                    'jogador'       => $jogador !== '' ? $jogador : null,
                    'origem'        => $origem  !== '' ? $origem  : null,
                    'classe'        => $classe,
                    'nex'           => $nex,
                    'pv_atual'      => $pvAt, 'pv_maximo'  => $pvMx,
                    'san_atual'     => $sAt,  'san_maximo' => $sMx,
                    'pe_atual'      => $peAt, 'pe_maximo'  => $peMx,
                    'forca'         => $forca,
                    'agilidade'     => $agilidade,
                    'intelecto'     => $intelecto,
                    'vigor'         => $vigor,
                    'presenca'      => $presenca,
                    'pe_por_turno'  => $peTurno,
                    'deslocamento'  => $deslocamento,
                    'defesa'        => $defesa,
                    'resistencias'  => $resistencias !== '' ? $resistencias : null,
                    'proficiencias' => $proficiencias !== '' ? $proficiencias : null,
                    'aparencia'     => $aparencia     !== '' ? $aparencia     : null,
                    'personalidade' => $personalidade !== '' ? $personalidade : null,
                    'historico'     => $historico     !== '' ? $historico     : null,
                    'objetivos'     => $objetivos     !== '' ? $objetivos     : null,
                ],
                'pericias'   => $pericias,
                'ataques'    => $ataques,
                'inventario' => $inventario,
                'rituais'    => $rituais,
            ],
            'erros' => $erros,
        ];
    }

    private static function clampAtrib(mixed $v): int
    {
        $v = (int) $v;
        return max(self::ATRIB_MIN, min(self::ATRIB_MAX, $v));
    }

    private static function trimMax(mixed $v, int $max): string
    {
        $s = trim((string) $v);
        return mb_strlen($s) > $max ? mb_substr($s, 0, $max) : $s;
    }

    /** @return array<string, mixed> */
    private static function normalizarAtaque(array $a): array
    {
        $atrib = (string) ($a['atributo_base'] ?? 'forca');
        if (!in_array($atrib, AgenteRepositorio::ATRIBUTOS, true)) $atrib = 'forca';
        return [
            'nome'          => trim((string) ($a['nome'] ?? '')),
            'atributo_base' => $atrib,
            'bonus_arma'    => max(-20, min(50, (int) ($a['bonus_arma']  ?? 0))),
            'bonus_extra'   => max(-20, min(50, (int) ($a['bonus_extra'] ?? 0))),
            'dano'          => trim((string) ($a['dano'] ?? '1d4')) ?: '1d4',
            'tipo_dano'     => self::trimMax($a['tipo_dano'] ?? '', 40) ?: null,
            'descricao'     => self::trimMax($a['descricao'] ?? '', 1000) ?: null,
        ];
    }

    /** @return array<string, mixed> */
    private static function normalizarItem(array $i): array
    {
        return [
            'nome'       => trim((string) ($i['nome'] ?? '')),
            'descricao'  => self::trimMax($i['descricao'] ?? '', 1000) ?: null,
            'categoria'  => self::trimMax($i['categoria'] ?? '', 40) ?: null,
            'espacos'    => max(0.0, min(99.99, (float) ($i['espacos'] ?? 1))),
            'quantidade' => max(1, (int) ($i['quantidade'] ?? 1)),
            'equipado'   => !empty($i['equipado']),
        ];
    }

    /** @return array<string, mixed> */
    private static function normalizarRitual(array $r): array
    {
        $elem = (string) ($r['elemento'] ?? 'Conhecimento');
        if (!in_array($elem, AgenteRepositorio::ELEMENTOS, true)) $elem = 'Conhecimento';
        $circ = (int) ($r['circulo'] ?? 1);
        if ($circ < 1 || $circ > 5) $circ = 1;
        return [
            'nome'      => trim((string) ($r['nome'] ?? '')),
            'circulo'   => $circ,
            'elemento'  => $elem,
            'custo_pe'  => max(0, min(99, (int) ($r['custo_pe'] ?? 1))),
            'descricao' => self::trimMax($r['descricao'] ?? '', 2000) ?: null,
        ];
    }
}
