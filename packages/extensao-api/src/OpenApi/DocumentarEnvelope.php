<?php

declare(strict_types=1);

namespace HT2ML\Api\OpenApi;

use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\BooleanType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\Generator\Types\Type;

/**
 * Faz o OpenAPI descrever o erro que a API devolve de verdade.
 *
 * O Scramble documenta as respostas de erro no formato padrão do Laravel —
 * `{message}` e `{message, errors}` —, mas o RenderizadorDeErroApi responde
 * `{success: false, message, data: {}, errors}`. Sem este ajuste, o portão que
 * compara o openapi.json com o gerado garante que o arquivo está sincronizado
 * com o gerador, e não que o contrato está certo: um cliente gerado a partir
 * dele quebraria no primeiro 401.
 */
final class DocumentarEnvelope
{
    public function __invoke(OpenApi $openApi): void
    {
        foreach ($openApi->components->responses as $resposta) {
            if (! $resposta instanceof Response || (int) $resposta->code < 400) {
                continue;
            }

            $resposta->setContent('application/json', Schema::fromType(self::envelopeDeErro($resposta)));
        }
    }

    private static function envelopeDeErro(Response $resposta): ObjectType
    {
        $errors = self::propriedadeOriginal($resposta, 'errors')
            ?? (new ArrayType)->setItems(new StringType);

        return (new ObjectType)
            ->addProperty('success', (new BooleanType)->setDescription('Sempre false numa resposta de erro.'))
            ->addProperty('message', (new StringType)->setDescription('Mensagem para o usuário, no idioma pedido.'))
            ->addProperty('data', (new ObjectType)->setDescription('Sempre um objeto vazio numa resposta de erro.'))
            ->addProperty('errors', $errors->setDescription('Detalhe por campo (422) ou lista vazia.'))
            ->setRequired(['success', 'message', 'data', 'errors']);
    }

    /** O schema que o Scramble já tinha inferido para um campo — o `errors` de validação, por exemplo. */
    private static function propriedadeOriginal(Response $resposta, string $nome): ?Type
    {
        $conteudo = $resposta->content['application/json'] ?? null;

        $tipo = $conteudo instanceof Schema ? $conteudo->type : null;

        return $tipo instanceof ObjectType && $tipo->hasProperty($nome) ? $tipo->getProperty($nome) : null;
    }
}
