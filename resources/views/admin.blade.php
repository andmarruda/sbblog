<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{config('app.name')}} - Painel administrativo</title>
        <link href="{{asset('css/bootstrap.min.css')}}" rel="stylesheet">
    </head>
    <body>
        <div class="container">
            <div class="row justify-content-md-center">
                <div class="col-md-4" style="margin-top:100px;">
                    <div class="card">
                        <div class="card-header">
                            Painel administrativo
                        </div>
                        <div class="card-body">
                            <form method="post" action="{{route('admin.checkLogin')}}" autocomplete="off">
                                @csrf
                                <div class="mb-3">
                                    <label for="email">Email</label>
                                    <input type="text" class="form-control" name="email" id="email" value="" placeholder="Email">
                                </div>
                                <div class="mb-3">
                                    <label for="pass">Senha</label>
                                    <input type="password" class="form-control" name="pass" id="pass" value="" placeholder="Senha">
                                </div>
                                @if(!is_null(session('message')))
                                <div class="mb-3">
                                    @include('utils.alertDanger', ['message' => session('message')])
                                </div>
                                @endif
                                <button type="submit" class="btn btn-primary"><i class="fa fa-arrow-right-to-bracket"></i> Entrar</button>
                            </form>
                        </div>
                        <div class="card-footer" style="text-align:right;">
                            SBBlog Powered By <a href="https://sysborg.com.br" target="_blank" title="https://sysborg.com.br">
                                <img src="{{asset('images/poweredby.png')}}" alt="Powered By Sysborg">
                            </a> <span style="margin-left:10px; margin-right:10px;">|</span> <a href="https://andersonarruda.com.br" target="_blank" title="https://andersonarruda.com.br">
                                <img src="{{asset('images/poweredby2.png')}}" alt="Powered By Anderson Arruda">
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script src="{{asset('js/bootstrap.bundle.min.js')}}"></script>
        <script src="{{asset('js/fontawesome/all.min.js')}}"></script>
    </body>
</html>