# Clarividência Paranormal - Guia de Estilo e Regras

## Contexto Narrativo
Este sistema é um artefato da Ordem. O design deve ser místico/investigativo.

## Tech Stack
- PHP 8.4+ (Strict Types habilitado)
- MySQL (PDO sempre)
- Vanilla JS (Zero frameworks pesados)

## Regras de Ordem Paranormal
- Testes de Atributo: Jogar N d20 (onde N é o atributo) e retornar o MAIOR.
- Desastre: Se o atributo for 0, jogar 2 dados e pegar o MENOR.
- Crítico: Resultado 20 natural deve ser destacado visualmente.

## Padrões de Código
- Nomes de funções em camelCase.
- Comentários em PHP devem seguir o padrão PHPDoc.