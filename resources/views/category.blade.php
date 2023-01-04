@extends('templates.adminTemplate')

@section('page')
<form method="post" action="{{route('admin.categoryPost')}}" style="margin-top:30px;" autocomplete="off">
    @include('utils.breadcrumb', ['title' => __('adminTemplate.category.title')])

    @csrf
    <input type="hidden" name="id" id="id" value="{{$cat->id ?? ''}}">

    <div class="mb-3">
        <label for="categoryName" class="form-label">{{__('adminTemplate.category.form.label')}}</label>
        <input type="text" maxlength="50" class="form-control" id="categoryName" name="categoryName" placeholder="{{__('adminTemplate.category.form.label')}}" required value="{{$cat->category ?? ''}}">
    </div>

    @include('utils.comboActive', ['active' => $cat->active ?? NULL])

    @if(!is_null(session('saved')))
        @include('utils.alertSuccess', ['message' => __('adminTemplate.category.okmessage')])
    @endif

    @include('utils.alertError')

    <div class="mb-3">
        <button type="submit" class="btn btn-primary"><i class="fa-regular fa-floppy-disk"></i> {{__('adminTemplate.form.btn.save')}}</button>
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalSearchCategory"><i class="fa fa-search"></i> {{__('adminTemplate.form.btn.search')}}</button>
    </div>
</form>

<div class="modal" id="modalSearchCategory" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{__('adminTemplate.category.modal.title')}}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form class="row g-3" id="formSearchCategory" action="javascript: searchCat();" autocomplete="off">
                    @csrf
                    <div class="col-auto">
                        <input type="text" class="form-control" id="categorySearch" name="categorySearch" placeholder="{{__('adminTemplate.category.modal.input')}}" required>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary mb-3"><i class="fa fa-search"></i> {{__('adminTemplate.form.btn.search')}}</button>
                    </div>
                </form>

                <div class="alert alert-info">{{__('adminTemplate.form.load.advice', ['desired' => __('adminTemplate.category.title')])}}</div>

                <table class="table table-bordered table-striped" id="gridCategorySearch" ondblclick="javascript: loadDataForm(event);">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{__('adminTemplate.category.modal.tab.label')}}</th>
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