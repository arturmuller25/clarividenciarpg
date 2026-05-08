<?php
declare(strict_types=1);

/**
 * Validador server-side dos dados de Campanha.
 *
 * Espelha as regras visíveis no formulário e fortalece (cliente é confiável
 * apenas para UX — segurança é responsabilidade do PHP).
 */
final class CampanhaValidador
{
    public const NOME_MAX      = 140;
    public const SISTEMA_MAX   = 60;
    public const DESCRICAO_MIN = 10;
    public const DESCRICAO_MAX = 5000;

    /**
     * @param array<string, mixed> $entrada Dados crus vindos de $_POST.
     * @return array{
     *     dados: array{nome: string, sistema: string, descricao: string},
     *     erros: array<string, string>
     * }
     */
    public static function validar(array $entrada): array
    {
        $nome      = trim((string) ($entrada['nome']      ?? ''));
        $sistema   = trim((string) ($entrada['sistema']   ?? 'Ordem Paranormal'));
        $descricao = trim((string) ($entrada['descricao'] ?? ''));

        if ($sistema === '') {
            $sistema = 'Ordem Paranormal';
        }

        $erros = [];

        if ($nome === '') {
            $erros['nome'] = 'O nome da campanha é obrigatório.';
        } elseif (mb_strlen($nome) > self::NOME_MAX) {
            $erros['nome'] = sprintf('O nome deve ter no máximo %d caracteres.', self::NOME_MAX);
        }

        if (mb_strlen($sistema) > self::SISTEMA_MAX) {
            $erros['sistema'] = sprintf('Sistema deve ter no máximo %d caracteres.', self::SISTEMA_MAX);
        }

        if ($descricao === '') {
            $erros['descricao'] = 'A descrição é obrigatória.';
        } elseif (mb_strlen($descricao) < self::DESCRICAO_MIN) {
            $erros['descricao'] = sprintf('A descrição deve ter ao menos %d caracteres.', self::DESCRICAO_MIN);
        } elseif (mb_strlen($descricao) > self::DESCRICAO_MAX) {
            $erros['descricao'] = sprintf('A descrição deve ter no máximo %d caracteres.', self::DESCRICAO_MAX);
        }

        return [
            'dados' => [
                'nome'      => $nome,
                'sistema'   => $sistema,
                'descricao' => $descricao,
            ],
            'erros' => $erros,
        ];
    }
}
