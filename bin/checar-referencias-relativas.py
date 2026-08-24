r"""Acha referência relativa que não resolve — a armadilha das mudanças de namespace.

Uma classe em App\Models referencia um irmão SEM qualificar: mesmo namespace,
nenhum `use`. Quando ela muda para HT2ML\Core\Models, essa referência passa a
apontar para o namespace novo, onde o irmão não está — e nenhuma busca por
"App\" encontra o problema, porque a string "App\" nunca aparece no arquivo.

Foi assim que AdminUser::permissionGrants() quebrou ao ir para o pacote, e
foi assim que a EmpresaPolicy deixou de ser aplicada. Rode isto depois de
qualquer movimentação de classe entre namespaces:

    python3 bin/checar-referencias-relativas.py .

Sai com código 1 se achar alguma coisa.
"""
import pathlib, re, collections, sys

raiz = pathlib.Path(sys.argv[1] if len(sys.argv) > 1 else '.')
BASES = (
    'app', 'database/seeders', 'database/factories',
    'packages/core/src', 'packages/core/database/seeders', 'packages/core/database/factories',
    'packages/extensao-rh/src', 'packages/extensao-fiscal-br/src',
    'packages/extensao-fiscal-br/database/seeders',
)

conhecidas = set()
arquivos = []
for base in BASES:
    if not (raiz / base).is_dir():
        continue
    for p in (raiz / base).rglob('*.php'):
        t = p.read_text(encoding='utf-8')
        ns = re.search(r'^namespace\s+([^;]+);', t, re.M)
        cl = re.search(r'^(?:final |abstract |readonly )*(?:class|interface|trait|enum)\s+(\w+)', t, re.M)
        if ns and cl:
            conhecidas.add(f'{ns.group(1)}\\{cl.group(1)}')
        if ns:
            arquivos.append((p, ns.group(1), t))

problemas = []
for p, ns, t in arquivos:
    aliases = set()
    for m in re.finditer(r'^use\s+(?:function\s+)?([A-Za-z0-9_\\]+)(?:\s+as\s+(\w+))?;', t, re.M):
        aliases.add(m.group(2) or m.group(1).rsplit('\\', 1)[-1])
    corpo = re.sub(r'^use .*$', '', t, flags=re.M)
    # nome relativo COMPLETO antes de ::class ou new
    for m in set(re.findall(r'(?<![\\\w])((?:[A-Z]\w*\\)*[A-Z]\w*)::class', corpo)) | \
             set(re.findall(r'\bnew\s+((?:[A-Z]\w*\\)*[A-Z]\w*)\s*\(', corpo)):
        primeiro = m.split('\\')[0]
        if primeiro in aliases:
            continue                      # importado (ou prefixo importado)
        alvo = f'{ns}\\{m}'
        if alvo in conhecidas:
            continue                      # resolve no próprio namespace
        # só reporta se o nome existe em outro namespace nosso — aí é quebra real
        iguais = [k for k in conhecidas if k.endswith('\\' + m)]
        if iguais:
            problemas.append((p.relative_to(raiz), m, iguais[0]))

print(f'══ {len(problemas)} referência(s) relativa(s) que não resolvem ══')
for arq, rel, real in sorted(problemas):
    print(f'  {arq}\n      {rel}  →  está em {real}')
sys.exit(1 if problemas else 0)
