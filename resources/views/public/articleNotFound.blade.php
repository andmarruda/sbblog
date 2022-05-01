@extends('templates.publicTemplate', ['category' => $category])

@section('page')
<div class="card" style="text-align:center;">
    <h1>404 - Artigo não encontrado</h1>
    <div class="card-body">
        <div style="text-align: center; margin-bottom:20px;">
            <h4 class="card-title">Algo aconteceu e não encontramos o artigo solicitado!</h4>
        </div>
        <p class="card-text" style="text-align:left;">
            Volte a página inicial e encontre um artigo de seu interesse!<br><br>
            Caso prefira use o campo de busca para achar um assunto de seu interesse!
        </p>
        <p class="card-text">
            <a href="{{route('latestPage')}}" class="btn btn-primary" role="button">Página inicial</a>
        </p>
    </div>
</div>

<div style="text-align: center; margin-bottom: 50px;">
    <a href="https://sysborg.com.br" target="_blank" title="Sysborg">
        <img src="{{asset('images/publicidade2.png')}}" alt="Espaço para publicidade">
    </a>
</div>
@endsection