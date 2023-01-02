@php
    $opts = [1 => __('adminTemplate.form.active.true'), 0 => __('adminTemplate.form.active.false')];
    $label = $label ?? __('adminTemplate.form.active');
    $comboId = $comboId ?? 'active';
@endphp

<div class="mb-3">
    <label for="{{$comboId}}">{{$label}}</label>
    <select class="form-control" id="{{$comboId}}" name="{{$comboId}}" required>
        <option value="">{{__('adminTemplate.form.active.select')}}</option>
        @foreach($opts as $val => $opt)
        <option value="{{$val}}"{{isset($active) && $val==(int) $active ? ' selected="selected"' : ''}}>{{$opt}}</option>
        @endforeach
    </select>
</div>