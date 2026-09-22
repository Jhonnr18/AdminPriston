<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\SkillFileRepository;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SkillController extends Controller
{
    public function __construct(
        private readonly SkillFileRepository $skills,
    ) {}

    public function index(Request $request): View
    {
        $page = $this->skills->page(
            $request->string('file')->toString() ?: null,
            $request->string('key')->toString() ?: null,
        );

        return view('admin.skills', [
            ...$page,
            'pageTitle' => 'Skills',
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'file' => ['required', 'string'],
            'key' => ['required', 'string'],
            'values' => ['required', 'array', 'size:10'],
            'values.*' => ['required', 'numeric'],
        ]);

        try {
            $this->skills->save(
                $data['file'],
                $data['key'],
                $data['values'],
                'operador',
                $request->ip(),
            );
        } catch (DomainException $e) {
            return back()->withErrors(['skill' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('skills', ['file' => $data['file'], 'key' => $data['key']])
            ->with('status', 'Skill atualizada. Backup criado.');
    }
}
