@extends('templates.adminTemplate')

@section('css')
    @include('form.article.css.form-css')
@endsection

@section('page')
<nav aria-label="breadcrumb" style="margin-top:30px;">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="#">{{(__('adminTemplate.article.title'))}}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('adminTemplate.article.new.title') }}</li>
    </ol>
</nav>

<nav>
    <div class="nav nav-tabs" id="nav-tab" role="tablist">
        <button class="nav-link active" id="nav-form-tab" data-bs-toggle="tab" data-bs-target="#nav-form" type="button" role="tab" aria-controls="nav-form" aria-selected="true">{{__('adminTemplate.article.tab.form')}}</button>
        <button class="nav-link" id="nav-comment-tab" data-bs-toggle="tab" data-bs-target="#nav-comment" type="button" role="tab" aria-controls="nav-comment" aria-selected="false">{{__('adminTemplate.article.tab.comment')}}</button>
    </div>
</nav>
<div class="tab-content" id="nav-tabContent" style="margin-bottom:20px;">
    <div class="tab-pane fade show active" id="nav-form" role="tabpanel" aria-labelledby="nav-form-tab">
        <form method="post" id="formArticleSave" action="" style="margin-top:20px;" autocomplete="off" enctype="multipart/form-data">
            
        </form>
    </div>
    <div class="tab-pane fade mt-3" id="nav-comment" role="tabpanel" aria-labelledby="nav-comment-tab" style="padding-top:20px;">
        @forelse($article->comments()->orderBy('created_at', 'desc')->get() as $comm)
            @include('utils.commentCard', ['name' => $comm->comment_name, 'comment' => $comm->comment_text, 'created_at' => $comm->created_at, 'admin' => true, 'id' => $comm->id, 'active' => is_null($comm->deleted_at)])
        @empty
            @include('utils.commentCardNotFounded', ['advice' => __('adminTemplate.article.commentList.none')])
        @endforelse
    </div>
</div>
@endsection

@section('jscript')
    @include('form.article.js.form-js')
@endsection