@csrf
<input type="hidden" name="id" id="id" value="{{ $article->id }}">
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
            <label for="title" class="form-label">{{__('adminTemplate.article.form.title')}}</label>
            <input type="text" maxlength="150" class="form-control" id="title" name="title" placeholder="{{__('adminTemplate.article.form.title')}}" required value="{{ old('title', $article) }}">
        </div>

        <div class="mb-3">
            <label for="category" class="form-label">{{__('adminTemplate.article.form.category')}}</label>
            <select class="form-control" id="category" name="category" required>
                <option value="">{{__('adminTemplate.article.form.category.select')}}</option>
                @foreach($categories as $category)
                    <option value="{{$category->id}}"{{ ($cat->id == ($article->category_id ?? -1)) ? ' selected="selected"' : '' }}>{{$category->category}}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">{{__('adminTemplate.article.form.meta')}} <small>{{__('adminTemplate.article.form.meta.small')}}</small></label>
            <input type="text" class="form-control" id="description" name="description" maxlength="200" value="{{ old('description', $article) }}" required>
        </div>

        <div class="mb-3">
            <label for="premiere_date" class="form-label">{{__('adminTemplate.article.form.premiereDate')}}</label>
            <div class="row">
                <div class="col">
                    <input type="date" class="form-control" id="premiere_date" name="premiere_date" value="{{!isset($article->premiere_date) ? '' : date('Y-m-d', strtotime($article->premiere_date))}}">
                </div>
                <div class="col">
                    <input type="time" class="form-control" id="premiere_time" name="premiere_time" value="{{!isset($article->premiere_date) ? '' : date('H:i', strtotime($article->premiere_time))}}">
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