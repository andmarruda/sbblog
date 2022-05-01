@extends('templates.adminTemplate')

@section('page')
<form method="post" action="{{route('admin.userPost')}}" style="margin-top:30px" autocomplete="off">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active" aria-current="page">Usuário</li>
        </ol>
    </nav>

    @if(!is_null(session('configUser')))
    <div class="alert alert-danger">Para maior segurança do sistema, crie um novo usuário! Após isso o sistema irá desativar o usuário de configuração!</div>
    @endif

    @csrf
    <input type="hidden" name="id" id="id" value="{{$user->id ?? ''}}">
    <div class="mb-3">
        <label for="name" class="form-label">Nome</label>
        <input type="text" maxlength="255" class="form-control" id="name" name="name" placeholder="Nome" required value="{{$user->name ?? ''}}">
    </div>
    <div class="mb-3">
        <label for="username" class="form-label">Usuário "Email"</label>
        <input type="email" maxlength="255" class="form-control" id="username" name="username" placeholder="Usuário 'Email'" required value="{{$user->email ?? ''}}">
    </div>
    <div class="mb-3">
        <label for="pass" class="form-label">Senha</label>
        <input type="password" maxlength="150" class="form-control" id="pass" name="pass" placeholder="Senha" required>
    </div>
    <div class="mb-3">
        <label for="confirmPass" class="form-label">Confirmação de senha</label>
        <input type="password" maxlength="150" class="form-control" id="confirmPass" name="confirmPass" placeholder="Confirmação de senha" required>
    </div>

    @include('utils.comboActive', ['active' => $user->active ?? NULL])
    @isset($saved)
        @if($saved)
            @include('utils.alertSuccess', ['message' => 'Usuário salvo com sucesso!'])
        @else
            @include('utils.alertDanger', ['message' => $message ?? 'Erro ao salvar o usuário!'])
        @endif
    @endisset

    <div class="mb-3">
        <button type="submit" class="btn btn-primary">Salvar</button>
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalSearchUsers">Pesquisar</button>
    </div>
</form>

<div class="modal" id="modalSearchUsers" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pesquisar usuário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form class="row g-3" id="formSearchUser" action="javascript: searchUser();" autocomplete="off">
                    @csrf
                    <div class="col-auto">
                        <input type="text" class="form-control" id="userSearch" name="userSearch" placeholder="Usuário" required>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary mb-3">Pesquisar</button>
                    </div>
                </form>

                <div class="alert alert-info">Clique 2x no usuário desejado para carregá-lo no formulário.</div>

                <table class="table table-bordered table-striped" id="gridUserSearch" ondblclick="javascript: loadDataForm(event);">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Usuário</th>
                            <th>Nome</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if(document.getElementById('id').value.length > 0){
            document.getElementById('pass').removeAttribute('required');
            document.getElementById('confirmPass').removeAttribute('required');
        }
    });

    const changePass = () => {
        if(document.getElementById('id').value.length == 0)
            return;

        let p = document.getElementById('pass');
        let cp = document.getElementById('confirmPass');
        if(p.value.length > 0 || cp.value.length > 0){
            p.setAttribute('required', '');
            cp.setAttribute('required', '');
            return;
        }

        p.removeAttribute('required', '');
        cp.removeAttribute('required', '');
    };

    document.getElementById('pass').addEventListener('blur', () => changePass());
    document.getElementById('confirmPass').addEventListener('blur', () => changePass());

    const searchUser = () => {
        searchToTable('formSearchUser', 'gridUserSearch', "{{route('admin.userSearch')}}", "{{route('admin.logout')}}", ['id', 'email', 'name'], 'id');
    };

    const loadDataForm = (event) => {
        loadForm("{{route('admin.user')}}", event);
    }
</script>

@if(isset($configUser) && $configUser)
    <script>
        setTimeout(() => {
            location.href = "{{route('admin.logout')}}";
        }, 1000);
    </script>
@endif

@endsection