<div class="card">
    <div class="card-body">
        <h5 class="card-title">{{$name}}</h5>
        <small>{{date('d/m/Y H:i', strtotime($created_at))}}</small>
        <p class="card-text">{{$comment}}</p>
    </div>
</div>