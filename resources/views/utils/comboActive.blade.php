@php
    $opts = [1 => __('adminTemplate.form.active.true'), 0 => __('adminTemplate.form.active.false')];
@endphp

<div class="mb-3">
    <label for="active">{{__('adminTemplate.form.active')}}</label>
    <select class="form-control" id="active" name="active" required>
        <option value="">{{__('adminTemplate.form.active.select')}}</option>
        @foreach($opts as $val => $opt)
        <option value="{{$val}}"{{isset($active) && $val==(int) $active ? ' selected="selected"' : ''}}>{{$opt}}</option>
        @endforeach
    </select>
</div>