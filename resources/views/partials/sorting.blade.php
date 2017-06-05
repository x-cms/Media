<h3 class="section">排序</h3>

<select
        name="sorting"
        class="form-control custom-select select-no-search"
        data-control="sorting">
    @foreach ($sortModes as $k => $v)
        <option
                {{ $k == $sortBy ? 'selected="selected"' : '' }}
                value="{{ $k }}"
        >{{ $v }}</option>
    @endforeach
</select>