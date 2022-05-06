@extends('templates.adminTemplate')

@section('page')
<form method="post" action="{{route('admin.generalPost')}}" style="margin-top:30px;" autocomplete="off" enctype="multipart/form-data">
    <input type="hidden" name="registered_file" id="registered_file" value="{{$gen->brand_image}}">
    @csrf
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active" aria-current="page">Configuração Geral</li>
        </ol>
    </nav>

    <div class="mb-3">
        <label for="slogan" class="form-label">Slogan</label>
        <input type="text" maxlength="200" class="form-control" id="slogan" name="slogan" placeholder="Slogan" required value="{{$gen->slogan}}">
    </div>

    <div class="mb-3">
        <label for="section" class="form-label">Nicho do Blog <small>"Ex.:Tecnologia, esporte, etc..."</small></label>
        <input type="text" maxlength="200" class="form-control" id="section" name="section" placeholder="Nicho do Blog" required value="{{$gen->section}}">
    </div>

    <div class="mb-3">
        <label for="brand_image" class="form-label">Capa</label>
        <input type="file" class="form-control" id="brand_image" name="brand_image">
    </div>

    @isset($saved)
        @if($saved)
            @include('utils.alertSuccess', ['message' => 'Configuração geral salva com sucesso!'])
        @else
            @include('utils.alertDanger', ['message' => 'Erro ao salvar configuração geral!'])
        @endif
    @endisset

    <div class="mb-3">
        <button type="submit" class="btn btn-primary">Salvar</button>
    </div>
</form>
@endsection