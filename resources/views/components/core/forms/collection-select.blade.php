<div class="mb-5">
    <label class="label block mb-2" for="{{$name}}">{{$label}} </label>
    <select class="form-control" name="{{$name}}">
        <option value="">Please select</option>
        @foreach($options as $option)
            @php
                $modelValue  = $model->{$name};
                $optionValue = $option->{$value};
                $optionTitle = $option->{$key};
            @endphp
            <option value="{{$optionValue}}" {{!is_null($model) ? $modelValue === $optionValue ? 'selected' : '' : ''}}>{{$optionTitle}}</option>
        @endforeach
    </select>
</div>
