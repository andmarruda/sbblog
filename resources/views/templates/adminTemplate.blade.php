<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{config('app.name')}} - Painel administrativo</title>
        <link href="{{asset('css/bootstrap.min.css')}}" rel="stylesheet">
        <link href="{{asset('css/admin.css')}}" rel="stylesheet">
        @if(in_array(Route::current()->getName(), ['admin.newArticle', 'admin.newArticlePost']))
        <link rel="stylesheet" href="{{asset('css/monokai-sublime.min.css')}}">
        <link rel="stylesheet" href="{{asset('css/quill.snow.css')}}">
        <script src="{{asset('js/highlight.min.js')}}"></script>
        <script src="{{asset('js/quill.min.js')}}"></script>
        @endif
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
                                        <a class="nav-link" aria-current="page" href="{{route('admin.dashboard')}}">{{__('adminTemplate.menu.dashboard')}}</a>
                                    </li>

                                    <li class="nav-item dropdown">
                                        <a class="nav-link dropdown-toggle" href="#" id="navbarArticleDropDownMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            {{__('adminTemplate.menu.article')}}
                                        </a>
                                        <ul class="dropdown-menu" aria-labelledby="navbarArticleDropDownMenu">
                                            <li><a class="dropdown-item" href="{{route('admin.newArticle')}}">{{__('adminTemplate.menu.article.new')}}</a></li>
                                            <li><a class="dropdown-item" href="{{route('admin.articleList')}}">{{__('adminTemplate.menu.article.list')}}</a></li>
                                            <li><a class="dropdown-item" href="{{route('category.create')}}">{{__('adminTemplate.menu.article.category')}}</a></li>
                                        </ul>
                                    </li>

                                    <li class="nav-item dropdown">
                                        <a class="nav-link dropdown-toggle" href="#" id="navbarConfiguration" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            {{__('adminTemplate.menu.config')}}
                                        </a>
                                        <ul class="dropdown-menu" aria-labelledby="navbarConfiguration">
                                            <li><a class="dropdown-item" href="{{route('user.create')}}">{{__('adminTemplate.menu.config.user')}}</a></li>
                                            <li><a class="dropdown-item" href="javascript: void(0);" data-bs-toggle="modal" data-bs-target="#modalChangePass">{{__('adminTemplate.menu.config.password')}}</a></li>
                                            <li><a class="dropdown-item" href="{{route('general.edit', 1)}}">{{__('adminTemplate.menu.config.general')}}</a></li>
                                        </ul>
                                    </li>

                                    <li class="nav-item">
                                        <a class="nav-link" href="javascript: void(0);" data-bs-toggle="modal" data-bs-target="#modalLogout">{{__('adminTemplate.menu.logout')}}</a>
                                    </li>
                                </ul>
                            </div>

                            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="javascript: void(0);" id="navbarArticleDropDownMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <img src="{{asset($_SESSION['sbblog']['lang']['icon'])}}" alt="{{$_SESSION['sbblog']['lang']['label']}}">
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="navbarArticleDropDownMenu">
                                        @foreach(\App\Models\Language::all() as $mLang)
                                        <li><a class="dropdown-item" href="{{route('admin.changeLang', ['id' => $mLang->id])}}"><img src="{{asset($mLang->icon)}}" alt="{{$mLang->label}}"> {{$mLang->label}}</a></li>
                                        @endforeach
                                    </ul>
                                </li>
                            </ul>
                        </div>
                    </nav>
                </div>
            </div>

            <section id="admin-content">
            @yield('page')
            </section>

            <footer class="row mt25">
                <div class="col-md-4 offset-md-3" style="padding-bottom: 20px;">
                    {{__('adminTemplate.footer.copyright')}} © 2021-2031
                </div>
                <div class="col-md-5" style="text-align: right;">
                    <a href="https://sysborg.com.br" target="_blank" title="https://sysborg.com.br">
                        <img src="{{asset('images/poweredby.png')}}" alt="Powered By Sysborg">
                    </a> <span style="margin-left:10px; margin-right:10px;">|</span> <a href="https://andersonarruda.com.br" target="_blank" title="https://andersonarruda.com.br">
                        <img src="{{asset('images/poweredby2.png')}}" alt="Powered By Anderson Arruda">
                    </a>
                </div>
            </footer>
        </div>

        <div class="modal" id="modalLogout" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{__('adminTemplate.modal.logout.question')}}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <h4>{{__('adminTemplate.modal.logout.question')}}</h4>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{__('adminTemplate.modal.logout.btn.cancel')}}</button>
                        <a href="{{route('admin.logout')}}" role="button" class="btn btn-primary">{{__('adminTemplate.modal.logout.btn.logout')}}</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal" id="modalChangePass" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{__('adminTemplate.modal.password.title')}}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="formAlterPass" action="javascript: alterPassword();">
                            @csrf
                            <div class="mb-3">
                                <label for="oldPassword">{{__('adminTemplate.modal.password.oldpass')}}</label>
                                <input type="password" class="form-control" id="oldPassword" name="oldPassword" value="" placeholder="{{__('adminTemplate.modal.password.oldpass')}}" required>
                            </div>

                            <div class="mb-3">
                                <label for="newPassword">{{__('adminTemplate.modal.password.newpass')}}</label>
                                <input type="password" class="form-control" id="newPassword" name="newPassword" value="" placeholder="{{__('adminTemplate.modal.password.newpass')}}" required>
                            </div>

                            <div class="mb-3">
                                <label for="checkPassword">{{__('adminTemplate.modal.password.confpass')}}</label>
                                <input type="password" class="form-control" id="checkPassword" name="checkPassword" value="" placeholder="{{__('adminTemplate.modal.password.confpass')}}" required>
                            </div>

                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary mb-3">{{__('adminTemplate.modal.password.btn.submit')}}</button>
                            </div>

                            <div id="adviceAlterPass"></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <script src="{{asset('js/bootstrap.bundle.min.js')}}"></script>
        <script src="{{asset('js/fontawesome/all.min.js')}}"></script>
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