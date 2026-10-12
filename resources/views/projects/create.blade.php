@extends('layouts.app')
@section('title', 'New project')
@section('content')
<div class="page-head"><div><h1>New Project</h1><p>Start tracking your thesis or capstone from the proposal stage.</p></div></div>
<div class="card" style="max-width:700px">
    <form method="POST" action="{{ route('projects.store') }}">
        @csrf
        @include('projects._form', ['mode' => 'create'])
        <div class="actions"><button class="btn btn-primary" type="submit">Create project</button><a class="btn" href="{{ route('projects.index') }}">Cancel</a></div>
    </form>
</div>
@endsection
