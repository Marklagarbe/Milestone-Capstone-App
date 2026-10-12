@extends('layouts.app')
@section('title', 'Edit project')
@section('content')
<div class="page-head"><div><h1>Edit Project</h1><p>{{ $project->title }}</p></div></div>
<div class="card" style="max-width:700px">
    <form method="POST" action="{{ route('projects.update', $project) }}">
        @csrf @method('PUT')
        @include('projects._form', ['mode' => 'edit'])
        <div class="actions"><button class="btn btn-primary" type="submit">Save changes</button><a class="btn" href="{{ route('projects.show', $project) }}">Cancel</a></div>
    </form>
</div>
@endsection
