<?php

declare(strict_types=1);

use App\Support\Generator\CampoModulo;

it('faz parse de aba(...) preservando o rótulo (case e acentos)', function (): void {
    $campo = CampoModulo::deToken('email:email:aba(Contato)');

    expect($campo->nome)->toBe('email')
        ->and($campo->tipo)->toBe('email')
        ->and($campo->aba)->toBe('Contato');
});

it('combina nullable + unique + aba em qualquer ordem', function (): void {
    $campo = CampoModulo::deToken('slug:string:unique:aba(Identificação):nullable');

    expect($campo->unique)->toBeTrue()
        ->and($campo->nullable)->toBeTrue()
        ->and($campo->aba)->toBe('Identificação');
});

it('aceita rótulo de aba com espaços e símbolos e gera slug', function (): void {
    $campo = CampoModulo::deToken('imagem:image:aba(Datas & Mídia)');

    expect($campo->aba)->toBe('Datas & Mídia')
        ->and($campo->abaSlug())->toBe('datas-midia');
});

it('mantém aba nula quando não declarada', function (): void {
    expect(CampoModulo::deToken('nome:string')->aba)->toBeNull();
});

it('continua parseando enum(a|b|c)', function (): void {
    $campo = CampoModulo::deToken('status:enum(rascunho|publicado|arquivado)');

    expect($campo->tipo)->toBe('enum')
        ->and($campo->enumValores)->toBe(['rascunho', 'publicado', 'arquivado']);
});

it('mapeia o tipo url (regra url + input type url)', function (): void {
    $campo = CampoModulo::deToken('site:url:nullable');

    expect($campo->regras())->toContain("'url'")
        ->and($campo->componenteBlade())->toContain('type="url"')
        ->and($campo->valorTeste())->toBe("'https://exemplo.com'");
});

it('mapeia o tipo decimal (coluna decimal + cast decimal:2)', function (): void {
    $campo = CampoModulo::deToken('custo:decimal');

    expect($campo->colunaMigration())->toContain("decimal('custo', 10, 2)")
        ->and($campo->castModel())->toBe("'custo' => 'decimal:2'")
        ->and($campo->componenteBlade())->toContain('step="0.01"')
        ->and($campo->regras())->toContain("'numeric'");
});

it('mapeia o tipo color (string(9) + color-picker)', function (): void {
    $campo = CampoModulo::deToken('cor:color:nullable');

    expect($campo->colunaMigration())->toContain("string('cor', 9)")
        ->and($campo->componenteBlade())->toContain('x-shared.color-picker')
        ->and($campo->componenteBlade())->toContain('clearable');
});

it('enum não-status vira select-search (pesquisável) com options e Rule::in', function (): void {
    $campo = CampoModulo::deToken('categoria:enum(servico|produto|assinatura)');

    expect($campo->componenteBlade())->toContain('x-shared.select-search')
        ->and($campo->componenteBlade())->toContain("'servico' => 'Servico'")
        ->and($campo->regras())->toContain("Rule::in(['servico', 'produto', 'assinatura'])")
        ->and($campo->valorTeste())->toBe("'servico'")
        ->and($campo->usaRuleNaValidacao())->toBeTrue();
});

it('mapeia o tipo multiselect (json + cast array + select-search multiple)', function (): void {
    $campo = CampoModulo::deToken('tags:multiselect(vip|novo|promo):nullable');

    expect($campo->tipo)->toBe('multiselect')
        ->and($campo->colunaMigration())->toContain("json('tags')")
        ->and($campo->castModel())->toBe("'tags' => 'array'")
        ->and($campo->tipoLivewire())->toBe('array')
        ->and($campo->defaultLivewire())->toBe('[]')
        ->and($campo->componenteBlade())->toContain('x-shared.select-search')
        ->and($campo->componenteBlade())->toContain('multiple')
        ->and($campo->valorTeste())->toBe("['vip']");
});

it('mapeia o tipo richtext (coluna text + rich-editor)', function (): void {
    $campo = CampoModulo::deToken('descricao:richtext:nullable');

    expect($campo->colunaMigration())->toContain("text('descricao')")
        ->and($campo->componenteBlade())->toContain('x-shared.rich-editor');
});
