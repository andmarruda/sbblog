<script src="{{asset('js/highlight.min.js')}}"></script>
<script src="{{asset('js/quill.min.js')}}"></script>
<script src="../node_modules/@yaireo/tagify/dist/tagify.min.js"></script>
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
                [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                ['blockquote', 'code-block'],
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