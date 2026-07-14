/**
 * Dígito verificador de CPF, CNPJ e PIS/PASEP no cliente.
 *
 * Isto NÃO é regra de negócio duplicada: o DV é um checksum público e imutável (o
 * algoritmo do CPF não muda com a legislação). O servidor — App\Rules\{Cpf,Cnpj,Pis} —
 * continua sendo a autoridade; aqui só antecipamos o veredito para que um documento
 * impossível (111.111.111-11, ou um dígito trocado) não custe um round-trip.
 *
 * A garantia de que as duas implementações não divergem é o fixture compartilhado
 * `tests/Fixtures/documentos-dv.json`: ele é rodado contra as Rules do PHP
 * (tests/Feature/Componentes/ValidadoresDvTest.php) e contra estas funções no browser
 * (tests/Browser/Admin/ValidadoresDvTest.php). Qualquer divergência vira teste vermelho.
 */

/** Só os dígitos, na ordem em que foram digitados. */
function digitos(valor) {
  return String(valor ?? '').replace(/\D/g, '');
}

/** Todos os dígitos iguais (111.111.111-11) passa no DV, mas não é documento. */
function todosIguais(numero) {
  return /^(\d)\1*$/.test(numero);
}

/** Dígito verificador módulo 11 na convenção brasileira: resto < 2 → 0. */
function digitoModulo11(numero, pesos) {
  const soma = pesos.reduce((acc, peso, i) => acc + Number(numero[i]) * peso, 0);
  const resto = soma % 11;

  return resto < 2 ? 0 : 11 - resto;
}

export function validarCpf(valor) {
  const cpf = digitos(valor);

  if (cpf.length !== 11 || todosIguais(cpf)) {
    return false;
  }

  const pesos1 = [10, 9, 8, 7, 6, 5, 4, 3, 2];
  const pesos2 = [11, 10, 9, 8, 7, 6, 5, 4, 3, 2];

  return Number(cpf[9]) === digitoModulo11(cpf, pesos1) && Number(cpf[10]) === digitoModulo11(cpf, pesos2);
}

export function validarCnpj(valor) {
  const cnpj = digitos(valor);

  if (cnpj.length !== 14 || todosIguais(cnpj)) {
    return false;
  }

  const pesos1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
  const pesos2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

  return Number(cnpj[12]) === digitoModulo11(cnpj, pesos1) && Number(cnpj[13]) === digitoModulo11(cnpj, pesos2);
}

export function validarPis(valor) {
  const pis = digitos(valor);

  if (pis.length !== 11 || todosIguais(pis)) {
    return false;
  }

  const pesos = [3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

  return Number(pis[10]) === digitoModulo11(pis, pesos);
}

/**
 * Título de eleitor: 8 sequenciais + 2 de UF (01–28) + 2 DVs (módulo 11; resto 10 → 0;
 * resto 0 em SP/MG → 1). Par PHP: App\Rules\TituloEleitor (contrato no fixture).
 */
export function validarTituloEleitor(valor) {
  const titulo = digitos(valor);

  if (titulo.length !== 12 || todosIguais(titulo)) {
    return false;
  }

  const uf = Number(titulo.slice(8, 10));

  if (uf < 1 || uf > 28) {
    return false;
  }

  const spMg = uf === 1 || uf === 2;
  const digito = (soma) => {
    const resto = soma % 11;

    if (resto === 10) {
      return 0;
    }

    return resto === 0 && spMg ? 1 : resto;
  };

  let soma1 = 0;

  for (let i = 0; i < 8; i++) {
    soma1 += Number(titulo[i]) * (i + 2);
  }

  const dv1 = digito(soma1);

  if (Number(titulo[10]) !== dv1) {
    return false;
  }

  const soma2 = Number(titulo[8]) * 7 + Number(titulo[9]) * 8 + dv1 * 9;

  return Number(titulo[11]) === digito(soma2);
}

/** Telefone BR com DDD: 10 dígitos (fixo) ou 11 (celular). Não é DV — só gate de comprimento. */
export function validarTelefone(valor) {
  const tel = digitos(valor);

  return tel.length === 10 || tel.length === 11;
}

const validadores = {
  cpf: { valida: validarCpf, rotulo: 'CPF' },
  cnpj: { valida: validarCnpj, rotulo: 'CNPJ' },
  pis: { valida: validarPis, rotulo: 'PIS/PASEP' },
  telefone: { valida: validarTelefone, rotulo: 'telefone' },
  titulo_eleitor: { valida: validarTituloEleitor, rotulo: 'título de eleitor' },
};

/**
 * Campos inválidos entre os `[data-af-validate]` de um root.
 *
 * Campo vazio NÃO é inválido aqui: obrigatoriedade é outra regra (e é do servidor).
 * Só julgamos o que foi preenchido.
 *
 * @returns {Array<{elemento: HTMLElement, mensagem: string}>}
 */
export function documentosInvalidos(root = document) {
  const campos = Array.from(root.querySelectorAll('[data-af-validate]'));

  return campos.reduce((invalidos, campo) => {
    const validador = validadores[campo.dataset.afValidate];
    const valor = digitos(campo.value);

    if (!validador || valor === '') {
      return invalidos;
    }

    if (!validador.valida(valor)) {
      invalidos.push({
        elemento: campo,
        mensagem: `O ${validador.rotulo} informado não é válido.`,
      });
    }

    return invalidos;
  }, []);
}

// Exposto no window para os testes de browser rodarem o mesmo fixture do PHP.
window.afValidators = {
  validarCpf,
  validarCnpj,
  validarPis,
  validarTelefone,
  validarTituloEleitor,
  documentosInvalidos,
};
