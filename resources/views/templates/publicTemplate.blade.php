<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{config('app.name')}}</title>
        <link href="{{asset('css/bootstrap.min.css')}}" rel="stylesheet">
        <link href="{{asset('css/main.css')}}" rel="stylesheet">
    </head>
    <body>
        <div class="container-md">
            <div class="row">
                <div class="col-md-12" style="text-align:center;">
                    <a href="{{route('latestPage')}}">
                        <img src="{{asset('images/sbblog.png')}}" alt="SBBlog">
                    </a>
                    <p class="slogan">Compartilhando conhecimento através dos Blogs</p>
                </div>
                <hr>
            </div>

            <div class="row mt25">
                <div class="col-md-9">
                    @yield('page')
                </div>

                <div class="col-md-3" style="text-align:center;">
                    @if($category->count() > 0)
                    <div class="card" style="margin-top:40px;">
                        <h5 class="card-header">Categorias</h5>
                        <div class="card-body" style="text-align:left;">
                            <ul class="nav flex-column">
                                @foreach($category as $cat)
                                <li class="nav-item">
                                    <a class="nav-link" href="{{route('latestPageCategory', ['id' => $cat->id, 'category' => $cat->category])}}">{{$cat->category}}</a>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    @endif
                    
                    <a href="https://sysborg.com.br" target="_blank" title="Sysborg">
                        <img src="{{asset('images/publicidade1.png')}}" alt="Espaço para publicidade">
                    </a>
                </div>
            </div>

            <div class="row mt25">
                <div class="col-md-4 offset-md-3" style="padding-bottom: 20px;">
                    Todos os direitos reservados. © 2021-2031
                </div>
                <div class="col-md-5" style="text-align: right;">
                    SBBlog Powered By <a href="https://sysborg.com.br" target="_blank" title="https://sysborg.com.br">
                        <img src="{{asset('images/poweredby.png')}}" alt="Powered By Sysborg">
                    </a> <span style="margin-left:10px; margin-right:10px;">|</span> <a href="https://andersonarruda.com.br" target="_blank" title="https://andersonarruda.com.br">
                        <img src="{{asset('images/poweredby2.png')}}" alt="Powered By Anderson Arruda">
                    </a>
                </div>
            </div>
        </div>

        <script src="{{asset('js/bootstrap.bundle.min.js')}}"></script>
    </body>
</html>