@extends('templates.adminTemplate')

@section('page')
<form method="post" id="formArticleSave" action="{{route('admin.newArticlePost')}}" style="margin-top:30px;" autocomplete="off" enctype="multipart/form-data">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active" aria-current="page">Cadastro de artigo</li>
        </ol>
    </nav>

    @csrf
    <input type="hidden" name="id" id="id" value="{{$article->id ?? ''}}">
    <input type="hidden" name="addedTags" id="addedTags" value="">
    <input type="hidden" name="removedTags" id="removedTags" value="">
    <input type="hidden" id="articleText" name="articleText" value="{{$article->article ?? ''}}">
    <input type="hidden" id="articleCoverPath" name="articleCoverPath" value="{{$article->cover_path ?? ''}}">

    <div class="mb-3">
        <label for="articleName" class="form-label">Título</label>
        <input type="text" maxlength="150" class="form-control" id="articleName" name="articleName" placeholder="Título" required value="{{$article->title ?? ''}}">
    </div>

    @isset($article->cover_path)
    <div class="mb-3">
        <label for="articleCoverPreview" class="form-lable">Pré visualização da capa</label><br>
        <img src="{{asset('storage/'.$article->cover_path)}}" alt="{{$article->title ?? ''}}" class="img-fluid">
    </div>
    @endisset

    <div class="mb-3">
        <label for="articleCover" class="form-label">Capa</label>
        <input type="file" class="form-control" id="articleCover" name="articleCover">
    </div>

    <div class="mb-3">
        <label for="category" class="form-label">Categoria</label>
        <select class="form-control" id="category" name="category" required>
            <option value="">Selecione...</option>
            @if($categories->count() > 0)
            @foreach($categories as $cat)
                <option value="{{$cat->id}}"{{($cat->id == ($article->category_id ?? -1)) ? ' selected="selected"' : ''}}>{{$cat->category}}</option>
            @endforeach
            @endif
        </select>
    </div>

    @include('utils.comboActive', ['active' => $article->active ?? NULL])

    <div class="mb-3">
        <label for="description" class="form-label">Descrição curta <small>até 200 caracteres</small></label>
        <input type="text" id="description" name="description" maxlength="200" required>
    </div>

    <div class="mb-3">
        <label for="article" class="form-label">Artigo</label>
        <div id="article" style="min-height:200px;"></div>
    </div>

    <div class="input-group mb-3">
        <input type="text" class="form-control" placeholder="Meta tag" id="metatagInput" name="metatagInput">
        <button class="btn btn-outline-secondary" type="button" id="button-addon2" onclick="javascript: addTag();">Adicionar Tag</button>
        <button class="btn btn-outline-secondary" type="button" id="button-addon2" onclick="javascript: removeTag();">Remover Tag</button>
    </div>

    <div class="mb-3">
        <select id="tags" name="tags" class="form-control" multiple style="height:150px;">
        @if(isset($article) && ($tags=$article->tags()->orderBy('tag')->get())->count() > 0)
            @foreach($tags as $at)
            <option value="{{$at->tag}}">{{$at->tag}}</option>
            @endforeach
        @endif
        </select>
    </div>

    @isset($saved)
        @if($saved)
            @include('utils.alertSuccess', ['message' => 'Artigo salvo com sucesso!'])
        @else
            @include('utils.alertDanger', ['message' => $message ?? 'Erro ao salvar o usuário!'])
        @endif
    @endisset

    <div class="mb-3">
        <button type="submit" class="btn btn-primary">Salvar</button>
        <a href="{{route('admin.articleList')}}" role="button" class="btn btn-outline-primary">Pesquisar</a>
    </div>
</form>

<script>
    var quillArticle;

    document.addEventListener('DOMContentLoaded', () => {
        quillArticle = new Quill('#article', {
            modules: {
                'syntax': true,
                'toolbar': [
                [ 'bold', 'italic', 'underline', 'strike' ],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'script': 'super' }, { 'script': 'sub' }],
                [{ 'header': '1' }, { 'header': '2' }, 'blockquote', 'code-block' ],
                [{ 'list': 'ordered' }, { 'list': 'bullet'}, { 'indent': '-1' }, { 'indent': '+1' }],
                [ {'direction': 'rtl'}, { 'align': [] }],
                [ 'link', 'image', 'video', 'formula' ],
                [ 'clean' ]
                ],
            },
            theme: 'snow'
        });

        textToArticle();

        document.getElementById('formArticleSave').addEventListener('submit', (event) => {
            articleToText();
            
            if(document.getElementById('articleText').value.length == 0){
                event.preventDefault();
                alert('Preencha um artigo para poder salvá-lo!');
                return false;
            }

            if(document.getElementById('articleCoverPath').value == '' && document.getElementById('articleCover').files.length == 0){
                event.preventDefault();
                alert('Escolha a capa para o seu artigo!');
                return false;
            }

            return true;
        });
    });

    var addedTags = [],
        removedTags = [];

    const _createOpt = (val) => {
        let opt = document.createElement('OPTION');
        opt.value = val;
        opt.appendChild(document.createTextNode(val));
        document.getElementById('tags').appendChild(opt);
    };

    const addTag = () => {
        let inputVal = document.getElementById('metatagInput');
        if(inputVal.value.length == 0)
            return;
        
        if(addedTags.indexOf(inputVal.value) > -1){
            alert('A tag '+ inputVal.value + ' já foi inserida');
            inputVal.value = '';
            return;
        }

        _createOpt(inputVal.value);
        addedTags.push(inputVal.value);
        inputVal.value = '';
        document.getElementById('addedTags').value = JSON.stringify(addedTags);
    };

    const removeTag = () => {
        let combobox = document.getElementById('tags');
        if(combobox.options.length == 0)
            return;

        for(let o of combobox.options){
            if(o.selected){
                combobox.removeChild(o);

                addedTags = addedTags.filter((val) => val != o.value);
                if(removedTags.indexOf(o.value) === -1)
                    removedTags.push(o.value);
            }
        }

        if(document.getElementById('id').value != '')
            document.getElementById('removedTags').value = JSON.stringify(removedTags);
            
        document.getElementById('addedTags').value = JSON.stringify(addedTags);
    };

    const articleToText = () => {
        document.getElementById('articleText').value = quillArticle.root.innerHTML;
    };

    const textToArticle = () => {
        let at = document.getElementById('articleText');
        if(at.value.length > 0){
            let d = quillArticle.clipboard.convert(at.value);
            quillArticle.setContents(d, 'silent');
        }
    };
</script>
@endsection