@if(session()->has('saved'))
<div class="alert alert-{{session('saved') ? 'success' : 'danger'}} alert-dismissible fade show mt-5 fixed-top" style="left:25%; right:25%;" role="alert" id="response-message">
    {{session('saved-message')}}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif