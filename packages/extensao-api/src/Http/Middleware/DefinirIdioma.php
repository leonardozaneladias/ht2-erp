<?php

declare(strict_types=1);

namespace HT2ML\Api\Http\Middleware;

use Closure;
use HT2ML\Api\Contracts\TemIdiomaPreferido;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Idioma da resposta da API. Ordem: `Accept-Language` → a preferência salva do
 * usuário (TemIdiomaPreferido) → o default da aplicação.
 *
 * O header ganha porque descreve a requisição concreta: é o que o app manda
 * explicitamente, e pode mandar a preferência salva ali. A preferência existe
 * para quando o header falta — e para o que não tem requisição nenhuma.
 */
final class DefinirIdioma
{
    /**
     * Os idiomas que este middleware entende, como o cliente os manda — fonte
     * única. Uma validação de "idioma preferido" deve usar esta lista: duas
     * listas divergiriam, e a preferência seria aceita e ignorada.
     *
     * @return list<string>
     */
    public static function idiomasSuportados(): array
    {
        return array_keys(self::mapa());
    }

    public function handle(Request $request, Closure $next): Response
    {
        $idioma = self::idiomaDoHeader($request) ?? $this->doUsuario($request);

        if ($idioma !== null) {
            app()->setLocale($idioma);
        }

        return $next($request);
    }

    /**
     * O locale pedido no header, sem depender do pipeline.
     *
     * Público porque nem toda resposta de API passa por este middleware: um 405
     * (rota existe, método não) e um 404 de rota inexistente são lançados pelo
     * roteador ANTES do grupo `api`. O RenderizadorDeErroApi chama este método
     * para que esses dois também saiam no idioma pedido. Reaplicar é seguro: o
     * header é a fonte de maior precedência, então o resultado é o mesmo.
     */
    public static function idiomaDoHeader(Request $request): ?string
    {
        // `Accept-Language` real vem como `es-AR,es;q=0.9,en;q=0.8` — só a
        // primeira etiqueta interessa, e dela basta o prefixo do idioma.
        $bruto = trim(explode(',', (string) $request->header('Accept-Language', ''))[0]);
        $etiqueta = trim(explode(';', $bruto)[0]);

        return self::traduzir($etiqueta) ?? self::traduzir(substr($etiqueta, 0, 2));
    }

    /**
     * Guard explícito: este middleware roda no grupo `api`, ANTES do
     * `auth:sanctum` declarado na rota — `$request->user()` sem argumento
     * consultaria o guard default e viria sempre nulo. Com `sanctum`, o Bearer
     * token é resolvido na hora.
     */
    private function doUsuario(Request $request): ?string
    {
        $usuario = $request->user('sanctum');

        return $usuario instanceof TemIdiomaPreferido
            ? self::traduzir((string) $usuario->idiomaPreferido())
            : null;
    }

    private static function traduzir(string $codigo): ?string
    {
        $locale = self::mapa()[$codigo] ?? null;

        return is_string($locale) ? $locale : null;
    }

    /** @return array<string, string> */
    private static function mapa(): array
    {
        /** @var array<string, string> $mapa */
        $mapa = (array) config('api.idiomas', []);

        return $mapa;
    }
}
