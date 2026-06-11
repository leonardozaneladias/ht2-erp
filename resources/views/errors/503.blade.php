@extends ('errors.layout')

@section ('codigo', '503')
@section ('titulo', 'Em manutenção')
@section ('mensagem')
    O sistema está em manutenção programada e volta em breve. Tente novamente em alguns minutos.
@endsection

@section ('acoes')
    {{-- Sem link durante manutenção: toda rota retornaria 503. --}}
@endsection
