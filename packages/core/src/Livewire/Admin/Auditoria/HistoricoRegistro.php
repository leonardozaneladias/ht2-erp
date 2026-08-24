<?php

declare(strict_types=1);

namespace HT2ML\Core\Livewire\Admin\Auditoria;

use HT2ML\Core\Models\Activity;
use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Support\Audit\FormatadorDiff;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Timeline de auditoria de UM registro (trilha do subject) — componente
 * reutilizável: embuta em qualquer formulário de edição com
 * `<livewire:admin.auditoria.historico-registro :subject-type="Model::class" :subject-id="$id" />`.
 *
 * Respeita o isolamento por empresa (Activity::visiveisPara) e a permissão
 * `auditoria.visualizar`. Lazy: carrega após o conteúdo principal, com skeleton.
 */
#[Lazy]
class HistoricoRegistro extends Component
{
    use WithPagination;

    /** @var class-string */
    #[Locked]
    public string $subjectType;

    #[Locked]
    public int $subjectId;

    /** Sem moldura própria (card/cabeçalho): para embutir dentro de um x-shared.tab-body. */
    #[Locked]
    public bool $bare = false;

    public function mount(string $subjectType, int $subjectId, bool $bare = false): void
    {
        $user = Auth::guard('admin')->user();

        if ($user === null || ! $user->can('auditoria.visualizar')) {
            throw new AuthorizationException('Acesso negado.');
        }

        $this->subjectType = $subjectType;
        $this->subjectId = $subjectId;
        $this->bare = $bare;
    }

    public function placeholder(): View
    {
        return view('livewire.admin.placeholders.skeleton-table');
    }

    /**
     * @return list<array{campo: string, antes: ?string, depois: ?string}>
     */
    public function mudancasDe(Activity $activity): array
    {
        return FormatadorDiff::linhas($activity);
    }

    public function render(): View
    {
        $user = Auth::guard('admin')->user();

        assert($user instanceof AdminUser);

        return view('livewire.admin.auditoria.historico-registro', [
            'atividades' => Activity::query()
                ->visiveisPara($user)
                ->where('subject_type', $this->subjectType)
                ->where('subject_id', $this->subjectId)
                ->with('causer')
                ->latest('id')
                ->paginate(10, pageName: 'historicoPage'),
        ]);
    }
}
