@extends('templates.adminTemplate')

@section('page')
    <style>
        #articleListPages div:first-child{ display: none; }
    </style>

    <div class="row" style="margin-top:20px;">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item active" aria-current="page">{{__('adminTemplate.articleList.title')}}</li>
            </ol>
        </nav>

        <form class="row g-2" action="{{dirname($_SERVER['PHP_SELF'])}}/admin/articleList" method="post">
            @csrf
            <div class="col-md-6">
                <label for="search" class="visually-hidden">{{__('adminTemplate.articleList.searchInput')}} <small>{{__('adminTemplate.articleList.searchInput.small')}}</small></label>
                <input type="text" class="form-control" id="search" name="search" placeholder="{{__('adminTemplate.articleList.searchInput')}}" required>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary mb-3"><i class="fa fa-search"></i> {{__('adminTemplate.form.btn.search')}}</button>
                <a href="{{route('admin.newArticle')}}" role="button" class="btn btn-outline-primary mb-3"><i class="fa fa-file-circle-plus"></i> {{__('adminTemplate.form.btn.addArticle')}}</a>
                <a href="{{route('admin.articleList')}}" role="button" class="btn btn-outline-primary mb-3"><i class="fa fa-magnifying-glass-minus"></i> {{__('adminTemplate.form.btn.cleanFilter')}}</a>
            </div>
        </form>
    </div>

    <div class="row">
        <div class="col-md-12">
            <h5>{{__('adminTemplate.articleList.latest')}}</h5>
        </div>

        @include('utils.articleCard', ['articles' => $articles])

        <div class="col-md-12" style="margin-top:20px;" id="articleListPages">
            {{$articles->links()}}
        </div>
    </div>
@endsection