@if($articles->count() > 0)
    @foreach($articles as $article)

    <div class="col-md-12 mt-3">
        <div class="card">
            <div class="row g-0">
                <div class="col-md-2 text-center" style="padding:1rem;">
                    <img src="{{asset('storage/'.$article->cover_path)}}" class="img-fluid rounded-start" alt="{{$article->title}}">
                </div>
                <div class="col-md-8 offset-md-1">
                    <nav class="mt-2 article-title" style="--bs-breadcrumb-divider: url(&#34;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8'%3E%3Cpath d='M2.5 0L1 1.5 3.5 4 1 6.5 2.5 8l4-4-4-4z' fill='currentColor'/%3E%3C/svg%3E&#34;);" aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 article-title">
                            <li class="breadcrumb-item"><a href="{{route('article.index', ['category_id' => $article->category_id])}}">{{$article->category->category}}</a></li>
                            <li class="breadcrumb-item active" aria-current="page">{{$article->title}}</li>
                        </ol>
                    </nav>

                    <small>{{__('adminTemplate.articleCard.createdBy')}} {{$article->writerName()}} {{__('adminTemplate.articleCard.createdTime')}} {{date('d/m/Y H:i', strtotime($article->created_at))}} - {{__('adminTemplate.articleCard.lastUpdate')}} {{date('d/m/Y H:i', strtotime($article->updated_at))}}</small>

                    @if(!is_null($article->description))
                    <p class="card-text">{!!substr($article->description, 0, 200)!!}</p>
                    @else
                    <p class="card-text">{!!substr($article->article, 0, 200)!!}</p>
                    @endif

                    <div class="d-flex justify-content-between">
                        <div>
                            <a href="{{route('admin.newArticle')}}/{{$article->id}}" class="btn btn-outline-primary"><i class="fa-regular fa-pen-to-square"></i> {{__('adminTemplate.form.btn.change')}}</a>
                            <a href="#" data-url="{{ route('article.destroy', ['article' => $article->id]) }}" data-toogle="article-destroy" data-delete="{{ is_null($article->deleted_at) }}" class="btn {{ is_null($article->deleted_at) ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                @if(is_null($article->deleted_at))
                                    <i class="fa-regular fa-trash-can"></i> {{__('adminTemplate.articleList.softDelete')}}
                                @else
                                    <i class="fa-solid fa-trash-can-arrow-up"></i> {{__('adminTemplate.articleList.restore')}}
                                @endif
                            </a>
                        </div>

                        <div>
                            <p class="card-text">
                                <ul class="list-inline">
                                    <li class="list-inline-item"><small class="text-muted">{{$article->numberUniqueVisits()}} <i class="fa-solid fa-eye" title="{{__('adminTemplate.articleCard.views')}}"></i></small></li>
                                    <li class="list-inline-item"><small class="text-muted"> | </small></li>
                                    <li class="list-inline-item"><small class="text-muted"><i class="fa-regular fa-clock" title="{{__('adminTemplate.articleCard.avgStayTime')}}"></i> {{$article->avgVisitsTime()}}</small></li>
                                    <li class="list-inline-item"><small class="text-muted"> | </small></li>
                                    <li class="list-inline-item"><small class="text-muted"><i class="fa-regular fa-message" title="{{__('adminTemplate.articleList.commentNumber')}}"></i> {{$article->comments()->get()->count()}}</small></li>
                                </ul>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
@else
    <div class="col-md-12" style="margin-top:20px;">
        <div class="card">
            <div class="row g-0">
                <div class="col-md-8 offset-md-1">
                    <h5 class="card-title">{{__('adminTemplate.articleCard.noArticle')}}</h5>
                </div>
            </div>
        </div>
    </div>
@endif