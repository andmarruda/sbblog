<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{config('app.name')}} - Painel administrativo</title>
        <link href="{{asset('css/bootstrap.min.css')}}" rel="stylesheet">
    </head>
    <body>
        <div class="container-md">
            <div class="row">
                <div class="col-md-12">
                    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
                        <div class="container-fluid">
                            <a href="{{route('admin.dashboard')}}" class="navbar-brand">{{config('app.name')}}</a>
                            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu" aria-controls="navbarMenu" aria-expanded="false" aria-label="Toggle navigation">
                                <span class="navbar-toggler-icon"></span>
                            </button>
                            <div class="collapse navbar-collapse d-flex" id="navbarMenu">
                                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                                    <li class="nav-item">
                                        <a class="nav-link" aria-current="page" href="{{route('admin.dashboard')}}">Dashboard</a>
                                    </li>

                                    <li class="nav-item">
                                        <a class="nav-link" href="{{route('admin.articleList')}}">Listar artigos</a>
                                    </li>

                                    <li class="nav-item">
                                        <a class="nav-link" href="{{route('admin.newArticle')}}">Novo artigo</a>
                                    </li>

                                    <li class="nav-item">
                                        <a class="nav-link" href="{{route('admin.category')}}">Categoria</a>
                                    </li>

                                    <li class="nav-item">
                                        <a class="nav-link" href="{{route('admin.user')}}">Usuario</a>
                                    </li>

                                    <li class="nav-item">
                                        <a class="nav-link" href="javascript: void(0);" data-bs-toggle="modal" data-bs-target="#modalAlterarSenha">Alterar senha</a>
                                    </li>

                                    <li class="nav-item">
                                        <a class="nav-link" href="javascript: void(0);" data-bs-toggle="modal" data-bs-target="#modalLogout">Sair</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </nav>
                </div>
            </div>

            @yield('page')
        </div>

        <div class="modal" id="modalLogout" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Deseja sair do sistema?</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <h4>Deseja sair do sistema?</h4>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Continuar no sistema</button>
                        <a href="{{route('admin.logout')}}" role="button" class="btn btn-primary">Sair</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal" id="modalAlterarSenha" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Alterar senha</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="formAlterPass" action="javascript: alterPassword();">
                            @csrf
                            <div class="mb-3">
                                <label for="senhaAntiga">Senha atual</label>
                                <input type="password" class="form-control" id="oldPassword" name="oldPassword" value="" placeholder="Senha atual" required>
                            </div>

                            <div class="mb-3">
                                <label for="senhaAntiga">Nova senha</label>
                                <input type="password" class="form-control" id="newPassword" name="newPassword" value="" placeholder="Nova senha" required>
                            </div>

                            <div class="mb-3">
                                <label for="senhaAntiga">Conf. Nova Senha</label>
                                <input type="password" class="form-control" id="checkPassword" name="checkPassword" value="" placeholder="Confirma Nova Senha" required>
                            </div>

                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary mb-3">Alterar senha</button>
                            </div>

                            <div id="adviceAlterPass"></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <script src="{{asset('js/bootstrap.bundle.min.js')}}"></script>
        <script src="{{asset('js/sbblog.js')}}"></script>
        <script>
            const alterPassword = async () => {
                let fd = new FormData(document.getElementById('formAlterPass'));
                let f = await fetch("{{route('admin.userAlterPass')}}", {
                    method: 'POST',
                    body: fd
                });

                if(!f.ok)
                    location.href = "{{route('admin.logout')}}";

                try{
                    let j = await f.json();
                    let div = document.getElementById('adviceAlterPass');
                    div.innerHTML = '<div class="alert '+ (j.error ? 'alert-danger' : 'alert-success') + ' alert-dismissible fade show">'+ j.message + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                    if(!j.error){
                        setTimeout(() => { 
                            document.getElementById('formAlterPass').reset(); 
                        }, 1000);
                    }
                } catch(e){
                    console.log(e);
                    location.href = "{{route('admin.logout')}}";
                }
            };
        </script>
    </body>
</html>