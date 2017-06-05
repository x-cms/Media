@if (!$isError)
<img src="{{ $imageUrl }}"/>
@else
<i class="icon-chain-broken" title="生产缩略图错误."></i>
<p class="thumbnail-error-message">生产缩略图错误.</p>
@endif