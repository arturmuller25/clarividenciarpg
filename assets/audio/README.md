# Pasta de áudio do Terminal da Ordem

Coloque os arquivos abaixo aqui para ativar os SFX do sistema.
Os arquivos NÃO estão versionados no repositório (são assets do projeto).

## Arquivos esperados

| Arquivo                          | Quando toca                                                     | Volume default |
|----------------------------------|-----------------------------------------------------------------|----------------|
| `som_para_a_hero.mp3`            | Início da animação do d20 na Splash (sincronizado com a rotação) | 0.70           |
| `som_para_as_rolagens.mp3`       | Cada vez que o usuário invoca uma rolagem (qualquer tipo de dado) | 0.55           |

## Comportamento

- **Hero**: o JavaScript tenta tocar o áudio automaticamente. Se o navegador
  bloquear o autoplay, um botão **"CLIQUE PARA INICIAR O RITUAL"** aparece
  no centro da tela. Após o clique, o som toca em sincronia com a animação.
- **Rolagens**: o som dispara no mesmo instante que a animação do dado
  começa. Se o áudio for mais longo que a animação (~1.1s), há fade-out
  suave de 450ms para evitar corte abrupto.

## Trocar os arquivos

Basta sobrescrever os MP3 com o mesmo nome — não precisa mexer no código.
Se quiser usar nomes diferentes, ajuste os atributos `data-audio` (no
`index.php`, na div `.hero`) e `data-audio-rolagem` (no `rolagem/index.php`,
no formulário `#form-rolagem`).
