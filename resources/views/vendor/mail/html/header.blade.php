@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">

    <!-- <img src="{{ asset('storage/logos/your-logo.png') }}" alt="{{ $slot }}" height="75"> -->
    <img src="{{ url(asset('storage/logos/akont_logo.png')) }}" alt="{{ config('app.name') }} Logo" style="height: 75px;">

</a>
</td>
</tr>

