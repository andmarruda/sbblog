<div class="mb-3">
    <label for="active">{{__('adminTemplate.form.active')}}</label>
    <select class="form-control" id="active" name="active" required>
        <option value="">Selecione...</option>
        @if(isset($active) && $active)
        <option value="1" selected="selected">Ativo</option>
        @else
        <option value="1">Ativo</option>
        @endif

        @if(isset($active) && !$active)
        <option value="0" selected="selected">Inativo</option>
        @else
        <option value="0">Inativo</option>
        @endif
    </select>
</div>