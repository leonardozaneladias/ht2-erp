<?php

declare(strict_types=1);

namespace HT2ML\Ia;

use HT2ML\Ia\Contracts\TradutorInterface;
use HT2ML\Ia\Models\TraducaoEmCache;
use HT2ML\Ia\Support\TextoParaIa;

/**
 * Tradução com cache por hash do conteúdo: o mesmo texto nunca é traduzido
 * duas vezes para o mesmo idioma, nem entre empresas diferentes.
 *
 * Recebe primitivos, nunca Request — o produto chama daqui o que for preciso
 * (um comunicado, um produto, uma mensagem) montando os textos ele mesmo.
 */
final class TraducaoService
{
    public function __construct(private readonly TradutorInterface $tradutor) {}

    /**
     * @return array{texto: string, cache: bool}
     */
    public function traduzir(string $texto, string $idiomaDestino, string $idiomaOrigem = 'pt', ?int $empresaId = null): array
    {
        // O teto vale também aqui: o cache poupa a SEGUNDA chamada, mas a
        // primeira de um texto enorme custaria igual.
        $texto = TextoParaIa::limitar($texto);
        $hash = hash('sha256', $texto);

        $emCache = TraducaoEmCache::query()
            ->where('hash_conteudo', $hash)
            ->where('idioma_destino', $idiomaDestino)
            ->first();

        if ($emCache !== null) {
            return ['texto' => $emCache->texto_traduzido, 'cache' => true];
        }

        $resultado = $this->tradutor->traduzir($texto, $idiomaOrigem, $idiomaDestino);

        TraducaoEmCache::query()->create([
            'empresa_id' => $empresaId,
            'hash_conteudo' => $hash,
            'idioma_origem' => $idiomaOrigem,
            'idioma_destino' => $idiomaDestino,
            'texto_traduzido' => $resultado['texto'],
            'provedor' => $this->tradutor->identificador(),
        ]);

        return ['texto' => $resultado['texto'], 'cache' => false];
    }
}
