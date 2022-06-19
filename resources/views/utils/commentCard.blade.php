<div class="card">
    <div class="card-body">
        <h5 class="card-title">{{$name}}</h5>
        <small>{{date('d/m/Y H:i', strtotime($created_at))}}</small>
        <p class="card-text">{{$comment}}</p>
        @if(isset($admin) && $admin)
        <p style="text-align:right;">
            @if($active)
            <a href="javascript: void(0);" onclick="javascript: commentAction(event, {{$id}});" class="btn btn-danger" role="button">{{__('adminTemplate.article.commentList.disable')}}</a>
            @else
            <a href="javascript: void(0);" onclick="javascript: commentAction(event, {{$id}});" class="btn btn-success" role="button">{{__('adminTemplate.article.commentList.enable')}}</a>
            @endif
        </p>
        @endif
    </div>
</div>