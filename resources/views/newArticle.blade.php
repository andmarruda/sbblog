@extends('templates.adminTemplate')

@section('css')
<link rel="stylesheet" href="{{asset('css/monokai-sublime.min.css')}}">
<link rel="stylesheet" href="{{asset('css/quill.snow.css')}}">
<style>
    #article{
        min-height:200px; 
        max-height:500px; 
        overflow-y:auto;
    }

    #articleCoverPreview {
        max-height: 200px;
    }
</style>
@endsection

@section('page')
<nav aria-label="breadcrumb" style="margin-top:30px;">
    <ol class="breadcrumb">
        <li class="breadcrumb-item active" aria-current="page">{{(__('adminTemplate.article.title'))}}</li>
    </ol>
</nav>

<nav>
  <div class="nav nav-tabs" id="nav-tab" role="tablist">
    <button class="nav-link active" id="nav-form-tab" data-bs-toggle="tab" data-bs-target="#nav-form" type="button" role="tab" aria-controls="nav-form" aria-selected="true">{{__('adminTemplate.article.tab.form')}}</button>
    <button class="nav-link" id="nav-comment-tab" data-bs-toggle="tab" data-bs-target="#nav-comment" type="button" role="tab" aria-controls="nav-comment" aria-selected="false">{{__('adminTemplate.article.tab.comment')}}</button>
  </div>
</nav>
<div class="tab-content" id="nav-tabContent" style="margin-bottom:20px;">
  <div class="tab-pane fade show active" id="nav-form" role="tabpanel" aria-labelledby="nav-form-tab">
    <form method="post" id="formArticleSave" action="{{ route('admin.newArticlePost') }}" style="margin-top:20px;" autocomplete="off" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="id" id="id" value="{{$article->id ?? ''}}">
        <input type="hidden" name="addedTags" id="addedTags" value="">
        <input type="hidden" name="removedTags" id="removedTags" value="">
        <input type="hidden" id="articleText" name="articleText" value="{{$article->article ?? ''}}">
        <input type="hidden" id="articleCoverPath" name="articleCoverPath" value="{{$article->cover_path ?? ''}}">

        <div class="row">
            <div class="col-6">
                <div class="mb-3 text-center">
                    <label for="articleCoverPreview" class="form-lable">{{__('adminTemplate.article.form.previewFolder')}}</label><br>
                    <img src="@isset($article->cover_path) {{asset('storage/'.($article->cover_path))}} @endisset" alt="{{$article->title ?? ''}}" class="img-fluid" id="articleCoverPreview">
                </div>
                @isset($article->cover_path)
                <div class="mb-3">
                    @if((new \App\Http\Controllers\ImageController(NULL))->getExtension('public/'. $article->cover_path) != 'webp')
                    <a href="{{route('admin.article.convertWebp', ['id' => $article->id ?? -1])}}" class="btn btn-outline-primary" role="button">{{__('adminTemplate.article.form.convertWebp')}}</a>
                    @endif
                </div>
                @endisset

                <div class="mb-3">
                    <label for="articleCover" class="form-label">{{__('adminTemplate.article.form.cover')}}</label>
                    <input type="file" class="form-control" id="articleCover" name="articleCover">
                </div>
            </div>

            <div class="col-6">
                <div class="mb-3">
                    <label for="articleName" class="form-label">{{__('adminTemplate.article.form.title')}}</label>
                    <input type="text" maxlength="150" class="form-control" id="articleName" name="articleName" placeholder="{{__('adminTemplate.article.form.title')}}" required value="{{$article->title ?? ''}}">
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

                <div class="mb-3">
                    <label for="description" class="form-label">{{__('adminTemplate.article.form.meta')}} <small>{{__('adminTemplate.article.form.meta.small')}}</small></label>
                    <input type="text" class="form-control" id="description" name="description" maxlength="200" value="{{$article->description ?? ''}}" required>
                </div>

                <div class="mb-3">
                    <label for="premiereDate" class="form-label">{{__('adminTemplate.article.form.premiereDate')}}</label>
                    <div class="row">
                        <div class="col">
                            <input type="date" class="form-control" id="premiereDate" name="premiereDate" value="{{!isset($article->premiere_date) ? '' : date('Y-m-d', strtotime($article->premiere_date))}}">
                        </div>
                        <div class="col">
                            <input type="time" class="form-control" id="premiereTime" name="premiereTime" value="{{!isset($article->premiere_date) ? '' : date('H:i', strtotime($article->premiere_time))}}">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-3">
            <label for="article" class="form-label">{{__('adminTemplate.article.form.article')}}</label>
            <div id="article"></div>
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
            <a href="{{route('article.index')}}" role="button" class="btn btn-outline-primary"><i class="fa fa-search"></i> {{__('adminTemplate.form.btn.search')}}</a>
        </div>
    </form>
  </div>
  <div class="tab-pane fade" id="nav-comment" role="tabpanel" aria-labelledby="nav-comment-tab" style="padding-top:20px;">
    @if(!isset($article) || $article->comments()->count() == 0)
        @include('utils.commentCardNotFounded', ['advice' => __('adminTemplate.article.commentList.none')])
    @else
        @foreach($article->comments()->orderBy('created_at', 'desc')->get() as $comm)
            @include('utils.commentCard', ['name' => $comm->comment_name, 'comment' => $comm->comment_text, 'created_at' => $comm->created_at, 'admin' => true, 'id' => $comm->id, 'active' => $comm->active])
        @endforeach
    @endif
  </div>
</div>
@endsection

@section('jscript')
<script src="{{asset('js/highlight.min.js')}}"></script>
<script src="{{asset('js/quill.min.js')}}"></script>
<script>
    const loadPreviewImage = ({ target }) => {
        let reader = new FileReader();
        reader.onload = (e) => {
            document.getElementById('articleCoverPreview').src = e.target.result;
        };
        reader.readAsDataURL(target.files[0]);
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('articleCover').addEventListener('change', loadPreviewImage);
    });
</script>

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

    //enable disable comments
    const enableText = '{{__('adminTemplate.article.commentList.enable')}}';
    const disableText = '{{__('adminTemplate.article.commentList.disable')}}';
    const commentAction = async (event, id) => {
        let header = new Headers();
        header.append('X-CSRF-Token', '{{csrf_token()}}');

        let fd = new FormData();
        fd.append('id', id);

        let f = await fetch("{{URL::to('/')}}/admin/article/comment/enable-disable", {
            method: 'post',
            headers: header,
            body: fd
        });

        let j = await f.json();
        if(!j.success){
            alert(j.message);
            return;
        }

        if(event.target.classList.contains('btn-danger')){
            event.target.classList.remove('btn-danger');
            event.target.classList.add('btn-success');
            event.target.innerText = enableText;
        } else{
            event.target.classList.remove('btn-success');
            event.target.classList.add('btn-danger');
            event.target.innerText = disableText;
        }
    };
</script>
@endsection