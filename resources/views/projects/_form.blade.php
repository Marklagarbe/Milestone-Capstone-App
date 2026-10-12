@if ($mode === 'create')
    <div class="field">
        <label for="group_id">Group</label>
        <select id="group_id" name="group_id" required>
            <option value="">Choose a group</option>
            @foreach ($groups as $g)<option value="{{ $g->id }}" @selected(old('group_id', request('group')) == $g->id)>{{ $g->name }}</option>@endforeach
        </select>
        @if ($groups->isEmpty())<span class="err">You have no group to attach a project to. <a href="{{ route('groups.create') }}">Create a group first.</a></span>@endif
    </div>
@endif
<div class="field"><label for="title">Project title</label><input id="title" type="text" name="title" value="{{ old('title', $project->title) }}" required></div>
<div class="row2">
    <div class="field">
        <label for="type">Type</label>
        <select id="type" name="type"><option value="thesis" @selected(old('type', $project->type) === 'thesis')>Thesis</option><option value="capstone" @selected(old('type', $project->type) === 'capstone')>Capstone</option></select>
    </div>
    <div></div>
</div>
<div class="field"><label for="description">Description</label><textarea id="description" name="description" placeholder="What problem does your project solve?">{{ old('description', $project->description) }}</textarea></div>
<div class="row2">
    <div class="field"><label for="start_date">Start date</label><input id="start_date" type="date" name="start_date" value="{{ old('start_date', optional($project->start_date)->format('Y-m-d')) }}"></div>
    <div class="field"><label for="target_date">Target completion</label><input id="target_date" type="date" name="target_date" value="{{ old('target_date', optional($project->target_date)->format('Y-m-d')) }}"></div>
</div>
@if ($mode === 'create')
    <label class="check" style="margin-bottom:18px">
        <input type="checkbox" name="use_template" value="1" checked>
        <span><b>Use the standard thesis phases</b><br><span class="muted small">Adds Proposal, Chapters 2 and 3, Development, Chapters 4 and 5, and Final Defense with milestones spaced between your start and target dates. You can edit them later.</span></span>
    </label>
@endif
