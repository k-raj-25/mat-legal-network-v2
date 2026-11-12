<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** Admin-only: blog editor (author) accounts. Editors can sign in and manage only their own blog posts. */
class EditorController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('manageEditors', User::class);

        $q = User::where('role', Role::Editor->value)->withCount([
            'posts',
            'posts as published_count' => fn ($p) => $p->where('status', 'published'),
        ]);
        if ($term = trim((string) $request->query('q'))) {
            $like = '%'.addcslashes(mb_substr($term, 0, 100), '%_\\').'%';
            $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('email', 'like', $like));
        }

        return view('admin.editors.index', ['editors' => $q->orderBy('name')->paginate(25)->withQueryString()]);
    }

    public function create()
    {
        Gate::authorize('manageEditors', User::class);

        return view('admin.editors.form', ['editor' => new User]);
    }

    public function store(Request $request)
    {
        Gate::authorize('manageEditors', User::class);
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $editor = new User(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password']]);
        $editor->forceFill(['role' => Role::Editor, 'is_active' => true, 'email_verified_at' => now()])->save();
        AuditLog::record('editor.created', $editor, ['editor' => $editor->email]);

        return redirect()->route('admin.editors.edit', $editor)
            ->with('status', 'Editor account created. Share the email and password with '.$editor->name.' securely; they can change the password after signing in.');
    }

    public function edit(User $editor)
    {
        Gate::authorize('updateEditor', $editor);
        $editor->loadCount(['posts', 'posts as published_count' => fn ($p) => $p->where('status', 'published')]);

        return view('admin.editors.form', ['editor' => $editor]);
    }

    public function update(Request $request, User $editor)
    {
        Gate::authorize('updateEditor', $editor);
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($editor->id)],
        ]);
        $editor->fill($data)->save();
        AuditLog::record('editor.updated', $editor, ['editor' => $editor->email]);

        return back()->with('status', 'Editor details saved.');
    }

    public function password(Request $request, User $editor)
    {
        Gate::authorize('updateEditor', $editor);
        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);
        $editor->forceFill(['password' => $data['password'], 'remember_token' => Str::random(60)])->save();
        $this->endSessions($editor);
        AuditLog::record('editor.password_reset', $editor, ['editor' => $editor->email]);

        return back()->with('status', 'Password reset. '.$editor->name.' has been signed out everywhere and must use the new password.');
    }

    public function toggle(User $editor)
    {
        Gate::authorize('updateEditor', $editor);
        $editor->is_active = ! $editor->is_active;
        if (! $editor->is_active) {
            $editor->remember_token = Str::random(60);
        }
        $editor->save();
        if (! $editor->is_active) {
            $this->endSessions($editor);
        }
        AuditLog::record($editor->is_active ? 'editor.activated' : 'editor.deactivated', $editor, ['editor' => $editor->email]);

        return back()->with('status', $editor->is_active
            ? 'Account activated. '.$editor->name.' can sign in again.'
            : 'Account deactivated. '.$editor->name.' can no longer sign in. Their published posts stay on the blog.');
    }

    /** Sign the editor out of every device (database sessions only; other drivers expire on their own). */
    private function endSessions(User $user): void
    {
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }
    }
}
