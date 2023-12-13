@extends('templates.publicTemplate')

@section('article-head')
    <meta name="keywords" content="{{$article->stringTags()}}">
    <meta name="description" content="{{$article->description}}">
    <meta property="og:title" content="{{$article->title. ' '. config('app.name')}}" />
    <meta property="og:description" content="{{$article->description}}" />
    <meta property="og:type" content="article" />
    <meta property="og:article:published_time" content="{{$article->created_at}}" />
    <meta property="og:article:modified_time" content="{{$article->updated_at}}" />
    <meta property="og:article:author" content="{{$article->user()->get()->first()->name}}" />
    <meta property="og:article:tag" content="{{$article->stringTags()}}" />
    <meta property="og:url" content="{{url('/')}}" />
    <meta property="og:image" content="{{asset('storage/'. $article->cover_path)}}" />
    <meta name="twitter:card" content="summary_large_image">
    <meta property="twitter:domain" content="{{request()->getHttpHost();}}">
    <meta property="twitter:url" content="{{url('/')}}">
    <meta name="twitter:title" content="{{$article->title. ' '. config('app.name')}}">
    <meta name="twitter:description" content="{{$article->description}}">
    <meta name="twitter:image" content="{{asset('storage/'. $article->cover_path)}}">
    <title>{{$article->title}}</title>
    <link rel="stylesheet" href="{{asset('css/monokai-sublime.min.css')}}">
    <link rel="stylesheet" href="{{asset('css/quill.snow.css')}}">
    {!! RecaptchaV3::initJs() !!}
@endsection

@section('recaptcha')
    @if(config('app.RECAPTCHAV3_SITEKEY') != '')
        {!! RecaptchaV3::initJs() !!}
    @endif    
@endsection

@section('page')

<div class="card">
    <img src="{{asset('storage/'. $article->cover_path)}}" class="card-img-top" alt="{{$article->title}}">
    <div class="card-body">
        <div style="text-align: center;">
            @foreach($article->tags as $tag)
                <span class="badge bg-info text-dark">{{$tag->tag}}</span>
            @endforeach
        </div>

        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{route('latestPage', ['id' => $article->category_id, 'category' => $article->category->category])}}">{{$article->category->category}}</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{$article->title}}</li>
            </ol>
        </nav>
        
        <div style="text-align: center;">
            <h4 class="card-title">{{$article->title}}</h4>
            <small>{{date('d/m/Y H:i', strtotime($article->created_at))}}</small><hr>
        </div>
        <p class="card-text">
            <div class="ql-snow">
                <div class="ql-editor">
                    {!!$article->article!!}
                </div>
            </div>
        </p>
    </div>
</div>

<div style="text-align: center; margin-bottom: 50px;">
    <a href="https://sysborg.com.br" target="_blank" title="Sysborg">
        <img src="{{asset('images/publicidade2.png')}}" alt="Espaço para publicidade">
    </a>
</div>

<div>
    <h5>{{($artComm = $article->comments()->where('active', '=', true)->paginate(20))->count()}} Comentário(s)</h5>
</div>

<div class="comments">
    @forelse($artComm as $comm)
    @include('utils.commentCard', ['name' => $comm->comment_name, 'comment' => $comm->comment_text, 'created_at' => $comm->created_at])
    @empty
    @include('utils.commentCardNotFounded', ['advice' => 'Seja o primeiro a comentar o nosso artigo!'])
    @endforelse
    <div style="text-align: center;">
        {{$artComm->links()}}
    </div>
</div>

<form method="post" action="{{route('articleComment')}}">
    @csrf
    <input type="hidden" id="id" name="id" value="{{$article->id}}">
    <input type="hidden" id="friendly" name="friendly" value="{{$article->url_friendly}}">
    <div class="mb-3">
        <label for="name" class="form-label">* Nome</label>
        <input type="text" class="form-control" id="name" name="name" placeholder="*Nome" required>
    </div>
    <div class="mb-3">
        <label for="comment" class="form-label">* Comentário <small>Máximo de 350 dígitos</small></label>
        <textarea class="form-control" rows="3" id="text" name="text" required maxlength="350" placeholder="*Comentário"></textarea>
    </div>
    @if(config('app.RECAPTCHAV3_SITEKEY') != '')
        {!! RecaptchaV3::field('comment') !!}
    @endif
    <button type="submit" class="btn btn-outline-primary"><i class="fa-regular fa-message"></i> Comentar</button>
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</form>

<script src="{{asset('js/collectSbblog.js')}}"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        registerVisit("{{URL::to('/')}}/visitInit", {{$article->id}}, "{{csrf_token()}}");
    });

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden')
            registerExit("{{URL::to('/')}}/visitEnd", "{{csrf_token()}}");
    });

    document.addEventListener('beforeunload', () => {
        registerExit("{{URL::to('/')}}/visitEnd", "{{csrf_token()}}");
    });
</script>
@endsection