@if($articles->count() > 0)
    @foreach($articles as $article)
    <div class="col-md-12" style="margin-top:20px;">
        <div class="card">
            <div class="row g-0">
                <div class="col-md-3">
                    <img src="{{asset('storage/'.$article->cover_path)}}" class="img-fluid rounded-start" alt="{{$article->title}}">
                </div>
                <div class="col-md-8 offset-md-1">
                    <h5 class="card-title">{{$article->title}}</h5>
                    <small>{{__('adminTemplate.articleCard.createdBy')}} {{$article->writerName()}} {{__('adminTemplate.articleCard.createdTime')}} {{date('d/m/Y H:i', strtotime($article->created_at))}}</small>
                    <p class="card-text">
                        <ul class="list-inline">
                            <li class="list-inline-item"><small class="text-muted">{{__('adminTemplate.articleCard.category')}} {{$article->category()->get()->first()->category}} </small></li>
                            <li class="list-inline-item"><small class="text-muted"> | </small></li>
                            <li class="list-inline-item"><small class="text-muted">{{__('adminTemplate.articleCard.lastUpdate')}} {{date('d/m/Y H:i', strtotime($article->updated_at))}}</small></li>
                            <li class="list-inline-item"><small class="text-muted"> | </small></li>
                            <li class="list-inline-item"><small class="text-muted">{{$article->numberUniqueVisits()}} <i class="fa-solid fa-eye" title="{{__('adminTemplate.articleCard.views')}}"></i></small></li>
                            <li class="list-inline-item"><small class="text-muted"> | </small></li>
                            <li class="list-inline-item"><small class="text-muted"><i class="fa-regular fa-clock" title="{{__('adminTemplate.articleCard.avgStayTime')}}"></i> {{$article->avgVisitsTime()}}</small></li>
                        </ul>
                    </p>

                    @if(!is_null($article->description))
                    <p class="card-text">{!!substr($article->description, 0, 200)!!}</p>
                    @else
                    <p class="card-text">{!!substr($article->article, 0, 200)!!}</p>
                    @endif
                    <p><a href="{{route('admin.newArticle')}}/{{$article->id}}" class="btn btn-primary"><i class="fa-regular fa-pen-to-square"></i> {{__('adminTemplate.form.btn.change')}}</a></p>
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