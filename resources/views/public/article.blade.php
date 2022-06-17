@extends('templates.publicTemplate', ['category' => $category, 'article' => $article])

@section('page')

<div class="card">
    <img src="{{asset('storage/'. $article->cover_path)}}" class="card-img-top" alt="{{$article->title}}">
    <div class="card-body">
        @if(($tags=$article->tags()->get())->count() > 0)
        <div style="text-align: center;">
        @foreach($tags as $tag)
            <span class="badge bg-info text-dark">{{$tag->tag}}</span>
        @endforeach
        </div>
        @endif

        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{route('latestPage', ['id' => $article->category()->first()->id, 'category' => $article->category()->first()->category])}}">{{$article->category()->first()->category}}</a></li>
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
    <h5>{{($artComm = $article->comments()->paginate(20))->count()}} Comentário(s)</h5>
</div>

<div class="comments">
    @forelse($artComm as $comm)
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">{{$comm->comment_name}}</h5>
            <small>{{date('d/m/Y H:i', strtotime($comm->created_at))}}</small>
            <p class="card-text">{{$comm->comment_text}}</p>
        </div>
    </div>
    @empty
    <div class="card">
        <div class="card-body">
            <h6 style="margin-bottom:0;">Seja o primeiro a comentar o nosso artigo!</h6>
        </div>
    </div>
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
    <button type="submit" class="btn btn-outline-primary"><i class="fa-regular fa-message"></i> Comentar</button>
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