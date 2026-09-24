<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\SkillFileRepository;
use App\Repositories\SkillSqlRepository;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SkillController extends Controller
{
    public function __construct(
        private readonly SkillFileRepository $skills,
        private readonly SkillSqlRepository $sqlSkills,
    ) {}

    public function sql(Request $request): View
    {
        $code = $request->integer('skill');

        return view('admin.skills-sql', [
            'skills' => $this->sqlSkills->definitions(),
            'selected' => $code > 0 ? $code : null,
            'parameters' => $code > 0 ? $this->sqlSkills->parameters($code) : [],
            'pageTitle' => 'Skills SQL',
        ]);
    }

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
            'reason' => ['required', 'string', 'min:5', 'max:500'],
            'values' => ['required', 'array', 'size:10'],
            'values.*' => ['required', 'numeric'],
        ]);

        try {
            $this->skills->save(
                $data['file'],
                $data['key'],
                $data['values'],
                auth()->user()->name,
                $request->ip(),
                $data['reason'],
            );
        } catch (DomainException $e) {
            return back()->withErrors(['skill' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('skills', ['file' => $data['file'], 'key' => $data['key']])
            ->with('status', 'Skill atualizada. Backup criado.');
    }

    public function saveSql(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'skill_code' => ['required', 'integer', 'min:1'],
            'parameter' => ['required', 'string', 'max:64'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
            'values' => ['required', 'array', 'size:10'],
            'values.*' => ['required', 'numeric'],
        ]);

        try {
            $this->sqlSkills->updateParameter(
                (int) $data['skill_code'],
                $data['parameter'],
                $data['values'],
                auth()->user()->name,
                $request->ip(),
                $data['reason'],
            );
        } catch (DomainException $e) {
            return back()->withErrors(['skill_sql' => $e->getMessage()])->withInput();
        }

        return redirect()->route('skills.sql', ['skill' => $data['skill_code']])
            ->with('status', 'Parâmetro SQL atualizado e versionado.');
    }

    public function saveCooldown(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'skill_code' => ['required', 'integer', 'min:1'],
            'level' => ['required', 'integer', 'between:1,10'],
            'cooldown_ms' => ['required', 'integer', 'between:0,600000'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        try {
            $this->sqlSkills->updateCooldown(
                (int) $data['skill_code'],
                (int) $data['level'],
                (int) $data['cooldown_ms'],
                auth()->user()->name,
                $request->ip(),
                $data['reason'],
            );
        } catch (DomainException $e) {
            return back()->withErrors(['skill_sql' => $e->getMessage()])->withInput();
        }

        return redirect()->route('skills.sql', ['skill' => $data['skill_code']])
            ->with('status', 'Cooldown atualizado e versionado.');
    }
}
