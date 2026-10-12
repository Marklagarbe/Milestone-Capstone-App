<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Alert;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Feedback;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    private const FILE_RULES = ['required', 'file', 'max:20480', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,zip,png,jpg,jpeg,txt'];

    public function index(Request $request)
    {
        $user = $request->user();
        $projects = Project::visibleTo($user)->with('milestones')->orderBy('title')->get();
        $ids = $projects->pluck('id');
        $view = $request->get('view', 'all');

        $query = Document::whereIn('project_id', $ids)->with(['project', 'uploader', 'latestVersion']);

        if ($view === 'trash') {
            $query->onlyTrashed();
        }
        if ($view === 'favorites') {
            $query->whereHas('favoritedBy', fn ($f) => $f->where('users.id', $user->id));
        }
        if ($view === 'mine') {
            $query->where('uploaded_by', $user->id);
        }

        $query->when($request->category, fn ($q, $v) => $q->where('category', $v))
            ->when($request->project, fn ($q, $v) => $q->where('project_id', $v))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->q, fn ($q, $v) => $q->where(fn ($w) => $w
                ->where('title', 'like', "%{$v}%")
                ->orWhereHas('versions', fn ($ver) => $ver->where('original_name', 'like', "%{$v}%"))));

        return view('documents.index', [
            'documents' => $query->latest('updated_at')->paginate(10)->withQueryString(),
            'projects' => $projects,
            'view' => $view,
            'folders' => Document::whereIn('project_id', $ids)->selectRaw('category, count(*) as total')->groupBy('category')->pluck('total', 'category'),
            'favoriteIds' => DB::table('document_favorites')->where('user_id', $user->id)->pluck('document_id')->all(),
            'trashCount' => Document::onlyTrashed()->whereIn('project_id', $ids)->count(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:200'],
            'category' => ['required', 'in:'.implode(',', array_keys(Document::CATEGORIES))],
            'milestone_id' => ['nullable', 'exists:milestones,id'],
            'description' => ['nullable', 'string', 'max:2000'],
            'file' => self::FILE_RULES,
        ]);

        $project = $this->access(Project::findOrFail($data['project_id']));
        if (! empty($data['milestone_id'])) {
            abort_unless($project->milestones()->whereKey($data['milestone_id'])->exists(), 422);
        }

        $document = Document::create([
            'project_id' => $project->id,
            'milestone_id' => $data['milestone_id'] ?? null,
            'title' => $data['title'],
            'category' => $data['category'],
            'description' => $data['description'] ?? null,
            'status' => $request->boolean('submit_now') ? 'submitted' : 'draft',
            'uploaded_by' => $request->user()->id,
        ]);

        $this->saveVersion($document, $request->file('file'), 'First upload');
        Activity::log($project, 'uploaded "'.$document->title.'" (v1)', 'document');

        if ($document->status === 'submitted') {
            $this->notifyFaculty($project, $document);
        }

        return redirect()->route('documents.show', $document)->with('success', 'File uploaded.');
    }

    public function show(Document $document)
    {
        $project = $this->access($document->project);
        $user = auth()->user();
        $document->load(['versions.uploader', 'uploader', 'milestone', 'feedback.author']);

        return view('documents.show', [
            'document' => $document,
            'project' => $project,
            'canReview' => $project->isFacultyFor($user),
            'canEdit' => $document->uploaded_by === $user->id || $project->canManage($user),
            'isFavorite' => $document->isFavoriteFor($user),
        ]);
    }

    public function storeVersion(Request $request, Document $document)
    {
        $project = $this->access($document->project);
        $request->validate(['file' => self::FILE_RULES, 'notes' => ['nullable', 'string', 'max:1000']]);
        $this->canEdit($document, $project);

        $version = $this->saveVersion($document, $request->file('file'), $request->notes);
        $document->update(['status' => $request->boolean('submit_now') ? 'submitted' : 'draft']);
        Activity::log($project, 'uploaded version '.$version->version.' of "'.$document->title.'"', 'document');

        if ($document->status === 'submitted') {
            $this->notifyFaculty($project, $document);
        }

        return back()->with('success', 'Version '.$version->version.' uploaded.');
    }

    public function download(DocumentVersion $version)
    {
        $document = $version->document;
        $this->access($document->project);
        abort_unless(Storage::disk('local')->exists($version->file_path), 404, 'The file is missing from storage.');

        return Storage::disk('local')->download($version->file_path, $version->original_name);
    }

    public function favorite(Request $request, Document $document)
    {
        $this->access($document->project);
        $document->favoritedBy()->toggle($request->user()->id);

        return back();
    }

    public function submit(Document $document)
    {
        $project = $this->access($document->project);
        $this->canEdit($document, $project);
        $document->update(['status' => 'submitted']);
        Activity::log($project, 'submitted "'.$document->title.'" for review', 'document');
        $this->notifyFaculty($project, $document);

        return back()->with('success', 'Submitted for review.');
    }

    public function review(Request $request, Document $document)
    {
        $project = $this->faculty($this->access($document->project));
        $data = $request->validate([
            'decision' => ['required', 'in:approved,revision'],
            'body' => ['nullable', 'string', 'max:2000', 'required_if:decision,revision'],
        ], ['body.required_if' => 'Please explain what needs to be revised.']);

        $document->update(['status' => $data['decision']]);

        Feedback::create([
            'project_id' => $project->id,
            'milestone_id' => $document->milestone_id,
            'document_id' => $document->id,
            'user_id' => $request->user()->id,
            'body' => $data['body'] ?: 'Approved.',
            'decision' => $data['decision'],
        ]);

        Activity::log($project, ($data['decision'] === 'approved' ? 'approved' : 'requested revisions on').' "'.$document->title.'"', 'document');
        Alert::notify($project->participants(), $data['decision'] === 'approved' ? 'Document approved' : 'Revision requested',
            $document->title, route('documents.show', $document), 'feedback');

        return back()->with('success', 'Review saved.');
    }

    public function destroy(Document $document)
    {
        $this->canEdit($document, $this->access($document->project));
        $document->delete();

        return redirect()->route('documents.index')->with('success', 'Moved to trash.');
    }

    public function restore(Document $document)
    {
        $this->canEdit($document, $this->access($document->project));
        $document->restore();

        return redirect()->route('documents.index', ['view' => 'trash'])->with('success', 'File restored.');
    }

    public function forceDelete(Document $document)
    {
        $this->canEdit($document, $this->access($document->project));
        Storage::disk('local')->delete($document->versions()->pluck('file_path')->all());
        $document->forceDelete();

        return redirect()->route('documents.index', ['view' => 'trash'])->with('success', 'File permanently deleted.');
    }

    private function saveVersion(Document $document, $file, ?string $notes): DocumentVersion
    {
        $path = $file->store('documents/'.$document->project_id, 'local');

        return $document->versions()->create([
            'version' => ($document->versions()->max('version') ?? 0) + 1,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'notes' => $notes,
            'uploaded_by' => auth()->id(),
        ]);
    }

    private function canEdit(Document $document, Project $project): void
    {
        $user = auth()->user();
        abort_unless($document->uploaded_by === $user->id || $project->canManage($user), 403, 'Only the uploader, group leader or faculty can change this file.');
    }

    private function notifyFaculty(Project $project, Document $document): void
    {
        Alert::notify([$project->group->instructor, $project->group->adviser], 'Document submitted for review',
            $project->title.': '.$document->title, route('documents.show', $document), 'document');
    }
}
