@extends('templates.adminTemplate')

@section('page')
<form method="post" action="{{route('admin.categoryPost')}}" style="margin-top:30px;" autocomplete="off">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active" aria-current="page">Categoria</li>
        </ol>
    </nav>

    @csrf
    <input type="hidden" name="id" id="id" value="{{$cat->id ?? ''}}">

    <div class="mb-3">
        <label for="categoryName" class="form-label">Categoria</label>
        <input type="text" maxlength="50" class="form-control" id="categoryName" name="categoryName" placeholder="Categoria" required value="{{$cat->category ?? ''}}">
    </div>

    @include('utils.comboActive', ['active' => $cat->active ?? NULL])

    @isset($saved)
        @if($saved)
            @include('utils.alertSuccess', ['message' => 'Categoria salva com sucesso!'])
        @else
            @include('utils.alertDanger', ['message' => 'Erro ao salvar a categoria!'])
        @endif
    @endisset

    <div class="mb-3">
        <button type="submit" class="btn btn-primary">Salvar</button>
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalSearchCategory">Pesquisar</button>
    </div>
</form>

<div class="modal" id="modalSearchCategory" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pesquisar categoria</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form class="row g-3" id="formSearchCategory" action="javascript: searchCat();" autocomplete="off">
                    @csrf
                    <div class="col-auto">
                        <input type="text" class="form-control" id="categorySearch" name="categorySearch" placeholder="Categoria" required>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary mb-3">Pesquisar</button>
                    </div>
                </form>

                <div class="alert alert-info">Clique 2x na categoria desejada para carregá-la no formulário.</div>

                <table class="table table-bordered table-striped" id="gridCategorySearch" ondblclick="javascript: loadDataForm(event);">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Categoria</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
    const searchCat = () => {
        searchToTable('formSearchCategory', 'gridCategorySearch', "{{route('admin.categorySearch')}}", "{{route('admin.logout')}}", ['id', 'category'], 'id');
    };

    const loadDataForm = (event) => {
        loadForm("{{route('admin.category')}}", event);
    }
</script>
@endsection