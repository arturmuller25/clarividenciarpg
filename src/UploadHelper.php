<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

/**
 * Helper de upload seguro de imagens.
 *
 * Defesas em camadas:
 *  - Whitelist de subpastas (campanhas/agentes/npcs/criaturas).
 *  - Whitelist de MIME via finfo (NÃO via $_FILES['type'], que vem do cliente).
 *  - Whitelist de extensão final (jpg/png/webp).
 *  - Limite de tamanho (4 MB).
 *  - Nome final aleatório (random_bytes) — impossível adivinhar.
 *  - Path traversal bloqueado (basename() comparado ao próprio nome).
 *  - Pasta /uploads tem .htaccess bloqueando execução PHP/CGI.
 */
final class UploadHelper
{
    public const TAMANHO_MAX  = 4 * 1024 * 1024;
    public const SUBPASTAS_OK = ['campanhas', 'agentes', 'npcs', 'criaturas'];
    public const MIMES_OK     = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * Valida e move o arquivo enviado. Retorna o nome final salvo (apenas o
     * basename, sem subpasta) ou null se nenhum arquivo foi enviado.
     *
     * @param array<string, mixed>|null $arquivo Item de $_FILES.
     * @param string $subpasta Categoria (deve estar em SUBPASTAS_OK).
     * @throws RuntimeException Se o arquivo for inválido por qualquer motivo.
     */
    public static function moverImagem(?array $arquivo, string $subpasta): ?string
    {
        if (!is_array($arquivo) || !isset($arquivo['error'])) {
            return null;
        }
        if ($arquivo['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($arquivo['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException(
                'Falha no upload (código PHP: ' . (int) $arquivo['error'] . ').'
            );
        }
        if (empty($arquivo['tmp_name']) || !is_uploaded_file($arquivo['tmp_name'])) {
            throw new RuntimeException('Arquivo não chegou via upload HTTP legítimo.');
        }
        if (((int) ($arquivo['size'] ?? 0)) > self::TAMANHO_MAX) {
            throw new RuntimeException(sprintf(
                'Imagem excede o tamanho máximo de %d MB.',
                self::TAMANHO_MAX / (1024 * 1024)
            ));
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($arquivo['tmp_name']);
        if (!is_string($mime) || !isset(self::MIMES_OK[$mime])) {
            throw new RuntimeException(
                'Tipo de imagem não permitido. Use JPEG, PNG ou WebP.'
            );
        }

        $extensao   = self::MIMES_OK[$mime];
        $nomeFinal  = bin2hex(random_bytes(8)) . '.' . $extensao;
        $destinoDir = self::resolverDestino($subpasta);
        $destino    = $destinoDir . DIRECTORY_SEPARATOR . $nomeFinal;

        if (!@move_uploaded_file($arquivo['tmp_name'], $destino)) {
            throw new RuntimeException(
                'Não foi possível salvar a imagem (verifique permissão de escrita em /uploads).'
            );
        }

        return $nomeFinal;
    }

    /**
     * Apaga uma imagem armazenada (ignora silenciosamente se não existir).
     */
    public static function apagarImagem(string $subpasta, ?string $nomeArquivo): void
    {
        if (!is_string($nomeArquivo) || $nomeArquivo === '') {
            return;
        }
        // Path traversal: nome final NÃO pode conter separadores
        if (basename($nomeArquivo) !== $nomeArquivo) {
            return;
        }
        $caminho = self::resolverDestino($subpasta) . DIRECTORY_SEPARATOR . $nomeArquivo;
        if (is_file($caminho)) {
            @unlink($caminho);
        }
    }

    /**
     * Constrói a URL pública de uma imagem armazenada (para usar nos templates).
     * Retorna null se o nome for inválido — o template deve mostrar um placeholder.
     */
    public static function urlImagem(string $subpasta, ?string $nomeArquivo): ?string
    {
        if (!is_string($nomeArquivo) || $nomeArquivo === '') {
            return null;
        }
        if (basename($nomeArquivo) !== $nomeArquivo) {
            return null;
        }
        if (!in_array($subpasta, self::SUBPASTAS_OK, true)) {
            return null;
        }
        return url('/uploads/' . $subpasta . '/' . $nomeArquivo);
    }

    /**
     * Valida a subpasta e garante que o diretório existe.
     */
    private static function resolverDestino(string $subpasta): string
    {
        if (!in_array($subpasta, self::SUBPASTAS_OK, true)) {
            throw new RuntimeException('Subpasta de upload inválida: ' . $subpasta);
        }
        $base = realpath(__DIR__ . '/../uploads');
        if ($base === false) {
            throw new RuntimeException('Pasta /uploads não encontrada — crie-a antes de fazer upload.');
        }
        $dir = $base . DIRECTORY_SEPARATOR . $subpasta;
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0775, true) && !is_dir($dir)) {
                throw new RuntimeException('Não foi possível criar a subpasta: ' . $subpasta);
            }
        }
        return $dir;
    }
}
