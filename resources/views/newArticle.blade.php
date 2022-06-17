@extends('templates.adminTemplate')

@section('page')
<form method="post" id="formArticleSave" action="{{route('admin.newArticlePost')}}" style="margin-top:30px;" autocomplete="off" enctype="multipart/form-data">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active" aria-current="page">{{(__('adminTemplate.article.title'))}}</li>
        </ol>
    </nav>

    @csrf
    <input type="hidden" name="id" id="id" value="{{$article->id ?? ''}}">
    <input type="hidden" name="addedTags" id="addedTags" value="">
    <input type="hidden" name="removedTags" id="removedTags" value="">
    <input type="hidden" id="articleText" name="articleText" value="{{$article->article ?? ''}}">
    <input type="hidden" id="articleCoverPath" name="articleCoverPath" value="{{$article->cover_path ?? ''}}">

    <div class="mb-3">
        <label for="articleName" class="form-label">{{__('adminTemplate.article.form.title')}}</label>
        <input type="text" maxlength="150" class="form-control" id="articleName" name="articleName" placeholder="{{__('adminTemplate.article.form.title')}}" required value="{{$article->title ?? ''}}">
    </div>

    @isset($article->cover_path)
    <div class="mb-3">
        <label for="articleCoverPreview" class="form-lable">{{__('adminTemplate.article.form.previewFolder')}}</label><br>
        <img src="{{asset('storage/'.$article->cover_path)}}" alt="{{$article->title ?? ''}}" class="img-fluid">
    </div>
    @endisset

    <div class="mb-3">
        <label for="articleCover" class="form-label">{{__('adminTemplate.article.form.cover')}}</label>
        <input type="file" class="form-control" id="articleCover" name="articleCover">
    </div>

    <div class="mb-3">
        <label for="category" class="form-label">{{__('adminTemplate.article.form.category')}}</label>
        <select class="form-control" id="category" name="category" required>
            <option value="">{{__('adminTemplate.article.form.category.select')}}</option>
            @if($categories->count() > 0)
            @foreach($categories as $cat)
                <option value="{{$cat->id}}"{{($cat->id == ($article->category_id ?? -1)) ? ' selected="selected"' : ''}}>{{$cat->category}}</option>
            @endforeach
            @endif
        </select>
    </div>

    @include('utils.comboActive', ['active' => $article->active ?? NULL])

    <div class="mb-3">
        <label for="description" class="form-label">{{__('adminTemplate.article.form.meta')}} <small>{{__('adminTemplate.article.form.meta.small')}}</small></label>
        <input type="text" class="form-control" id="description" name="description" maxlength="200" value="{{$article->description ?? ''}}" required>
    </div>

    <div class="mb-3">
        <label for="article" class="form-label">{{__('adminTemplate.article.form.article')}}</label>
        <div id="article" style="min-height:200px;"></div>
    </div>

    <div class="input-group mb-3">
        <input type="text" class="form-control" placeholder="Meta tag" id="metatagInput" name="metatagInput">
        <button class="btn btn-outline-secondary" type="button" id="button-addon2" onclick="javascript: addTag();">{{__('adminTemplate.form.btn.addTag')}}</button>
        <button class="btn btn-outline-secondary" type="button" id="button-addon2" onclick="javascript: removeTag();">{{__('adminTemplate.form.btn.removeTag')}}</button>
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
            @include('utils.alertSuccess', ['message' => __('adminTemplate.article.okmessage')])
        @else
            @include('utils.alertDanger', ['message' => $message ?? __('adminTemplate.article.errmessage')])
        @endif
    @endisset

    <div class="mb-3">
        <button type="submit" class="btn btn-primary"><i class="fa-regular fa-floppy-disk"></i> {{__('adminTemplate.form.btn.save')}}</button>
        <a href="{{route('admin.articleList')}}" role="button" class="btn btn-outline-primary"><i class="fa fa-search"></i> {{__('adminTemplate.form.btn.search')}}</a>
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
                alert("{{__('adminTemplate.article.require.article')}}");
                return false;
            }

            if(document.getElementById('articleCoverPath').value == '' && document.getElementById('articleCover').files.length == 0){
                event.preventDefault();
                alert("{{__('adminTemplate.article.require.cover')}}");
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
            alert("{{__('adminTemplate.article.tag.duplicate1')}} "+ inputVal.value + " {{__('adminTemplate.article.tab.duplicate2')}}");
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