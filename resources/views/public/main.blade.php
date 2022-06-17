@extends('templates.publicTemplate', ['category' => $category])

@section('page')
<form method="post" action="{{route('latestPageSearch')}}">
    @csrf
    <div class="input-group mb-3">
        <input type="text" class="form-control" id="searchArticle" name="searchArticle" placeholder="Pesquisar artigo" aria-label="Pesquisar artigo">
        <button class="btn btn-outline-secondary" type="submit" id="button-addon2">Pesquisar</button>
    </div>
</form>

@if($articles->count() > 0)
    @foreach($articles as $article)
    <div class="card">
        <a href="{{route('article', ['friendly' => $article->url_friendly, 'id' => $article->id])}}">
            <img src="{{asset('storage/'.$article->cover_path)}}" class="card-img-top" alt="{{$article->title}}">
        </a>
        <div class="card-body">
            <h5 class="card-title">{{$article->title}}</h5>
            <small>Escrito por {{$article->user()->get()->first()->name}} em {{date('d/m/Y H:i', strtotime($article->created_at))}}</small>
            
            @if(($tags=$article->tags()->get())->count() > 0)
            <div style="font-size:12px;">
                <span>Tags: </span>
                @foreach($tags as $tag)
                <span class="badge bg-info text-dark">{{$tag->tag}}</span>
                @endforeach
            </div>
            @endif
            @if(!is_null($article->description))
            <p class="card-text">{{$article->description}}</p>
            @else
            <p class="card-text">{!!mb_substr($article->article, 0, 300, 'UTF-8').'...'!!}</p>
            @endif
            <div class="row">
                <div class="col-md-6">
                    <a href="{{route('article', ['friendly' => $article->url_friendly, 'id' => $article->id])}}" class="btn btn-outline-primary"><i class="fa-solid fa-eye"></i> Ler artigo completo</a>
                </div>

                <div class="col-md-6" style="text-align:right;">
                    <i class="fa-regular fa-message" title="Nº de comentário(s):"></i> {{$article->comments()->get()->count()}}
                </div>
            </div>
        </div>
    </div>
    @endforeach
@else
<div class="card">
    <div class="card-body">Nenhum artigo encontrado!</div>
</div>
@endif

<div class="banner-bottom">
    <a href="https://sysborg.com.br" target="_blank" title="Sysborg">
        <img src="{{asset('images/publicidade2.png')}}" alt="Espaço para publicidade">
    </a>
</div>
<div style="text-align: center;">
    {{$articles->links()}}
</div>
@endsection