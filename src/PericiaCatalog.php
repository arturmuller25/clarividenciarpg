<?php
declare(strict_types=1);

/**
 * Catálogo canônico das 26 perícias de Ordem Paranormal e o atributo
 * que cada uma usa como base. Útil para popular a UI da ficha de
 * agente e para futuras automações de cálculo (testes de perícia).
 */
final class PericiaCatalog
{
    public const GRAUS = ['Destreinado', 'Treinado', 'Veterano', 'Especialista'];

    /**
     * @return array<string, string> nome da perícia => atributo base
     */
    public static function pericias(): array
    {
        return [
            'Acrobacia'     => 'agilidade',
            'Adestramento'  => 'presenca',
            'Atletismo'     => 'forca',
            'Atualidades'   => 'intelecto',
            'Ciências'      => 'intelecto',
            'Crime'         => 'agilidade',
            'Diplomacia'    => 'presenca',
            'Enganação'     => 'presenca',
            'Fortitude'     => 'vigor',
            'Furtividade'   => 'agilidade',
            'Iniciativa'    => 'agilidade',
            'Intimidação'   => 'presenca',
            'Intuição'      => 'presenca',
            'Investigação'  => 'intelecto',
            'Luta'          => 'forca',
            'Medicina'      => 'intelecto',
            'Ocultismo'     => 'intelecto',
            'Percepção'     => 'presenca',
            'Pilotagem'     => 'agilidade',
            'Pontaria'      => 'agilidade',
            'Profissão'     => 'intelecto',
            'Reflexos'      => 'agilidade',
            'Religião'      => 'intelecto',
            'Sobrevivência' => 'intelecto',
            'Tecnologia'    => 'intelecto',
            'Vontade'       => 'presenca',
        ];
    }

    public static function existe(string $pericia): bool
    {
        return array_key_exists($pericia, self::pericias());
    }

    public static function grauValido(string $grau): bool
    {
        return in_array($grau, self::GRAUS, true);
    }
}
